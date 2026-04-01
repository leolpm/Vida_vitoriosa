@extends('layouts.admin')

@section('title', 'Depoimentos')
@section('section', 'Conteúdo')
@section('page-title', 'Depoimentos')

@section('content')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<style>
    .choices {
        margin-bottom: 0;
        font-size: 1rem;
    }

    .choices__inner {
        min-height: calc(2.75rem + 2px);
        padding: .55rem .85rem;
        background: rgba(255,255,255,0.82);
        border: 1px solid rgba(16, 27, 39, 0.16);
        border-radius: .8rem;
    }

    .choices__list--dropdown,
    .choices__list[aria-expanded] {
        border-color: rgba(16, 27, 39, 0.16);
        border-radius: .8rem;
        box-shadow: 0 18px 42px rgba(16, 27, 39, 0.16);
    }

    .choices__list--dropdown .choices__item--selectable.is-highlighted {
        background: #c58f3a;
    }

    .choices__placeholder {
        opacity: 1;
        color: #6f6458;
    }
</style>
@endpush

<div class="card-surface p-4 mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label fw-semibold" for="participant_id">Participante</label>
            <select name="participant_id" id="participant_id" class="form-select">
                <option value="">Todos</option>
                @foreach ($participants as $participant)
                    <option value="{{ $participant->id }}" @selected(request('participant_id') == $participant->id)>{{ $participant->label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label fw-semibold" for="status">Status</label>
            <select name="status" id="status" class="form-select">
                <option value="">Todos</option>
                @foreach (['received' => 'Recebido', 'reviewed' => 'Revisado', 'approved' => 'Aprovado', 'archived' => 'Arquivado'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label fw-semibold" for="generated">Exportado em PDF</label>
            <select name="generated" id="generated" class="form-select">
                <option value="">Todos</option>
                <option value="1" @selected(request('generated') === '1')>Sim</option>
                <option value="0" @selected(request('generated') === '0')>Não</option>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <button class="btn btn-gold w-100" type="submit">Filtrar</button>
        </div>
    </form>
</div>

<div class="card-surface p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>Remetente</th>
                <th>Telefone</th>
                <th>Participante</th>
                <th>Relação</th>
                <th>Status</th>
                <th>PDF</th>
                <th class="text-end">Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($testimonials as $testimonial)
                <tr>
                    <td class="fw-semibold">{{ $testimonial->sender_name }}</td>
                    <td>{{ $testimonial->phone ?: '---' }}</td>
                    <td>{{ $testimonial->participant?->label }}</td>
                    <td>{{ $testimonial->relationship }}{{ $testimonial->relationship_other ? ' - '.$testimonial->relationship_other : '' }}</td>
                    <td><span class="badge {{ $testimonial->status_badge_class }}">{{ $testimonial->status_label }}</span></td>
                    <td>
                        <span class="badge {{ $testimonial->is_pdf_generated ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $testimonial->is_pdf_generated ? 'Sim' : 'Não' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.testimonials.show', $testimonial) }}" class="btn btn-sm btn-outline-dark">Ver</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-secondary">Nenhum depoimento encontrado.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $testimonials->links() }}
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const participantSelect = document.getElementById('participant_id');

    if (!participantSelect || !window.Choices) {
        return;
    }

    const choices = new window.Choices(participantSelect, {
        searchEnabled: true,
        searchPlaceholderValue: 'Digite o nome do participante',
        itemSelectText: '',
        shouldSort: false,
        placeholder: true,
        placeholderValue: 'Todos',
        searchResultLimit: 50,
        position: 'bottom',
    });

    participantSelect.addEventListener('showDropdown', () => {
        const searchInput = participantSelect.closest('.choices')?.querySelector('.choices__input');
        searchInput?.focus();
    });
});
</script>
@endpush
