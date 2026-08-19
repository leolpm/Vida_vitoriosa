@extends('layouts.admin')

@section('title', 'Fluxo de Impressão')
@section('section', 'Operação')
@section('page-title', 'Fluxo de Impressão · ' . $currentEvent->name)

@section('content')
<div class="row g-3 mb-4">
    <div class="col-12 col-lg-4">
        <a href="{{ route('admin.print-flows.create', ['type' => 'testimonial_search']) }}" class="text-decoration-none text-reset">
            <div class="stat-card p-4 h-100 operational-card">
                <div class="d-flex justify-content-between gap-3 align-items-start">
                    <div>
                        <div class="section-eyebrow text-warning-emphasis mb-2">Busca de depoimentos</div>
                        <div class="display-6 fw-bold">{{ $criticalCount }}</div>
                        <div class="fw-semibold">Participantes abaixo da meta</div>
                    </div>
                    <span class="stat-icon"><i class="bi bi-bullseye"></i></span>
                </div>
                <div class="small text-secondary mt-3">
                    Meta: {{ $minimumTestimonials }} carta(s) · {{ $criticalWithOpenTaskCount }} com tarefa aberta
                    <i class="bi bi-info-circle ms-1" data-bs-toggle="tooltip" title="Conta participantes ativos abaixo da meta do evento. O número secundário informa quantos já possuem uma busca distribuída."></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-lg-4">
        <a href="{{ route('admin.print-flows.create', ['type' => 'main_print']) }}" class="text-decoration-none text-reset">
            <div class="stat-card p-4 h-100 operational-card operational-card-print">
                <div class="d-flex justify-content-between gap-3 align-items-start">
                    <div>
                        <div class="section-eyebrow text-primary mb-2">Impressão principal</div>
                        <div class="display-6 fw-bold">{{ $mainCandidatesCount }}</div>
                        <div class="fw-semibold">Participantes com cartas para impressão</div>
                    </div>
                    <span class="stat-icon"><i class="bi bi-printer"></i></span>
                </div>
                <div class="small text-secondary mt-3">
                    {{ $mainLettersCount }} carta(s) elegível(is)
                    <i class="bi bi-info-circle ms-1" data-bs-toggle="tooltip" title="Conta participantes com cartas aprovadas administrativamente, ainda sem decisão no fluxo e sem outra tarefa aberta."></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-lg-4">
        <a href="{{ route('admin.print-flows.create', ['type' => 'reevaluation']) }}" class="text-decoration-none text-reset">
            <div class="stat-card p-4 h-100 operational-card operational-card-review">
                <div class="d-flex justify-content-between gap-3 align-items-start">
                    <div>
                        <div class="section-eyebrow text-danger mb-2">Reavaliação</div>
                        <div class="display-6 fw-bold">{{ $reviewCandidatesCount }}</div>
                        <div class="fw-semibold">Participantes com cartas para revisão</div>
                    </div>
                    <span class="stat-icon"><i class="bi bi-arrow-repeat"></i></span>
                </div>
                <div class="small text-secondary mt-3">
                    {{ $reviewLettersCount }} carta(s) aguardando primeira reavaliação
                    <i class="bi bi-info-circle ms-1" data-bs-toggle="tooltip" title="Conta somente cartas reprovadas que ainda não passaram por uma reavaliação e não estão em outra tarefa aberta."></i>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="card-surface p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
        <div>
            <div class="section-eyebrow text-secondary mb-1">Distribuição</div>
            <h2 class="h5 mb-0">Tarefas do evento</h2>
        </div>
        <a href="{{ route('admin.print-flows.create') }}" class="btn btn-gold"><i class="bi bi-plus-lg me-1"></i>Nova tarefa</a>
    </div>

    <form method="GET" class="row g-3 align-items-end" id="flow-filters">
        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label small fw-semibold">Participante</label>
            <select name="participant_id" class="form-select">
                <option value="">Todos</option>
                @foreach($participants as $participant)
                    <option value="{{ $participant->id }}" @selected((string) request('participant_id') === (string) $participant->id)>{{ $participant->label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <label class="form-label small fw-semibold">Equipe</label>
            <select name="team_member_id" class="form-select">
                <option value="">Todos</option>
                @foreach($members as $member)
                    <option value="{{ $member->id }}" @selected((string) request('team_member_id') === (string) $member->id)>{{ $member->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6 col-xl-2">
            <label class="form-label small fw-semibold">Tipo</label>
            <select name="type" class="form-select">
                <option value="">Todos</option>
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6 col-xl-2">
            <label class="form-label small fw-semibold d-block">Status</label>
            <div class="dropdown">
                <button class="btn btn-outline-secondary bg-white text-dark dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <span>
                        @if(count($selectedStatuses) === 0)
                            Todos os status
                        @elseif(count($selectedStatuses) === 1)
                            {{ $statuses[$selectedStatuses[0]] }}
                        @else
                            {{ count($selectedStatuses) }} status selecionados
                        @endif
                    </span>
                </button>
                <div class="dropdown-menu p-3 status-dropdown shadow border-0">
                    <div class="small text-secondary mb-2">Selecione um ou mais</div>
                    @foreach($statuses as $value => $label)
                        <div class="form-check py-1">
                            <input class="form-check-input" type="checkbox" name="status[]" value="{{ $value }}" id="status-{{ $value }}" @checked(in_array($value, $selectedStatuses, true))>
                            <label class="form-check-label" for="status-{{ $value }}">{{ $label }}</label>
                        </div>
                    @endforeach
                    <button class="btn btn-sm btn-link text-secondary px-0 mt-2" type="button" id="clear-statuses">Limpar status</button>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-2 d-flex gap-2">
            <button class="btn btn-outline-dark flex-grow-1"><i class="bi bi-funnel me-1"></i>Filtrar</button>
            <a href="{{ route('admin.print-flows.index') }}" class="btn btn-outline-secondary" title="Limpar todos os filtros"><i class="bi bi-x-lg"></i></a>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="expired" value="1" id="expired" @checked(request()->boolean('expired'))>
                <label class="form-check-label small" for="expired">Somente com link vencido</label>
            </div>
        </div>
    </form>

    @if($selectedStatuses !== [])
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3" aria-label="Status selecionados">
            <span class="small text-secondary">Status ativos:</span>
            @foreach($selectedStatuses as $selectedStatus)
                @php
                    $remainingStatuses = array_values(array_diff($selectedStatuses, [$selectedStatus]));
                    $removeStatusQuery = request()->except(['status', 'page']);
                    if ($remainingStatuses !== []) $removeStatusQuery['status'] = $remainingStatuses;
                @endphp
                <a class="badge rounded-pill text-bg-light border text-decoration-none text-dark px-3 py-2" href="{{ route('admin.print-flows.index', $removeStatusQuery) }}">
                    {{ $statuses[$selectedStatus] }} <i class="bi bi-x ms-1"></i>
                </a>
            @endforeach
        </div>
    @endif
</div>

<div class="card-surface p-4 mb-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Participante</th><th>Responsável</th><th>Tipo</th><th>Status</th><th>Etapa</th><th>Distribuído</th><th>Link</th><th class="text-end">Ações</th></tr></thead>
            <tbody>
            @forelse($flows as $flow)
                @php($lastToken = $flow->tokens->first())
                <tr>
                    <td class="fw-semibold">{{ $flow->participant->label }}</td>
                    <td>{{ $flow->teamMember->name }}</td>
                    <td>{{ $flow->type_label }}</td>
                    <td><span class="badge {{ $flow->status === 'completed' ? 'text-bg-success' : ($flow->status === 'cancelled' ? 'text-bg-secondary' : 'text-bg-primary') }}">{{ $flow->status_label }}</span></td>
                    <td>{{ ucfirst(str_replace('_', ' ', $flow->current_step)) }}</td>
                    <td>{{ $flow->distributed_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($lastToken)
                            <span class="badge {{ $lastToken->invalidated_at || $lastToken->expires_at->isPast() ? 'text-bg-danger' : 'text-bg-light border' }}">{{ $lastToken->accesses_used }}/{{ $lastToken->max_accesses }} · {{ $lastToken->expires_at->format('H:i') }}</span>
                        @else
                            <span class="text-secondary">---</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.print-flows.share', $flow) }}" class="btn btn-sm btn-outline-dark">Detalhes</a>
                        @if($flow->isOpen())
                            <form method="POST" action="{{ route('admin.print-flows.renew', $flow) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Novo link</button></form>
                            <form method="POST" action="{{ route('admin.print-flows.cancel', $flow) }}" class="d-inline" onsubmit="return confirm('Cancelar este fluxo e invalidar o link?');">@csrf<button class="btn btn-sm btn-outline-danger">Cancelar</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-secondary py-4">Nenhum fluxo encontrado para os filtros selecionados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $flows->links() }}
</div>

<div class="card-surface p-4" id="critical-participants">
    <div class="section-eyebrow text-secondary mb-1">Busca de depoimentos</div>
    <h2 class="h5 mb-3">Participantes abaixo da meta do evento</h2>
    <div class="row g-3">
        @forelse($criticalParticipants as $participant)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="border rounded-4 p-3 d-flex justify-content-between align-items-center gap-3 h-100">
                    <div>
                        <div class="fw-semibold">{{ $participant->label }}</div>
                        <div class="small text-secondary">{{ $participant->current_testimonials_count }} de {{ $minimumTestimonials }} depoimento(s)</div>
                        @if($participant->has_open_search_task)
                            <span class="badge text-bg-primary mt-2">Tarefa de busca aberta</span>
                        @endif
                    </div>
                    @if(!$participant->has_open_search_task)
                        <a href="{{ route('admin.print-flows.create', ['type' => 'testimonial_search', 'participant_id' => $participant->id]) }}" class="btn btn-sm btn-outline-dark">Criar tarefa</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-secondary">Nenhum participante abaixo da meta.</div>
        @endforelse
    </div>
</div>
@endsection

@push('styles')
<style>
    .operational-card { transition: transform .2s ease, box-shadow .2s ease; }
    .operational-card:hover { transform: translateY(-3px); box-shadow: 0 20px 42px rgba(16, 27, 39, .12); }
    .operational-card-print { background: linear-gradient(135deg, #fff 0%, #edf5ff 100%); }
    .operational-card-review { background: linear-gradient(135deg, #fff 0%, #fff2f1 100%); }
    .status-dropdown { min-width: 260px; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => new bootstrap.Tooltip(element));
    document.getElementById('clear-statuses')?.addEventListener('click', () => {
        document.querySelectorAll('input[name="status[]"]').forEach((checkbox) => { checkbox.checked = false; });
    });
});
</script>
@endpush
