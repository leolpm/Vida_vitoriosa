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
                    <textarea class="form-control form-control-lg bg-white share-url mb-3" id="share-url" rows="2" readonly spellcheck="false">{{ $share['access_url'] }}</textarea>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-dark btn-lg" type="button" data-copy-url="{{ $share['access_url'] }}" data-copy-target="#share-url" aria-describedby="copy-link-feedback"><i class="bi bi-copy me-1"></i>Copiar link</button>
                        <a class="btn btn-success btn-lg" href="{{ $share['whatsapp_url'] }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp me-1"></i>Abrir WhatsApp Web</a>
                    </div>
                    <div class="small mt-2" id="copy-link-feedback" role="status" aria-live="polite"></div>
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
                        <button class="btn btn-gold btn-lg" data-loading-text="Gerando link..."><i class="bi bi-arrow-clockwise me-1"></i>Gerar novo link</button>
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

    .share-url {
        min-height: 5rem;
        resize: none;
        overflow-wrap: anywhere;
    }
</style>
@endpush

@push('scripts')
<script>
const copyText = async (text, target) => {
    if (window.isSecureContext && navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(text);

            return true;
        } catch (error) {
            // Browsers can still deny the Clipboard API; the selected field is the local fallback.
        }
    }

    target.focus();
    target.select();
    target.setSelectionRange(0, target.value.length);

    let copyEventHandled = false;
    const handleCopy = (event) => {
        if (! event.clipboardData) {
            return;
        }

        event.clipboardData.setData('text/plain', text);
        event.preventDefault();
        copyEventHandled = true;
    };

    document.addEventListener('copy', handleCopy);

    try {
        return document.execCommand('copy') && copyEventHandled;
    } finally {
        document.removeEventListener('copy', handleCopy);
    }
};

document.querySelectorAll('[data-copy-url]').forEach((button) => {
    button.addEventListener('click', async () => {
        const target = document.querySelector(button.dataset.copyTarget);
        const feedback = document.getElementById('copy-link-feedback');
        const originalHtml = button.innerHTML;

        if (!(target instanceof HTMLTextAreaElement)) {
            return;
        }

        window.clearTimeout(button.copyResetTimeout);
        button.disabled = true;

        try {
            const copied = await copyText(button.dataset.copyUrl, target);

            if (! copied) {
                throw new Error('O navegador recusou a cópia.');
            }

            button.innerHTML = '<i class="bi bi-check2 me-1"></i>Link copiado';
            button.classList.add('btn-success');
            button.classList.remove('btn-outline-dark');
            feedback.textContent = 'Link copiado para a área de transferência.';
            feedback.className = 'small mt-2 text-success';
        } catch (error) {
            target.focus();
            target.select();
            button.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Copie manualmente';
            button.classList.add('btn-danger');
            button.classList.remove('btn-outline-dark');
            feedback.textContent = 'Não foi possível copiar automaticamente. O link foi selecionado; pressione Ctrl+C.';
            feedback.className = 'small mt-2 text-danger';
        } finally {
            button.disabled = false;

            button.copyResetTimeout = window.setTimeout(() => {
                button.innerHTML = originalHtml;
                button.classList.remove('btn-success', 'btn-danger');
                button.classList.add('btn-outline-dark');
                feedback.textContent = '';
                feedback.className = 'small mt-2';
            }, 4500);
        }
    });
});
</script>
@endpush
