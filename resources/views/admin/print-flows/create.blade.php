@extends('layouts.admin')

@section('title', 'Distribuir fluxo')
@section('section', 'Fluxo de Impressão')
@section('page-title', 'Distribuir nova tarefa')

@section('content')
<form method="POST" action="{{ route('admin.print-flows.store') }}" id="distribution-form" data-options-url="{{ route('admin.print-flows.distribution-options') }}">
    @csrf

    <div class="card-surface p-4 mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-4">
            <div>
                <div class="section-eyebrow text-primary mb-1">1. Defina a tarefa</div>
                <h2 class="h4 mb-1">O que precisa ser feito?</h2>
                <p class="text-secondary mb-0">O fluxo será criado para <strong>{{ $currentEvent->name }}</strong>.</p>
            </div>
            <span class="badge text-bg-primary rounded-pill px-3 py-2"><i class="bi bi-shield-check me-1"></i>Dados isolados por evento</span>
        </div>

        <label class="form-label fw-semibold" for="type">Tipo da tarefa</label>
        <select class="form-select form-select-lg @error('type') is-invalid @enderror" id="type" name="type">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $initialType) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror

        <div class="form-check form-switch mt-3 {{ old('type', $initialType) === 'reevaluation' ? '' : 'd-none' }}" id="include-reevaluated-wrap">
            <input class="form-check-input" type="checkbox" role="switch" id="include_reevaluated" name="include_reevaluated" value="1" @checked(old('include_reevaluated', $includeReevaluated))>
            <label class="form-check-label" for="include_reevaluated">
                <strong>Incluir cartas já reavaliadas e ainda reprovadas</strong>
                <span class="d-block small text-secondary">Use para criar manualmente uma nova rodada de reavaliação.</span>
            </label>
        </div>
    </div>

    <div class="card-surface p-4 mb-4" id="candidate-section">
        <div class="section-eyebrow text-secondary mb-1">2. Escolha o participante</div>
        <h2 class="h4 mb-3" id="candidate-title">Participantes elegíveis</h2>

        <div class="candidate-loading alert alert-light border d-none" id="candidate-loading">
            <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Atualizando participantes e cartas...
        </div>
        <div class="alert alert-danger d-none" id="candidate-error" role="alert"></div>

        <div id="candidate-controls">
            <label class="form-label fw-semibold" for="participant-search">Pesquisar participante</label>
            <div class="input-group mb-3">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input class="form-control form-control-lg" id="participant-search" type="search" placeholder="Digite o nome do participante" autocomplete="off">
            </div>

            <label class="form-label fw-semibold" for="participant_id">Participante</label>
            <select class="form-select form-select-lg @error('participant_id') is-invalid @enderror" id="participant_id" name="participant_id" required>
                <option value="">Selecione...</option>
            </select>
            @error('participant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text" id="participant-help"></div>
        </div>

        <div class="alert alert-warning border-0 rounded-4 mt-3 d-none" id="candidate-empty"></div>

        <section class="mt-4 d-none" id="letters-section" aria-labelledby="letters-title">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center mb-3">
                <div>
                    <div class="section-eyebrow text-secondary mb-1">Cartas relacionadas</div>
                    <h3 class="h5 mb-0" id="letters-title">Selecione as cartas desta tarefa</h3>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-dark" type="button" id="select-all-letters">Selecionar todas</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="clear-all-letters">Limpar</button>
                </div>
            </div>
            <div class="d-grid gap-3" id="letters-list"></div>
            @error('testimonial_ids')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            @error('testimonial_ids.*')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        </section>
    </div>

    <div class="card-surface p-4 mb-4">
        <div class="section-eyebrow text-secondary mb-1">3. Defina o responsável</div>
        <h2 class="h4 mb-3">Membro disponível</h2>

        <label class="form-label fw-semibold" for="team_member_id">Membro responsável</label>
        <select class="form-select form-select-lg @error('team_member_id') is-invalid @enderror" id="team_member_id" name="team_member_id" required>
            <option value="">Selecione...</option>
        </select>
        @error('team_member_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Membros que atingiram o limite de tarefas não aparecem nesta relação.</div>
        <div class="alert alert-warning border-0 rounded-4 mt-3 d-none" id="member-empty">
            Nenhum membro possui vaga disponível neste momento. Revise os limites ou conclua tarefas abertas na área de Equipe.
        </div>
    </div>

    <div class="card-surface p-4 distribution-summary">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4 align-items-xl-center">
            <div>
                <div class="section-eyebrow text-secondary mb-1">Resumo</div>
                <div class="fw-semibold" id="distribution-summary-text">Escolha o participante e o membro responsável.</div>
                <div class="small text-secondary">O link temporário será exibido na próxima tela.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-gold btn-lg" id="submit-distribution" disabled><i class="bi bi-send me-1"></i>Distribuir fluxo</button>
                <a href="{{ route('admin.print-flows.index') }}" class="btn btn-outline-dark btn-lg">Cancelar</a>
            </div>
        </div>
    </div>
</form>
@endsection

@push('styles')
<style>
    .letter-option {
        border: 1px solid rgba(16, 27, 39, .12);
        border-radius: 1.15rem;
        padding: 1rem;
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }
    .letter-option:has(.form-check-input:checked) {
        border-color: rgba(197, 143, 58, .8);
        box-shadow: 0 10px 24px rgba(197, 143, 58, .12);
        transform: translateY(-1px);
    }
    .letter-history {
        background: #f7f9fc;
        border-radius: .9rem;
    }
    .distribution-summary { position: static; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('distribution-form');
    const typeSelect = document.getElementById('type');
    const participantSelect = document.getElementById('participant_id');
    const participantSearch = document.getElementById('participant-search');
    const participantHelp = document.getElementById('participant-help');
    const candidateLoading = document.getElementById('candidate-loading');
    const candidateError = document.getElementById('candidate-error');
    const candidateEmpty = document.getElementById('candidate-empty');
    const lettersSection = document.getElementById('letters-section');
    const lettersList = document.getElementById('letters-list');
    const memberSelect = document.getElementById('team_member_id');
    const memberEmpty = document.getElementById('member-empty');
    const includeWrap = document.getElementById('include-reevaluated-wrap');
    const includeReevaluated = document.getElementById('include_reevaluated');
    const summary = document.getElementById('distribution-summary-text');
    const submit = document.getElementById('submit-distribution');
    const oldValues = {
        type: @json(old('type', $initialType)),
        participantId: String(@json(old('participant_id', request('participant_id', '')))),
        memberId: String(@json(old('team_member_id', ''))),
        testimonialIds: @json(array_map('strval', old('testimonial_ids', []))),
    };
    let options = @json($initialOptions);
    let restoreOldValues = true;

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const selectedParticipant = () => options.participants.find(
        (participant) => String(participant.id) === participantSelect.value
    );

    const renderParticipants = () => {
        const currentValue = participantSelect.value;
        const term = participantSearch.value.trim().toLocaleLowerCase('pt-BR');
        const filtered = options.participants.filter(
            (participant) => participant.name.toLocaleLowerCase('pt-BR').includes(term)
        );

        participantSelect.innerHTML = '<option value="">Selecione...</option>';
        filtered.forEach((participant) => {
            const option = document.createElement('option');
            option.value = participant.id;
            option.textContent = typeSelect.value === 'testimonial_search'
                ? `${participant.name} - ${participant.current_count}/${participant.target} depoimento(s)`
                : `${participant.name} - ${participant.eligible_count} carta(s)`;
            participantSelect.appendChild(option);
        });

        const desiredValue = restoreOldValues && oldValues.type === typeSelect.value
            ? oldValues.participantId
            : currentValue;
        if ([...participantSelect.options].some((option) => option.value === desiredValue)) {
            participantSelect.value = desiredValue;
        }

        candidateEmpty.classList.toggle('d-none', options.participants.length > 0);
        candidateEmpty.textContent = typeSelect.value === 'testimonial_search'
            ? 'Nenhum participante está abaixo da meta sem uma tarefa de busca aberta.'
            : 'Nenhum participante possui cartas disponíveis para este tipo de tarefa.';
        participantHelp.textContent = filtered.length === options.participants.length
            ? `${options.participants.length} participante(s) disponível(is).`
            : `${filtered.length} de ${options.participants.length} participante(s) encontrado(s).`;

        renderLetters(restoreOldValues);
    };

    const historyHtml = (letter) => {
        if (!letter.history?.length) return '';

        const items = letter.history.map((review) => `
            <li class="py-2 border-bottom">
                <div class="fw-semibold">${escapeHtml(review.decision)} por ${escapeHtml(review.reviewer)}</div>
                <div class="small text-secondary">${escapeHtml(review.flow_type)} · ${escapeHtml(review.decided_at)}</div>
                ${review.reason ? `<div class="small mt-1">Motivo: ${escapeHtml(review.reason)}</div>` : ''}
            </li>
        `).join('');

        return `
            <details class="letter-history mt-3 p-3">
                <summary class="fw-semibold">Ver histórico de ${letter.review_count} revisão(ões)</summary>
                <ul class="list-unstyled mb-0 mt-2">${items}</ul>
            </details>
        `;
    };

    const renderLetters = (useOldSelection = false) => {
        const participant = selectedParticipant();
        const hasLetters = typeSelect.value !== 'testimonial_search' && participant;
        lettersSection.classList.toggle('d-none', !hasLetters);
        lettersList.innerHTML = '';

        if (!hasLetters) {
            updateSummary();
            return;
        }

        const oldSelected = new Set(oldValues.testimonialIds);
        participant.testimonials.forEach((letter) => {
            const checked = useOldSelection && oldValues.participantId === String(participant.id)
                ? oldSelected.has(String(letter.id))
                : true;
            const reviewBadgeClass = letter.last_decision === 'rejected' ? 'text-bg-danger' : 'text-bg-secondary';

            lettersList.insertAdjacentHTML('beforeend', `
                <label class="letter-option d-block" for="testimonial-${letter.id}">
                    <div class="d-flex gap-3 align-items-start">
                        <input class="form-check-input mt-1 letter-checkbox" type="checkbox" name="testimonial_ids[]" value="${letter.id}" id="testimonial-${letter.id}" ${checked ? 'checked' : ''}>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div>
                                    <div class="fw-bold">${escapeHtml(letter.sender_name)}</div>
                                    <div class="small text-secondary">${escapeHtml(letter.relationship)} · ${escapeHtml(letter.created_at)}</div>
                                </div>
                                <span class="badge ${reviewBadgeClass}">${escapeHtml(letter.review_state)}</span>
                            </div>
                            ${letter.last_rejection_reason ? `<div class="alert alert-danger py-2 px-3 mt-3 mb-0 small"><strong>Última reprovação:</strong> ${escapeHtml(letter.last_rejection_reason)}</div>` : ''}
                            ${letter.last_reviewer ? `<div class="small text-secondary mt-2">Última revisão: ${escapeHtml(letter.last_reviewer)} · ${escapeHtml(letter.last_reviewed_at)} · ${letter.reevaluation_count} reavaliação(ões)</div>` : ''}
                            ${historyHtml(letter)}
                        </div>
                    </div>
                </label>
            `);
        });

        document.querySelectorAll('.letter-checkbox').forEach((checkbox) => checkbox.addEventListener('change', updateSummary));
        updateSummary();
    };

    const renderMembers = () => {
        const desiredValue = restoreOldValues ? oldValues.memberId : memberSelect.value;
        memberSelect.innerHTML = '<option value="">Selecione...</option>';
        options.members.forEach((member) => {
            const option = document.createElement('option');
            option.value = member.id;
            option.textContent = member.label;
            memberSelect.appendChild(option);
        });
        if ([...memberSelect.options].some((option) => option.value === desiredValue)) {
            memberSelect.value = desiredValue;
        }
        memberEmpty.classList.toggle('d-none', options.members.length > 0);
        updateSummary();
    };

    function updateSummary() {
        const participant = selectedParticipant();
        const memberLabel = memberSelect.selectedOptions[0]?.textContent;
        const selectedLetters = document.querySelectorAll('.letter-checkbox:checked').length;
        const needsLetters = typeSelect.value !== 'testimonial_search';
        const ready = Boolean(participant && memberSelect.value && (!needsLetters || selectedLetters > 0));

        if (!participant) {
            summary.textContent = 'Escolha um participante elegível.';
        } else if (!memberSelect.value) {
            summary.textContent = `${participant.name}: escolha o membro responsável.`;
        } else if (needsLetters && selectedLetters === 0) {
            summary.textContent = `${participant.name}: selecione pelo menos uma carta.`;
        } else {
            summary.textContent = needsLetters
                ? `${participant.name} · ${selectedLetters} carta(s) · ${memberLabel}`
                : `${participant.name} · tarefa de busca · ${memberLabel}`;
        }

        submit.disabled = !ready;
    }

    const loadOptions = async () => {
        candidateLoading.classList.remove('d-none');
        candidateError.classList.add('d-none');
        submit.disabled = true;

        const url = new URL(form.dataset.optionsUrl, window.location.origin);
        url.searchParams.set('type', typeSelect.value);
        if (typeSelect.value === 'reevaluation' && includeReevaluated.checked) {
            url.searchParams.set('include_reevaluated', '1');
        }

        try {
            const response = await fetch(url, {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error('Não foi possível carregar as opções desta tarefa.');
            options = await response.json();
            participantSearch.value = '';
            participantSelect.value = '';
            memberSelect.value = '';
            renderParticipants();
            renderMembers();
        } catch (error) {
            candidateError.textContent = error.message;
            candidateError.classList.remove('d-none');
        } finally {
            candidateLoading.classList.add('d-none');
            restoreOldValues = false;
        }
    };

    typeSelect.addEventListener('change', () => {
        includeWrap.classList.toggle('d-none', typeSelect.value !== 'reevaluation');
        if (typeSelect.value !== 'reevaluation') includeReevaluated.checked = false;
        restoreOldValues = false;
        loadOptions();
    });
    includeReevaluated.addEventListener('change', () => {
        restoreOldValues = false;
        loadOptions();
    });
    participantSearch.addEventListener('input', renderParticipants);
    participantSelect.addEventListener('change', () => renderLetters(false));
    memberSelect.addEventListener('change', updateSummary);
    document.getElementById('select-all-letters').addEventListener('click', () => {
        document.querySelectorAll('.letter-checkbox').forEach((checkbox) => { checkbox.checked = true; });
        updateSummary();
    });
    document.getElementById('clear-all-letters').addEventListener('click', () => {
        document.querySelectorAll('.letter-checkbox').forEach((checkbox) => { checkbox.checked = false; });
        updateSummary();
    });

    renderParticipants();
    renderMembers();
    restoreOldValues = false;
});
</script>
@endpush
