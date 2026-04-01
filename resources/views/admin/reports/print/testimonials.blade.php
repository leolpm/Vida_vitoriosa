@extends('layouts.print')

@section('title', 'Impressão - Relatório de depoimentos')

@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 mb-3 no-print">
    <div>
        <div class="print-title">Relatório de depoimentos</div>
        <div class="print-subtitle">Use a janela do navegador para imprimir ou salvar como PDF.</div>
    </div>
    <a href="javascript:window.close()" class="btn btn-outline-dark btn-sm">Fechar</a>
</div>

<div class="print-meta">
    <span class="badge text-bg-light border text-dark">{{ $statusLabel }}</span>
    <span class="badge text-bg-light border text-dark">{{ $generatedLabel }}</span>
    <span class="badge text-bg-secondary">{{ $testimonials->count() }} depoimento(s)</span>
</div>

<table class="table table-sm table-bordered align-middle">
    <thead class="table-light">
    <tr>
        <th>Nome</th>
        <th>Telefone</th>
        <th>Participante</th>
        <th>Status</th>
        <th>PDF gerado</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($testimonials as $testimonial)
        <tr>
            <td>{{ $testimonial->sender_name }}</td>
            <td>{{ $testimonial->phone ?: '---' }}</td>
            <td>{{ $testimonial->participant?->label ?: '---' }}</td>
            <td>{{ $testimonial->status_label }}</td>
            <td>{{ $testimonial->is_pdf_generated ? 'Sim' : 'Não' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="text-secondary">Nenhum depoimento encontrado.</td>
        </tr>
    @endforelse
    </tbody>
</table>
@endsection
