@extends('layouts.admin')

@section('title', $member->exists ? 'Editar membro' : 'Novo membro')
@section('section', 'Equipe')
@section('page-title', $member->exists ? 'Editar membro da equipe' : 'Cadastrar membro da equipe')

@section('content')
<div class="card-surface p-4">
    <form method="POST" action="{{ $member->exists ? route('admin.team.update', $member) : route('admin.team.store') }}" class="row g-3">
        @csrf
        @if ($member->exists) @method('PUT') @endif
        <div class="col-12 col-lg-6">
            <label for="name" class="form-label fw-semibold">Nome</label>
            <input id="name" name="name" class="form-control form-control-lg @error('name') is-invalid @enderror" value="{{ old('name', $member->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-lg-6">
            <label for="phone" class="form-label fw-semibold">Telefone com DDI</label>
            <input id="phone" name="phone" class="form-control form-control-lg @error('phone') is-invalid @enderror" value="{{ old('phone', $member->phone) }}" placeholder="+55 21 99999-9999" required>
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-6">
            <label for="status" class="form-label fw-semibold">Status</label>
            <select id="status" name="status" class="form-select form-select-lg @error('status') is-invalid @enderror">
                <option value="active" @selected(old('status', $member->status) === 'active')>Ativo</option>
                <option value="inactive" @selected(old('status', $member->status) === 'inactive')>Inativo</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-6">
            <label for="task_limit" class="form-label fw-semibold">Limite individual de tarefas</label>
            <input type="number" min="1" max="100" id="task_limit" name="task_limit" class="form-control form-control-lg @error('task_limit') is-invalid @enderror" value="{{ old('task_limit', $member->task_limit) }}" placeholder="Usar limite global">
            @error('task_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <fieldset class="border rounded-4 p-3">
                <legend class="float-none w-auto px-2 fs-6 fw-semibold">Eventos autorizados</legend>
                <div class="d-flex flex-wrap gap-4">
                    @foreach ($events as $event)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="event_ids[]" value="{{ $event->id }}" id="event_{{ $event->id }}" @checked(in_array($event->id, old('event_ids', $selectedEvents->all())))>
                            <label class="form-check-label" for="event_{{ $event->id }}">{{ $event->name }}</label>
                        </div>
                    @endforeach
                </div>
                @error('event_ids')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </fieldset>
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-gold btn-lg" data-loading-text="Salvando...">Salvar membro</button>
            <a href="{{ route('admin.team.index') }}" class="btn btn-outline-dark btn-lg">Voltar</a>
        </div>
    </form>
</div>
@endsection
