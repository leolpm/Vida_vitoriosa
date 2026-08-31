<?php

namespace App\Http\Controllers;

use App\Exceptions\PrintFlowAccessException;
use App\Models\PrintFlowReview;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Services\PrintFlowAccessService;
use App\Services\PrintFlowManager;
use App\Services\PrintPageComposer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PrintFlowPortalController extends Controller
{
    public function show(string $token, Request $request, PrintFlowAccessService $access): View|Response
    {
        try {
            $flow = $access->resolve($token, $request);
        } catch (PrintFlowAccessException $exception) {
            return response()->view('print-flows.blocked', ['message' => $exception->getMessage()], $exception->status);
        }

        if ($flow->status === 'completed') {
            return view('print-flows.completed', compact('flow'));
        }

        if ($flow->type === 'testimonial_search') {
            return view('print-flows.search', compact('flow', 'token'));
        }

        $flow->load(['testimonials' => fn ($q) => $q->orderBy('created_at'), 'reviews']);
        $latestReviews = $flow->reviews->sortByDesc('id')->unique('testimonial_id')->keyBy('testimonial_id');
        $reviewHistory = PrintFlowReview::query()
            ->whereIn('testimonial_id', $flow->testimonials->pluck('id'))
            ->with(['teamMember:id,name', 'printFlow:id,type,status'])
            ->orderByDesc('decided_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('testimonial_id');

        return view('print-flows.review', compact('flow', 'token', 'latestReviews', 'reviewHistory'));
    }

    public function review(
        string $token,
        Testimonial $testimonial,
        Request $request,
        PrintFlowAccessService $access,
        PrintFlowManager $manager,
    ): RedirectResponse {
        $flow = $access->resolve($token, $request);
        abort_unless($flow->current_step === 'review' && $flow->testimonials()->whereKey($testimonial->id)->exists(), 404);

        $anchor = 'revisao-carta-'.$testimonial->id;
        $validator = Validator::make($request->all(), [
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'rejection_reason' => ['nullable', 'required_if:decision,rejected', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('print-flows.show', $token)
                ->withFragment($anchor)
                ->withErrors($validator)
                ->withInput()
                ->with('active_review_testimonial_id', $testimonial->id)
                ->with('review_validation_errors', $validator->errors()->all());
        }

        $validated = $validator->validated();

        $review = PrintFlowReview::create([
            'event_id' => $flow->event_id,
            'print_flow_id' => $flow->id,
            'testimonial_id' => $testimonial->id,
            'team_member_id' => $flow->team_member_id,
            'decision' => $validated['decision'],
            'rejection_reason' => $validated['decision'] === 'rejected' ? $validated['rejection_reason'] : null,
            'decided_at' => now(),
        ]);

        $manager->audit($flow, 'team_member', $flow->team_member_id, 'testimonial_reviewed', null, [
            'review_id' => $review->id,
            'testimonial_id' => $testimonial->id,
            'decision' => $review->decision,
        ], $request);

        return redirect()
            ->route('print-flows.show', $token)
            ->withFragment($anchor)
            ->with('success', 'Decisão registrada para a carta de '.$testimonial->sender_name.'.')
            ->with('active_review_testimonial_id', $testimonial->id)
            ->with('review_saved_testimonial_id', $testimonial->id);
    }

    public function finishReview(
        string $token,
        Request $request,
        PrintFlowAccessService $access,
        PrintFlowManager $manager,
    ): RedirectResponse {
        $flow = $access->resolve($token, $request);
        abort_unless($flow->current_step === 'review', 422);

        $testimonialIds = $flow->testimonials()->pluck('testimonials.id');
        $reviewedIds = PrintFlowReview::query()
            ->where('print_flow_id', $flow->id)
            ->whereIn('testimonial_id', $testimonialIds)
            ->distinct()
            ->pluck('testimonial_id');

        if ($reviewedIds->count() !== $testimonialIds->count()) {
            return back()->with('error', 'Revise todas as cartas antes de concluir esta etapa.');
        }

        $before = ['status' => $flow->status, 'current_step' => $flow->current_step];
        $flow->update(['status' => 'ready_to_print', 'current_step' => 'print']);
        $manager->audit($flow, 'team_member', $flow->team_member_id, 'review_completed', $before, [
            'status' => $flow->status,
            'current_step' => $flow->current_step,
        ], $request);

        return redirect()->route('print-flows.show', $token)->with('success', 'Revisão concluída. O lote está pronto para impressão.');
    }

    public function print(
        string $token,
        Request $request,
        PrintFlowAccessService $access,
        PrintFlowManager $manager,
        PrintPageComposer $composer,
    ): View {
        $flow = $access->resolve($token, $request);
        abort_unless(in_array($flow->status, ['ready_to_print', 'printing'], true), 422);

        $latestReviewIds = PrintFlowReview::query()
            ->selectRaw('MAX(id)')
            ->where('print_flow_id', $flow->id)
            ->groupBy('testimonial_id');
        $approvedIds = PrintFlowReview::query()
            ->whereIn('id', $latestReviewIds)
            ->where('decision', 'approved')
            ->pluck('testimonial_id');
        $testimonials = $flow->testimonials()->whereIn('testimonials.id', $approvedIds)->orderBy('created_at')->get();
        $pages = $composer->compose($testimonials);

        if ($flow->status !== 'printing') {
            $before = ['status' => $flow->status, 'current_step' => $flow->current_step];
            $flow->update(['status' => 'printing', 'current_step' => 'complete']);
            $manager->audit($flow, 'team_member', $flow->team_member_id, 'print_opened', $before, [
                'status' => $flow->status,
                'total_pages' => $pages['total_pages'],
            ], $request);
        }

        $settings = [
            'retreat_name' => Setting::valueFor('retreat_name', $flow->event->name),
            'retreat_location' => Setting::valueFor('retreat_location', ''),
            'retreat_year' => Setting::valueFor('retreat_year', ''),
            'pdf_footer_text' => Setting::valueFor('pdf_footer_text', $flow->event->name),
            'pdf_header_image_path' => Setting::valueFor('pdf_header_image_path'),
        ];

        return view('print-flows.print', compact('flow', 'token', 'testimonials', 'pages', 'settings'));
    }

    public function complete(
        string $token,
        Request $request,
        PrintFlowAccessService $access,
        PrintFlowManager $manager,
    ): RedirectResponse {
        $flow = $access->resolve($token, $request);
        $request->validate(['printed_confirmation' => ['accepted']]);
        abort_unless(in_array($flow->status, ['printing', 'ready_to_print'], true), 422);

        return $this->completeFlow($flow, $token, $request, $manager, 'print_completed');
    }

    public function completeSearch(
        string $token,
        Request $request,
        PrintFlowAccessService $access,
        PrintFlowManager $manager,
    ): RedirectResponse {
        $flow = $access->resolve($token, $request);
        $request->validate(['search_confirmation' => ['accepted']]);
        abort_unless($flow->type === 'testimonial_search' && $flow->isOpen(), 422);

        return $this->completeFlow($flow, $token, $request, $manager, 'testimonial_search_completed');
    }

    private function completeFlow($flow, string $token, Request $request, PrintFlowManager $manager, string $action): RedirectResponse
    {
        DB::transaction(function () use ($flow, $request, $manager, $action): void {
            $before = ['status' => $flow->status, 'current_step' => $flow->current_step];
            $flow->update(['status' => 'completed', 'current_step' => 'complete', 'completed_at' => now()]);
            $manager->audit($flow, 'team_member', $flow->team_member_id, $action, $before, [
                'status' => 'completed',
                'completed_at' => $flow->completed_at,
            ], $request);
        });

        return redirect()->route('print-flows.show', $token)->with('success', 'Tarefa concluída com sucesso.');
    }
}
