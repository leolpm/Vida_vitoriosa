<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\PrintFlow;
use App\Models\TeamMember;
use App\Services\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class TeamMemberController extends Controller
{
    public function index(Request $request): View
    {
        $name = $request->string('name')->trim()->toString();
        $status = $request->string('status')->toString();

        $query = TeamMember::query()
            ->with('events')
            ->withCount(['printFlows as open_tasks_count' => fn ($q) => $q->withoutGlobalScopes()->whereIn('status', PrintFlow::OPEN_STATUSES)])
            ->orderBy('name');

        if ($name !== '') {
            $query->where('name', 'like', '%'.$name.'%');
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        return view('admin.team.index', [
            'members' => $query->paginate(15)->withQueryString(),
            'name' => $name,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.team.form', [
            'member' => new TeamMember(['status' => 'active']),
            'events' => Event::active()->orderBy('name')->get(),
            'selectedEvents' => collect(),
        ]);
    }

    public function store(Request $request, PhoneNormalizer $normalizer): RedirectResponse
    {
        $validated = $this->validateRequest($request);
        $validated['phone'] = $this->normalizePhone($validated['phone'], $normalizer);
        $eventIds = $validated['event_ids'];
        unset($validated['event_ids']);

        $member = TeamMember::create($validated);
        $this->syncEvents($member, $eventIds);

        return redirect()->route('admin.team.index')->with('success', 'Membro da equipe criado com sucesso.');
    }

    public function edit(TeamMember $member): View
    {
        return view('admin.team.form', [
            'member' => $member,
            'events' => Event::active()->orderBy('name')->get(),
            'selectedEvents' => $member->events()->wherePivot('is_active', true)->pluck('events.id'),
        ]);
    }

    public function update(Request $request, TeamMember $member, PhoneNormalizer $normalizer): RedirectResponse
    {
        $validated = $this->validateRequest($request);
        $validated['phone'] = $this->normalizePhone($validated['phone'], $normalizer);
        $eventIds = $validated['event_ids'];
        unset($validated['event_ids']);

        $member->update($validated);
        $this->syncEvents($member, $eventIds);

        return redirect()->route('admin.team.index')->with('success', 'Membro da equipe atualizado com sucesso.');
    }

    public function destroy(TeamMember $member): RedirectResponse
    {
        if ($member->printFlows()->withoutGlobalScopes()->exists()) {
            return back()->with('error', 'Este membro possui fluxos no histórico e deve ser apenas desativado.');
        }

        $member->delete();

        return redirect()->route('admin.team.index')->with('success', 'Membro da equipe removido com sucesso.');
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'status' => ['required', 'in:active,inactive'],
            'task_limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'event_ids' => ['required', 'array', 'min:1'],
            'event_ids.*' => ['integer', 'exists:events,id'],
        ]);
    }

    private function normalizePhone(string $phone, PhoneNormalizer $normalizer): string
    {
        try {
            return $normalizer->normalize($phone);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['phone' => $exception->getMessage()]);
        }
    }

    private function syncEvents(TeamMember $member, array $eventIds): void
    {
        $member->events()->sync(collect($eventIds)->mapWithKeys(
            fn (int|string $eventId): array => [(int) $eventId => ['is_active' => true]]
        ));
    }
}
