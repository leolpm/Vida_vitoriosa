@extends('layouts.admin')

@section('title', 'Compartilhar fluxo')
@section('section', 'Fluxo de Impressão')
@section('page-title', 'Compartilhar tarefa')

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card-surface p-4 h-100">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-start mb-4">
                <div>
                    <div class="section-eyebrow text-success mb-1">Tarefa registrada</div>
                    <h2 class="h3 mb-1">{{ $flow->participant->label }}</h2>
                    <div class="text-secondary">{{ $flow->type_label }} · {{ $currentEvent->name }}</div>
                </div>
                <span class="badge {{ $flow->isOpen() ? 'text-bg-primary' : ($flow->status === 'completed' ? 'text-bg-success' : 'text-bg-secondary') }} rounded-pill px-3 py-2">{{ $flow->status_label }}</span>
            </div>

            @if($share)
                <div class="share-link-panel p-4 rounded-4 mb-4">
                    <div class="section-eyebrow text-success mb-2">Link temporário disponível</div>
                    <div class="form-control form-control-lg bg-white text-break mb-3" id="share-url">{{ $share['access_url'] }}</div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-dark btn-lg" type="button" data-copy-url="{{ $share['access_url'] }}"><i class="bi bi-copy me-1"></i>Copiar link</button>
                        <a class="btn btn-success btn-lg" href="{{ $share['whatsapp_url'] }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp me-1"></i>Abrir WhatsApp Web</a>
                    </div>
                    <div class="small text-secondary mt-3">Válido até {{ $share['expires_at'] }} · {{ $share['max_accesses'] }} acesso(s) permitido(s).</div>
                </div>
            @else
                <div class="alert alert-warning border-0 rounded-4 p-4 mb-4">
                    <div class="d-flex gap-3 align-items-start">
                        <i class="bi bi-shield-lock fs-3"></i>
                        <div>
                            <div class="fw-bold">O link original não está mais disponível</div>
                            <div>Por segurança, o token é mostrado apenas imediatamente após a criação ou renovação. Gere um novo link se precisar compartilhar novamente.</div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="d-flex flex-wrap gap-2">
                @if($flow->isOpen())
                    <form method="POST" action="{{ route('admin.print-flows.renew', $flow) }}">
                        @csrf
                        <button class="btn btn-gold btn-lg"><i class="bi bi-arrow-clockwise me-1"></i>Gerar novo link</button>
                    </form>
                @endif
                <a href="{{ route('admin.print-flows.index') }}" class="btn btn-outline-dark btn-lg">Voltar para os fluxos</a>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card-surface p-4">
            <div class="section-eyebrow text-secondary mb-1">Resumo da tarefa</div>
            <h2 class="h5 mb-4">Informações do fluxo</h2>
            <dl class="row g-3 mb-0">
                <dt class="col-5 text-secondary fw-normal">Evento</dt><dd class="col-7 fw-semibold text-end">{{ $currentEvent->name }}</dd>
                <dt class="col-5 text-secondary fw-normal">Participante</dt><dd class="col-7 fw-semibold text-end">{{ $flow->participant->label }}</dd>
                <dt class="col-5 text-secondary fw-normal">Responsável</dt><dd class="col-7 fw-semibold text-end">{{ $flow->teamMember->name }}</dd>
                <dt class="col-5 text-secondary fw-normal">Tipo</dt><dd class="col-7 fw-semibold text-end">{{ $flow->type_label }}</dd>
                <dt class="col-5 text-secondary fw-normal">Cartas</dt><dd class="col-7 fw-semibold text-end">{{ $flow->testimonials->count() }}</dd>
                <dt class="col-5 text-secondary fw-normal">Distribuído</dt><dd class="col-7 fw-semibold text-end">{{ $flow->distributed_at->format('d/m/Y H:i') }}</dd>
                <dt class="col-5 text-secondary fw-normal">Status</dt><dd class="col-7 fw-semibold text-end">{{ $flow->status_label }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .share-link-panel {
        background: linear-gradient(135deg, rgba(25, 135, 84, .10), rgba(25, 135, 84, .03));
        border: 1px solid rgba(25, 135, 84, .18);
    }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('[data-copy-url]').forEach((button) => {
    button.addEventListener('click', async () => {
        await navigator.clipboard.writeText(button.dataset.copyUrl);
        button.innerHTML = '<i class="bi bi-check2 me-1"></i>Link copiado';
    });
});
</script>
@endpush
