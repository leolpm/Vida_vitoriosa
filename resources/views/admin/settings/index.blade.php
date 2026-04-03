@extends('layouts.admin')

@section('title', 'Configurações')
@section('section', 'Sistema')
@section('page-title', 'Configurações visuais')

@section('content')
<div class="card-surface p-4">
    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="row g-3">
        @csrf
        @method('PUT')

        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold" for="retreat_name">Nome do retiro</label>
            <input type="text" name="retreat_name" id="retreat_name" class="form-control form-control-lg @error('retreat_name') is-invalid @enderror" value="{{ old('retreat_name', $settings['retreat_name']) }}">
            @error('retreat_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold" for="retreat_location">Local do retiro</label>
            <input type="text" name="retreat_location" id="retreat_location" class="form-control form-control-lg @error('retreat_location') is-invalid @enderror" value="{{ old('retreat_location', $settings['retreat_location']) }}">
            @error('retreat_location') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold" for="retreat_year">Ano / edição</label>
            <input type="text" name="retreat_year" id="retreat_year" class="form-control form-control-lg @error('retreat_year') is-invalid @enderror" value="{{ old('retreat_year', $settings['retreat_year']) }}">
            @error('retreat_year') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold" for="login_code_expires_minutes">Expiração do código (minutos)</label>
            <input type="number" min="1" max="240" name="login_code_expires_minutes" id="login_code_expires_minutes" class="form-control form-control-lg @error('login_code_expires_minutes') is-invalid @enderror" value="{{ old('login_code_expires_minutes', $settings['login_code_expires_minutes']) }}">
            @error('login_code_expires_minutes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold" for="testimonials_closes_at">Encerramento dos depoimentos</label>
            <input
                type="datetime-local"
                name="testimonials_closes_at"
                id="testimonials_closes_at"
                class="form-control form-control-lg @error('testimonials_closes_at') is-invalid @enderror"
                value="{{ old('testimonials_closes_at', $settings['testimonials_closes_at']) }}"
            >
            <div class="form-text">
                Após este dia e horário, o formulário público será bloqueado automaticamente e mostrará uma mensagem amigável.
            </div>
            @error('testimonials_closes_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold" for="pdf_footer_text">Texto de rodapé</label>
            <input type="text" name="pdf_footer_text" id="pdf_footer_text" class="form-control form-control-lg @error('pdf_footer_text') is-invalid @enderror" value="{{ old('pdf_footer_text', $settings['pdf_footer_text']) }}">
            @error('pdf_footer_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-lg-6">
            <label class="form-label fw-semibold" for="public_site_image">Imagem do site público</label>
            <input type="file" name="public_site_image" id="public_site_image" class="form-control @error('public_site_image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/*">
            <div class="form-text">Imagem panorâmica horizontal. Ideal: 1357 x 267 px, ou proporção aproximada de 5:1.</div>
            @error('public_site_image') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            @if ($settings['public_site_image_url'])
                <div class="mt-3">
                    <img src="{{ $settings['public_site_image_url'] }}" alt="Imagem pública" class="img-fluid rounded-4 border">
                </div>
            @endif
        </div>

        <div class="col-12 col-lg-6">
            <label class="form-label fw-semibold" for="pdf_header_image">Imagem do PDF</label>
            <input type="file" name="pdf_header_image" id="pdf_header_image" class="form-control @error('pdf_header_image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/*">
            <div class="form-text">Imagem panorâmica horizontal. Ideal: 1024 x 369 px, ou proporção aproximada de 3:1.</div>
            @error('pdf_header_image') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            @if ($settings['pdf_header_image_url'])
                <div class="mt-3">
                    <img src="{{ $settings['pdf_header_image_url'] }}" alt="Imagem do PDF" class="img-fluid rounded-4 border">
                </div>
            @endif
        </div>

        <div class="col-12 d-flex gap-2">
            <button class="btn btn-gold btn-lg" type="submit">Salvar configurações</button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-dark btn-lg">Voltar</a>
        </div>
    </form>
</div>

<div class="card-surface border border-danger-subtle bg-danger-subtle bg-opacity-10 p-4 mt-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div class="pe-lg-3">
            <div class="section-eyebrow text-danger mb-2">Zona de risco</div>
            <h2 class="h5 mb-2">Resetar sistema</h2>
            <p class="mb-0 text-secondary">
                Apaga todos os participantes, depoimentos, PDFs gerados e imagens dos depoimentos.
                Essa ação não pode ser revertida.
            </p>
        </div>
        <button class="btn btn-outline-danger btn-lg" type="button" data-bs-toggle="modal" data-bs-target="#resetSystemModal">
            <i class="bi bi-trash3 me-2"></i>
            Resetar sistema
        </button>
    </div>
</div>

<div class="modal fade" id="resetSystemModal" tabindex="-1" aria-labelledby="resetSystemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <div>
                    <h5 class="modal-title mb-1" id="resetSystemModalLabel">Confirmar reset do sistema</h5>
                    <small class="text-white-50">Ação irreversível</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <form action="{{ route('admin.settings.reset') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger border-0 mb-3">
                        Atenção: ao continuar, todos os participantes, depoimentos, PDFs gerados e imagens dos depoimentos serão apagados sem possibilidade de recuperação.
                    </div>

                    <label for="systemResetConfirmation" class="form-label fw-semibold">Digite RESETAR para confirmar</label>
                    <input
                        type="text"
                        name="confirmation"
                        id="systemResetConfirmation"
                        class="form-control form-control-lg @error('confirmation') is-invalid @enderror"
                        placeholder="RESETAR"
                        autocomplete="off"
                        inputmode="text"
                    >
                    @error('confirmation')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror

                    <div class="form-text mt-2">
                        O botão de confirmação só será habilitado quando a palavra estiver exatamente correta.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="systemResetSubmit" disabled>Apagar tudo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('resetSystemModal');
    const input = document.getElementById('systemResetConfirmation');
    const submit = document.getElementById('systemResetSubmit');

    if (!input || !submit) {
        return;
    }

    const sync = () => {
        submit.disabled = input.value.trim() !== 'RESETAR';
    };

    input.addEventListener('input', sync);
    sync();

    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', () => {
            input.value = '';
            sync();
        });
    }

    @if ($errors->has('confirmation'))
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    @endif
});
</script>
@endpush
