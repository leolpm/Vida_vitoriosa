@extends('layouts.print-flow')
@section('title', 'Tarefa concluída')
@section('content')
<div class="flow-card text-center py-5">
    <div class="flow-mark mx-auto mb-3"><i class="bi bi-check2-circle"></i></div>
    <div class="flow-eyebrow mb-2">Fluxo concluído</div>
    <h1 class="flow-title h3">Obrigado, {{ $flow->teamMember->name }}</h1>
    <p class="text-secondary mb-0">A tarefa de <strong>{{ $flow->participant->label }}</strong> foi concluída em {{ $flow->completed_at?->format('d/m/Y H:i') }}.</p>
</div>
@endsection
