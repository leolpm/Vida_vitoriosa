@extends('layouts.print-flow')
@section('title', 'Revisar cartas')

@section('content')
@php
    $step = $flow->current_step;
    $reviewDone = in_array($step, ['print', 'complete'], true);
    $printDone = $step === 'complete';
@endphp
<div class="flow-steps mb-4">
    <div class="flow-step {{ $step === 'review' ? 'active' : 'done' }}">1. Revisar cartas</div>
    <div class="flow-step {{ $step === 'print' ? 'active' : ($printDone ? 'done' : '') }}">2. Imprimir lote</div>
    <div class="flow-step {{ $step === 'complete' ? 'active' : '' }}">3. Confirmar conclusão</div>
</div>

<div class="flow-card mb-4 d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
    <div>
        <div class="flow-eyebrow mb-1">{{ $flow->type_label }}</div>
        <h1 class="flow-title h3 mb-1">{{ $flow->participant->label }}</h1>
        <div class="text-secondary">Responsável: {{ $flow->teamMember->name }} · {{ $flow->testimonials->count() }} carta(s)</div>
    </div>
    @if($reviewDone)
        <a href="{{ route('print-flows.print', $token) }}" class="btn btn-flow btn-lg" target="_blank" rel="noopener"><i class="bi bi-printer me-1"></i>Abrir impressão</a>
    @endif
</div>

@if($step === 'review')
    <div class="d-grid gap-4">
        @foreach($flow->testimonials as $testimonial)
            @php
                $review = $latestReviews->get($testimonial->id);
                $history = $reviewHistory->get($testimonial->id, collect());
                $previousHistory = $history->where('print_flow_id', '!=', $flow->id);
                $reevaluationCount = $previousHistory->filter(fn ($item) => $item->printFlow?->type === 'reevaluation')->count();
            @endphp
            <article class="flow-card">
                <div class="row g-4">
                    <div class="{{ $testimonial->photo_url ? 'col-12 col-lg-8' : 'col-12' }}">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div><div class="flow-eyebrow">Carta de</div><h2 class="h5 mb-0">{{ $testimonial->sender_name }}</h2><div class="small text-secondary">{{ $testimonial->relationship === 'Outro' ? $testimonial->relationship_other : $testimonial->relationship }}</div></div>
                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                @if($review)<span class="badge {{ $review->decision === 'approved' ? 'text-bg-success' : 'text-bg-danger' }}">{{ $review->decision === 'approved' ? 'Aprovada' : 'Reprovada' }}</span>@endif
                                @if($reevaluationCount > 0)<span class="badge text-bg-warning">Já reavaliada {{ $reevaluationCount }} vez(es)</span>@elseif($previousHistory->isNotEmpty())<span class="badge text-bg-secondary">Reprovada · aguardando reavaliação</span>@endif
                            </div>
                        </div>
                        <div class="p-3 rounded-4 bg-light" style="white-space:pre-wrap">{{ $testimonial->message }}</div>
                        @if($previousHistory->isNotEmpty())
                            <details class="mt-3 border rounded-4 p-3 bg-light">
                                <summary class="fw-semibold">Histórico anterior · {{ $previousHistory->count() }} revisão(ões)</summary>
                                <div class="d-grid gap-2 mt-3">
                                    @foreach($previousHistory as $historyItem)
                                        <div class="border-bottom pb-2">
                                            <div class="fw-semibold">{{ $historyItem->decision === 'approved' ? 'Aprovada' : 'Reprovada' }} por {{ $historyItem->teamMember?->name ?? 'Membro não identificado' }}</div>
                                            <div class="small text-secondary">{{ $historyItem->printFlow?->type_label ?? 'Fluxo não identificado' }} · {{ $historyItem->decided_at->format('d/m/Y H:i') }}</div>
                                            @if($historyItem->rejection_reason)<div class="small mt-1">Motivo: {{ $historyItem->rejection_reason }}</div>@endif
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>
                    @if($testimonial->photo_url)
                        <div class="col-12 col-lg-4"><img src="{{ $testimonial->photo_url }}" class="img-fluid rounded-4 border w-100" style="max-height:420px;object-fit:contain" alt="Foto enviada por {{ $testimonial->sender_name }}"></div>
                    @endif
                </div>
                <form method="POST" action="{{ route('print-flows.review', [$token, $testimonial]) }}" class="mt-4 border-top pt-3" data-review-form>
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Decisão</label>
                            <select name="decision" class="form-select" data-decision required>
                                <option value="approved" @selected($review?->decision === 'approved')>Aprovar carta</option>
                                <option value="rejected" @selected($review?->decision === 'rejected')>Reprovar carta</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6" data-reason-wrap>
                            <label class="form-label fw-semibold">Motivo da reprovação</label>
                            <input name="rejection_reason" class="form-control" value="{{ $review?->rejection_reason }}" maxlength="1000" data-reason>
                        </div>
                        <div class="col-12 col-md-2"><button class="btn btn-outline-dark w-100">Salvar</button></div>
                    </div>
                </form>
            </article>
        @endforeach
    </div>
    <form method="POST" action="{{ route('print-flows.review.finish', $token) }}" class="flow-card mt-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        @csrf
        <div><div class="fw-bold">Concluir a revisão</div><div class="text-secondary small">Todas as cartas precisam ter uma decisão registrada.</div></div>
        <button class="btn btn-flow btn-lg">Avançar para impressão</button>
    </form>
@else
    <div class="flow-card">
        <div class="flow-eyebrow mb-2">Próxima ação</div>
        <h2 class="flow-title h4">Imprima o lote e confirme a conclusão</h2>
        <p class="text-secondary">A abertura da janela de impressão não conclui automaticamente a tarefa.</p>
        <a href="{{ route('print-flows.print', $token) }}" class="btn btn-flow btn-lg" target="_blank" rel="noopener"><i class="bi bi-printer me-1"></i>Abrir impressão</a>
        <form method="POST" action="{{ route('print-flows.complete', $token) }}" class="mt-4 pt-4 border-top">
            @csrf
            <div class="form-check border rounded-4 p-3 ps-5 mb-3">
                <input class="form-check-input" type="checkbox" name="printed_confirmation" value="1" id="printed_confirmation" required>
                <label class="form-check-label fw-semibold" for="printed_confirmation">Confirmo que o lote foi impresso e a tarefa foi concluída.</label>
            </div>
            <button class="btn btn-success btn-lg">Confirmar conclusão</button>
        </form>
    </div>
@endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-review-form]').forEach((form) => {
    const decision = form.querySelector('[data-decision]');
    const reasonWrap = form.querySelector('[data-reason-wrap]');
    const reason = form.querySelector('[data-reason]');
    const sync = () => {
        const rejected = decision.value === 'rejected';
        reasonWrap.classList.toggle('d-none', !rejected);
        reason.required = rejected;
    };
    decision.addEventListener('change', sync);
    sync();
});
</script>
@endpush
