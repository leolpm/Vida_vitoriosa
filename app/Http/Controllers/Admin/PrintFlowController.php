<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\PrintFlow;
use App\Models\TeamMember;
use App\Services\PrintFlowCandidateService;
use App\Services\PrintFlowManager;
use App\Support\CurrentEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PrintFlowController extends Controller
{
    public function __construct(
        private readonly CurrentEvent $currentEvent,
        private readonly PrintFlowCandidateService $candidates,
    ) {}

    public function index(Request $request): View
    {
        $selectedStatuses = collect((array) $request->input('status', []))
            ->filter(fn ($status): bool => is_string($status))
            ->intersect(array_keys(PrintFlow::STATUSES))
            ->unique()
            ->values()
            ->all();

        $query = PrintFlow::query()
            ->with(['participant', 'teamMember', 'tokens' => fn ($tokenQuery) => $tokenQuery->latest()])
            ->latest('distributed_at');

        if ($selectedStatuses !== []) {
            $query->whereIn('status', $selectedStatuses);
        }

        foreach (['type', 'team_member_id', 'participant_id'] as $filter) {
            $value = $request->input($filter);
            if ($value !== null && $value !== '') {
                $query->where($filter, $value);
            }
        }

        if ($request->boolean('expired')) {
            $query->whereHas('tokens', fn ($tokenQuery) => $tokenQuery
                ->where('expires_at', '<', now())
                ->whereNull('invalidated_at'));
        }

        $dashboard = $this->candidates->dashboardData();

        return view('admin.print-flows.index', [
            'flows' => $query->paginate(15)->withQueryString(),
            'participants' => Participant::active()->orderBy('name')->get(),
            'members' => $this->authorizedMembers(),
            'criticalParticipants' => $dashboard['critical_participants'],
            'minimumTestimonials' => $dashboard['minimum_testimonials'],
            'criticalCount' => $dashboard['critical_count'],
            'criticalWithOpenTaskCount' => $dashboard['critical_with_open_task_count'],
            'mainCandidatesCount' => $dashboard['main_candidates_count'],
            'mainLettersCount' => $dashboard['main_letters_count'],
            'reviewCandidatesCount' => $dashboard['review_candidates_count'],
            'reviewLettersCount' => $dashboard['review_letters_count'],
            'selectedStatuses' => $selectedStatuses,
            'statuses' => PrintFlow::STATUSES,
            'types' => PrintFlow::TYPES,
        ]);
    }

    public function create(Request $request): View
    {
        $requestedType = (string) $request->old('type', $request->input('type'));
        $type = array_key_exists($requestedType, PrintFlow::TYPES)
            ? $requestedType
            : 'main_print';
        $includeReevaluated = (bool) $request->old('include_reevaluated', $request->boolean('include_reevaluated'));

        return view('admin.print-flows.create', [
            'types' => PrintFlow::TYPES,
            'initialType' => $type,
            'initialOptions' => $this->candidates->options($type, $includeReevaluated),
            'includeReevaluated' => $includeReevaluated,
        ]);
    }

    public function distributionOptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(PrintFlow::TYPES))],
            'include_reevaluated' => ['nullable', 'boolean'],
        ]);

        return response()->json($this->candidates->options(
            $validated['type'],
            $request->boolean('include_reevaluated'),
        ));
    }

    public function store(Request $request, PrintFlowManager $manager): RedirectResponse
    {
        $validated = $request->validate([
            'participant_id' => [
                'required',
                Rule::exists('participants', 'id')->where(fn ($query) => $query
                    ->where('event_id', $this->currentEvent->id())
                    ->where('status', 'active')),
            ],
            'team_member_id' => ['required', Rule::exists('team_members', 'id')->where('status', 'active')],
            'type' => ['required', Rule::in(array_keys(PrintFlow::TYPES))],
            'testimonial_ids' => ['nullable', 'array'],
            'testimonial_ids.*' => ['integer', 'distinct'],
            'include_reevaluated' => ['nullable', 'boolean'],
        ]);

        $result = $manager->distribute($validated, (int) Auth::id(), $request);
        $flow = $result['flow'];

        return redirect()->route('admin.print-flows.share', $flow)
            ->with('success', 'Fluxo distribuído com sucesso.')
            ->with('flow_share', $this->sharePayload($flow, $result));
    }

    public function share(PrintFlow $flow): View
    {
        $flow->load(['participant', 'teamMember', 'event', 'testimonials']);
        $share = session('flow_share');

        if (! is_array($share) || (int) ($share['flow_id'] ?? 0) !== $flow->id) {
            $share = null;
        }

        return view('admin.print-flows.share', compact('flow', 'share'));
    }

    public function renew(Request $request, PrintFlow $flow, PrintFlowManager $manager): RedirectResponse
    {
        abort_if(in_array($flow->status, ['completed', 'cancelled'], true), 422, 'Não é possível renovar este fluxo.');
        $flow->load(['participant', 'teamMember', 'event']);
        $result = $manager->renewToken($flow, (int) Auth::id(), $request);

        return redirect()->route('admin.print-flows.share', $flow)
            ->with('success', 'Novo link gerado. O anterior foi invalidado.')
            ->with('flow_share', $this->sharePayload($flow, $result));
    }

    public function cancel(Request $request, PrintFlow $flow, PrintFlowManager $manager): RedirectResponse
    {
        if (! $flow->isOpen()) {
            return back()->with('error', 'Somente fluxos em andamento podem ser cancelados.');
        }

        $before = ['status' => $flow->status, 'current_step' => $flow->current_step];
        $flow->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $flow->tokens()->whereNull('invalidated_at')->update([
            'invalidated_at' => now(),
            'invalidation_reason' => 'flow_cancelled',
        ]);
        $manager->audit($flow, 'admin', (int) Auth::id(), 'flow_cancelled', $before, ['status' => 'cancelled'], $request);

        return back()->with('success', 'Fluxo cancelado com sucesso.');
    }

    private function authorizedMembers()
    {
        return TeamMember::active()
            ->whereHas('events', fn ($query) => $query
                ->whereKey($this->currentEvent->id())
                ->where('event_team_member.is_active', true))
            ->withCount(['printFlows as open_tasks_count' => fn ($query) => $query
                ->withoutGlobalScopes()
                ->whereIn('status', PrintFlow::OPEN_STATUSES)])
            ->orderBy('name')
            ->get();
    }

    private function sharePayload(PrintFlow $flow, array $result): array
    {
        return [
            'flow_id' => $flow->id,
            'access_url' => $result['access_url'],
            'whatsapp_url' => $result['whatsapp_url'],
            'expires_at' => $result['expires_at']->format('d/m/Y H:i'),
            'max_accesses' => $result['max_accesses'],
        ];
    }
}
