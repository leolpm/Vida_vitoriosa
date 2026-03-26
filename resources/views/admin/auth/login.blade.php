@extends('layouts.public')

@section('title', 'Login administrativo')

@section('content')
<div class="row justify-content-center py-4">
    <div class="col-12 col-md-8 col-lg-5">
        <div class="glass-card rounded-5 p-4 p-md-5">
            <div class="section-eyebrow mb-2">Área administrativa</div>
            <h1 class="brand-title mb-2">Entrar com e-mail</h1>
            <p class="text-secondary">Informe seu e-mail para receber um código de acesso.</p>

            <form action="{{ route('admin.login.send') }}" method="POST" class="mt-4">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">E-mail</label>
                    <input type="email" name="email" id="email" class="form-control form-control-lg @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="voce@exemplo.com">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-gold btn-lg w-100">Enviar código</button>
            </form>
        </div>
    </div>
</div>
@endsection
