@extends('layouts.admin')

@section('title', 'Distribuir fluxo')
@section('section', 'Fluxo de Impressão')
@section('page-title', 'Distribuir nova tarefa')

@section('content')
<div class="card-surface p-4">
    <div class="alert alert-primary border-0 rounded-4">
        O fluxo será criado para <strong>{{ $currentEvent->name }}</strong>. O membro receberá um link temporário pelo WhatsApp.
    </div>
    <form method="POST" action="{{ route('admin.print-flows.store') }}" class="row g-3">
        @csrf
        <div class="col-12 col-lg-4">
            <label class="form-label fw-semibold" for="type">Tipo da tarefa</label>
            <select class="form-select form-select-lg @error('type') is-invalid @enderror" id="type" name="type">
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', 'main_print') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-lg-4">
            <label class="form-label fw-semibold" for="participant_id">Participante</label>
            <select class="form-select form-select-lg @error('participant_id') is-invalid @enderror" id="participant_id" name="participant_id" required>
                <option value="">Selecione...</option>
                @foreach ($participants as $participant)
                    <option value="{{ $participant->id }}" @selected((string) old('participant_id', request('participant_id')) === (string) $participant->id)>{{ $participant->label }} · {{ $participant->testimonials_count }} carta(s)</option>
                @endforeach
            </select>
            @error('participant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-lg-4">
            <label class="form-label fw-semibold" for="team_member_id">Membro responsável</label>
            <select class="form-select form-select-lg @error('team_member_id') is-invalid @enderror" id="team_member_id" name="team_member_id" required>
                <option value="">Selecione...</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" @selected((string) old('team_member_id') === (string) $member->id)>{{ $member->name }} · {{ $member->open_tasks_count }} aberta(s)</option>
                @endforeach
            </select>
            @error('team_member_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 d-flex gap-2 mt-4">
            <button class="btn btn-gold btn-lg"><i class="bi bi-send me-1"></i>Distribuir fluxo</button>
            <a href="{{ route('admin.print-flows.index') }}" class="btn btn-outline-dark btn-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection
