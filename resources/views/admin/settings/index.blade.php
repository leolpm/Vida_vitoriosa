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
@endsection
