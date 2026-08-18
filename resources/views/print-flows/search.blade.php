@extends('layouts.print-flow')
@section('title', 'Busca de depoimentos')
@section('content')
<div class="flow-steps mb-4">
    <div class="flow-step active">1. Buscar depoimentos</div><div class="flow-step">2. Confirmar</div><div class="flow-step">3. Concluído</div>
</div>
<div class="flow-card">
    <div class="flow-eyebrow mb-2">Participante crítico</div>
    <h1 class="flow-title h3">{{ $flow->participant->label }}</h1>
    <p class="text-secondary">Entre em contato com pessoas próximas e ajude a equipe a conseguir novos depoimentos para este participante.</p>
    <form method="POST" action="{{ route('print-flows.search.complete', $token) }}">
        @csrf
        <div class="form-check border rounded-4 p-3 ps-5 mb-3">
            <input class="form-check-input" type="checkbox" name="search_confirmation" value="1" id="search_confirmation" required>
            <label class="form-check-label fw-semibold" for="search_confirmation">Concluí a busca de depoimentos e informei a equipe.</label>
        </div>
        <button class="btn btn-flow btn-lg">Concluir tarefa</button>
    </form>
</div>
@endsection
