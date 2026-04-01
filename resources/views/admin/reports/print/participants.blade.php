@extends('layouts.print')

@section('title', 'Impressão - Relatório de participantes')

@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 mb-3 no-print">
    <div>
        <div class="print-title">Relatório de participantes</div>
        <div class="print-subtitle">Use a janela do navegador para imprimir ou salvar como PDF.</div>
    </div>
    <a href="javascript:window.close()" class="btn btn-outline-dark btn-sm">Fechar</a>
</div>

<div class="print-meta">
    <span class="badge text-bg-light border text-dark">{{ $filterLabel }}</span>
    <span class="badge text-bg-secondary">{{ $participants->count() }} participante(s)</span>
</div>

<table class="table table-sm table-bordered align-middle">
    <thead class="table-light">
    <tr>
        <th>Nome</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($participants as $participant)
        <tr>
            <td>{{ $participant->name }}</td>
        </tr>
    @empty
        <tr>
            <td class="text-secondary">Nenhum participante encontrado.</td>
        </tr>
    @endforelse
    </tbody>
</table>
@endsection
