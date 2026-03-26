@extends('layouts.public')

@section('title', 'Verificar código')

@section('content')
<div class="row justify-content-center py-4">
    <div class="col-12 col-md-8 col-lg-5">
        <div class="glass-card rounded-5 p-4 p-md-5">
            <div class="section-eyebrow mb-2">Confirmação de acesso</div>
            <h1 class="brand-title mb-2">Digite o código enviado</h1>
            <p class="text-secondary">Enviamos um código de 6 dígitos para <strong>{{ $email }}</strong>.</p>

            <form action="{{ route('admin.login.verify.submit') }}" method="POST" class="mt-4">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <div class="mb-3">
                    <label for="code" class="form-label fw-semibold">Código de acesso</label>
                    <input type="text" name="code" id="code" inputmode="numeric" maxlength="6" class="form-control form-control-lg text-center @error('code') is-invalid @enderror" style="letter-spacing: .35em;" value="{{ old('code') }}" placeholder="000000">
                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-gold btn-lg w-100">Verificar código</button>
                <a href="{{ route('admin.login') }}" class="btn btn-link w-100 mt-2">Usar outro e-mail</a>
            </form>
        </div>
    </div>
</div>
@endsection
