@extends('layouts.admin')

@section('title', 'Fluxo de Impressão')
@section('section', 'Operação')
@section('page-title', 'Fluxo de Impressão · ' . $currentEvent->name)

@section('content')
@if (session('flow_share'))
    @php($share = session('flow_share'))
    <div class="card-surface p-4 mb-4 border border-success-subtle">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            <div>
                <div class="section-eyebrow text-success mb-1">Link pronto</div>
                <h2 class="h5 mb-1">{{ $share['participant'] }}</h2>
                <div class="text-secondary text-break">{{ $share['access_url'] }}</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-dark" type="button" data-copy-url="{{ $share['access_url'] }}"><i class="bi bi-copy me-1"></i>Copiar link</button>
                <a class="btn btn-success" href="{{ $share['whatsapp_url'] }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp me-1"></i>Abrir WhatsApp</a>
            </div>
        </div>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-8">
        <div class="card-surface p-4 h-100">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                <div>
                    <div class="section-eyebrow text-secondary mb-1">Distribuição</div>
                    <h2 class="h5 mb-0">Fluxos do evento</h2>
                </div>
                <a href="{{ route('admin.print-flows.create') }}" class="btn btn-gold"><i class="bi bi-plus-lg me-1"></i>Novo fluxo</a>
            </div>
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-3"><label class="form-label small fw-semibold">Participante</label><select name="participant_id" class="form-select"><option value="">Todos</option>@foreach($participants as $participant)<option value="{{ $participant->id }}" @selected((string)request('participant_id') === (string)$participant->id)>{{ $participant->label }}</option>@endforeach</select></div>
                <div class="col-12 col-md-3"><label class="form-label small fw-semibold">Equipe</label><select name="team_member_id" class="form-select"><option value="">Todos</option>@foreach($members as $member)<option value="{{ $member->id }}" @selected((string)request('team_member_id') === (string)$member->id)>{{ $member->name }}</option>@endforeach</select></div>
                <div class="col-6 col-md-2"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Todos</option>@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-6 col-md-2"><label class="form-label small fw-semibold">Tipo</label><select name="type" class="form-select"><option value="">Todos</option>@foreach($types as $value=>$label)<option value="{{ $value }}" @selected(request('type')===$value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-outline-dark">Filtrar</button><a href="{{ route('admin.print-flows.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a></div>
                <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="expired" value="1" id="expired" @checked(request()->boolean('expired'))><label class="form-check-label small" for="expired">Somente com link vencido</label></div></div>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card-surface p-4 h-100 bg-warning-subtle bg-opacity-25">
            <div class="section-eyebrow text-warning-emphasis mb-1">Atenção</div>
            <div class="d-flex align-items-end justify-content-between">
                <div><div class="display-6 fw-bold">{{ $criticalParticipants->count() }}</div><div>participante(s) abaixo da meta</div></div>
                <span class="stat-icon"><i class="bi bi-exclamation-triangle"></i></span>
            </div>
            <div class="small text-secondary mt-2">Meta de {{ $minimumTestimonials }} depoimento(s) em {{ $currentEvent->name }}.</div>
        </div>
    </div>
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
                        @else --- @endif
                    </td>
                    <td class="text-end text-nowrap">
                        @if($flow->isOpen())
                            <form method="POST" action="{{ route('admin.print-flows.renew', $flow) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Novo link</button></form>
                            <form method="POST" action="{{ route('admin.print-flows.cancel', $flow) }}" class="d-inline" onsubmit="return confirm('Cancelar este fluxo e invalidar o link?');">@csrf<button class="btn btn-sm btn-outline-danger">Cancelar</button></form>
                        @else
                            <span class="text-secondary small">Histórico</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-secondary py-4">Nenhum fluxo encontrado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $flows->links() }}
</div>

<div class="card-surface p-4" id="critical-participants">
    <div class="section-eyebrow text-secondary mb-1">Participantes críticos</div>
    <h2 class="h5 mb-3">Abaixo da meta do evento</h2>
    <div class="row g-3">
        @forelse($criticalParticipants as $participant)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="border rounded-4 p-3 d-flex justify-content-between align-items-center gap-3">
                    <div><div class="fw-semibold">{{ $participant->label }}</div><div class="small text-secondary">{{ $participant->testimonials_count }} de {{ $minimumTestimonials }} depoimento(s)</div></div>
                    <a href="{{ route('admin.print-flows.create', ['participant_id' => $participant->id]) }}" class="btn btn-sm btn-outline-dark">Criar tarefa</a>
                </div>
            </div>
        @empty
            <div class="text-secondary">Nenhum participante abaixo da meta.</div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-copy-url]').forEach((button) => {
    button.addEventListener('click', async () => {
        await navigator.clipboard.writeText(button.dataset.copyUrl);
        button.innerHTML = '<i class="bi bi-check2 me-1"></i>Copiado';
    });
});
</script>
@endpush
