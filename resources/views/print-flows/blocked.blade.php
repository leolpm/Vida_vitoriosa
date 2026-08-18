@extends('layouts.print-flow')
@section('title', 'Acesso indisponível')
@section('content')
<div class="flow-card text-center py-5">
    <div class="flow-mark mx-auto mb-3"><i class="bi bi-link-45deg"></i></div>
    <div class="flow-eyebrow mb-2">Acesso indisponível</div>
    <h1 class="flow-title h3">Não foi possível abrir esta tarefa</h1>
    <p class="text-secondary mb-0 mx-auto" style="max-width:620px">{{ $message }}</p>
</div>
@endsection
