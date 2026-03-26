@extends('layouts.admin')

@section('title', $user->exists ? 'Editar usuário' : 'Novo usuário')
@section('section', 'Acesso')
@section('page-title', $user->exists ? 'Editar usuário' : 'Novo usuário')

@section('content')
<div class="card-surface p-4">
    <form action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" method="POST" class="row g-3">
        @csrf
        @if ($user->exists)
            @method('PUT')
        @endif

        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold" for="name">Nome</label>
            <input type="text" name="name" id="name" class="form-control form-control-lg @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold" for="email">E-mail</label>
            <input type="email" name="email" id="email" class="form-control form-control-lg @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold" for="is_active">Status</label>
            @php($selectedIsActive = old('is_active', $user->exists ? ($user->is_active ? '1' : '0') : '1'))
            <select name="is_active" id="is_active" class="form-select form-select-lg @error('is_active') is-invalid @enderror">
                <option value="1" @selected($selectedIsActive === '1')>Ativo</option>
                <option value="0" @selected($selectedIsActive === '0')>Inativo</option>
            </select>
            @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 d-flex gap-2">
            <button class="btn btn-gold btn-lg" type="submit">Salvar</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-dark btn-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection
