@extends('layouts.admin')

@section('title', $participant->exists ? 'Editar participante' : 'Novo participante')
@section('section', 'Cadastro')
@section('page-title', $participant->exists ? 'Editar participante' : 'Novo participante')

@section('content')
<div class="card-surface p-4">
    <form action="{{ $participant->exists ? route('admin.participants.update', $participant) : route('admin.participants.store') }}" method="POST" class="row g-3">
        @csrf
        @if ($participant->exists)
            @method('PUT')
        @endif

        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold" for="name">Nome</label>
            <input type="text" name="name" id="name" class="form-control form-control-lg @error('name') is-invalid @enderror" value="{{ old('name', $participant->name) }}">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold" for="display_name">Nome de exibição</label>
            <input type="text" name="display_name" id="display_name" class="form-control form-control-lg @error('display_name') is-invalid @enderror" value="{{ old('display_name', $participant->display_name) }}">
            @error('display_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold" for="status">Status</label>
            <select name="status" id="status" class="form-select form-select-lg @error('status') is-invalid @enderror">
                <option value="active" @selected(old('status', $participant->status ?: 'active') === 'active')>Ativo</option>
                <option value="inactive" @selected(old('status', $participant->status) === 'inactive')>Inativo</option>
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-8">
            <label class="form-label fw-semibold" for="retreat_edition">Edição do retiro</label>
            <input type="text" name="retreat_edition" id="retreat_edition" class="form-control form-control-lg @error('retreat_edition') is-invalid @enderror" value="{{ old('retreat_edition', $participant->retreat_edition) }}">
            @error('retreat_edition') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 d-flex gap-2">
            <button class="btn btn-gold btn-lg" type="submit">Salvar</button>
            <a href="{{ route('admin.participants.index') }}" class="btn btn-outline-dark btn-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection
