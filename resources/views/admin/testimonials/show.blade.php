@extends('layouts.admin')

@section('title', 'Depoimento')
@section('section', 'Conteúdo')
@section('page-title', 'Depoimento')

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card-surface p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <div class="section-eyebrow text-secondary mb-1">Detalhes</div>
                    <h2 class="h4 mb-1">{{ $testimonial->sender_name }}</h2>
                    <div class="text-secondary">{{ $testimonial->participant?->label }}</div>
                </div>
                <div class="text-end">
                    <div class="badge {{ $testimonial->is_pdf_generated ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ $testimonial->is_pdf_generated ? 'Exportado em PDF' : 'Ainda não exportado' }}
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <div class="fw-semibold mb-2">Mensagem</div>
                <div class="p-3 rounded-4 bg-light">{{ $testimonial->message }}</div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="fw-semibold">Relação</div>
                    <div>{{ $testimonial->relationship }}{{ $testimonial->relationship_other ? ' - '.$testimonial->relationship_other : '' }}</div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="fw-semibold">Telefone</div>
                    <div>{{ $testimonial->phone ?: '---' }}</div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="fw-semibold">Status atual</div>
                    <div>
                        <span class="badge {{ $testimonial->status_badge_class }}">{{ $testimonial->status_label }}</span>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="fw-semibold">Criado em</div>
                    <div>{{ $testimonial->created_at?->format('d/m/Y H:i') }}</div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="fw-semibold">Último PDF</div>
                    <div>{{ $testimonial->pdf_generated_at?->format('d/m/Y H:i') ?: '---' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card-surface p-4 mb-4">
            <div class="section-eyebrow text-secondary mb-1">Gestão</div>
            <h2 class="h5 mb-3">Atualizar status</h2>
            <form action="{{ route('admin.testimonials.update', $testimonial) }}" method="POST">
                @csrf
                @method('PATCH')
                <select name="status" class="form-select mb-3">
                    @foreach (\App\Models\Testimonial::STATUS_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected($testimonial->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-gold w-100" type="submit">Salvar</button>
            </form>
        </div>

        <div class="card-surface p-4 mb-4">
            <div class="section-eyebrow text-secondary mb-1">Foto</div>
            <h2 class="h5 mb-3">Arquivo enviado</h2>
            @if ($testimonial->photo_path)
                <img src="{{ $testimonial->photo_url }}" alt="Foto do depoimento" class="img-fluid rounded-4 mb-3">
                <a href="{{ route('admin.testimonials.photo', $testimonial) }}" class="btn btn-outline-dark w-100">Baixar foto</a>
            @else
                <p class="text-secondary mb-0">Nenhuma foto foi enviada.</p>
            @endif
        </div>

        <div class="card-surface p-4">
            <div class="section-eyebrow text-secondary mb-1">PDF</div>
            <h2 class="h5 mb-3">Lote vinculado</h2>
            <div class="text-secondary">
                {{ $testimonial->pdfBatch?->file_path ? basename($testimonial->pdfBatch->file_path) : 'Nenhum lote vinculado' }}
            </div>
        </div>
    </div>
</div>
@endsection
