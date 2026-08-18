<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\PrintFlow;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Services\PrintFlowManager;
use App\Support\CurrentEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PrintFlowController extends Controller
{
    public function __construct(private readonly CurrentEvent $currentEvent) {}

    public function index(Request $request): View
    {
        $query = PrintFlow::query()->with(['participant', 'teamMember', 'tokens' => fn ($q) => $q->latest()])->latest('distributed_at');

        foreach (['status', 'type', 'team_member_id', 'participant_id'] as $filter) {
            $value = $request->input($filter);
            if ($value !== null && $value !== '') {
                $query->where($filter, $value);
            }
        }

        if ($request->boolean('expired')) {
            $query->whereHas('tokens', fn ($q) => $q->where('expires_at', '<', now())->whereNull('invalidated_at'));
        }

        $minimum = (int) Setting::valueFor('print_flow_min_testimonials', $this->currentEvent->get()->slug === 'edd' ? 2 : 3);
        $criticalParticipants = Participant::active()
            ->criticalForPrintFlow($minimum)
            ->withCount(['testimonials' => fn ($q) => $q->where('status', '!=', 'archived')])
            ->orderBy('name')
            ->get();

        return view('admin.print-flows.index', [
            'flows' => $query->paginate(15)->withQueryString(),
            'participants' => Participant::active()->orderBy('name')->get(),
            'members' => $this->authorizedMembers(),
            'criticalParticipants' => $criticalParticipants,
            'minimumTestimonials' => $minimum,
            'statuses' => PrintFlow::STATUSES,
            'types' => PrintFlow::TYPES,
        ]);
    }

    public function create(): View
    {
        return view('admin.print-flows.create', [
            'participants' => Participant::active()->withCount('testimonials')->orderBy('name')->get(),
            'members' => $this->authorizedMembers(),
            'types' => PrintFlow::TYPES,
        ]);
    }

    public function store(Request $request, PrintFlowManager $manager): RedirectResponse
    {
        $validated = $request->validate([
            'participant_id' => ['required', Rule::exists('participants', 'id')->where('event_id', $this->currentEvent->id())],
            'team_member_id' => ['required', Rule::exists('team_members', 'id')->where('status', 'active')],
            'type' => ['required', Rule::in(array_keys(PrintFlow::TYPES))],
        ]);

        $result = $manager->distribute($validated, (int) Auth::id(), $request);

        return redirect()->route('admin.print-flows.index')
            ->with('success', 'Fluxo distribuído com sucesso.')
            ->with('flow_share', [
                'access_url' => $result['access_url'],
                'whatsapp_url' => $result['whatsapp_url'],
                'participant' => $result['flow']->participant->label,
            ]);
    }

    public function renew(Request $request, PrintFlow $flow, PrintFlowManager $manager): RedirectResponse
    {
        abort_if(in_array($flow->status, ['completed', 'cancelled'], true), 422, 'Não é possível renovar este fluxo.');
        $result = $manager->renewToken($flow, (int) Auth::id(), $request);

        return back()->with('success', 'Novo link gerado. O anterior foi invalidado.')
            ->with('flow_share', [
                'access_url' => $result['access_url'],
                'whatsapp_url' => $result['whatsapp_url'],
                'participant' => $flow->participant->label,
            ]);
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
            ->whereHas('events', fn ($q) => $q->whereKey($this->currentEvent->id())->where('event_team_member.is_active', true))
            ->withCount(['printFlows as open_tasks_count' => fn ($q) => $q->withoutGlobalScopes()->whereIn('status', PrintFlow::OPEN_STATUSES)])
            ->orderBy('name')
            ->get();
    }
}
