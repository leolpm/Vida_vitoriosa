@extends('layouts.public')

@section('title', 'Depoimento enviado')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="glass-card rounded-5 p-4 p-md-5 text-center">
            <div class="display-1 text-success mb-3"><i class="bi bi-check-circle-fill"></i></div>
            <div class="section-eyebrow mb-2">Recebido</div>
            <h1 class="brand-title mb-3">Seu depoimento foi enviado com sucesso.</h1>
            <p class="text-secondary mb-4">Obrigado por compartilhar essa mensagem.</p>
            <a href="{{ route('testimonials.create') }}" class="btn btn-dark btn-lg">Enviar outro depoimento</a>
        </div>
    </div>
</div>
@endsection
