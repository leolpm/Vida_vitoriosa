@extends('layouts.public')

@section('title', 'Enviar depoimento')

@section('content')
@php($relationships = config('vida.relationships'))

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.15.0/build/css/intlTelInput.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<style>
    .form-stage {
        max-width: 860px;
        margin: 0 auto;
    }

    .paper-card {
        background:
            linear-gradient(180deg, rgba(255,255,255,0.78), rgba(246, 236, 223, 0.95)),
            #f6ecdf;
        border: 1px solid rgba(80, 64, 48, 0.16);
        box-shadow: 0 24px 70px rgba(33, 29, 24, 0.14);
        border-radius: 1.8rem;
        overflow: hidden;
    }

    .paper-header {
        padding: 1rem 1rem 0;
    }

    .paper-banner {
        position: relative;
        aspect-ratio: 5 / 1;
        min-height: 150px;
        padding: .55rem;
        border-radius: 1.4rem;
        overflow: hidden;
        background: linear-gradient(180deg, #f0e2c8 0%, #e2c89f 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.35);
    }

    .paper-banner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center center;
        display: block;
        border-radius: 1rem;
    }

    @media (max-width: 575.98px) {
        .paper-header {
            padding: .75rem .75rem 0;
        }

        .paper-banner {
            aspect-ratio: 16 / 7;
            min-height: 120px;
            padding: .4rem;
            border-radius: 1.1rem;
        }

        .paper-banner img,
        .paper-banner .fallback {
            border-radius: .85rem;
        }

        .paper-body {
            padding: 1.15rem .9rem 1.25rem;
        }

        .form-title {
            font-size: clamp(1.35rem, 6vw, 1.8rem);
        }
    }

    .paper-banner .fallback {
        height: 100%;
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255,255,255,.92);
        font-family: 'Fraunces', serif;
        font-size: 1.8rem;
        text-align: center;
        padding: 1rem;
        background: linear-gradient(135deg, rgba(37, 30, 24, 0.76), rgba(37, 30, 24, 0.42));
        border-radius: 1rem;
    }

    .paper-body {
        padding: 1.4rem 1rem 1.5rem;
    }

    .form-title {
        font-family: 'Fraunces', serif;
        color: #2d2219;
        text-align: center;
        font-size: clamp(1.55rem, 2vw, 2.2rem);
        line-height: 1.1;
    }

    .form-subtitle {
        text-align: center;
        color: rgba(62, 49, 38, 0.85);
        max-width: 620px;
        margin: 0 auto;
    }

    .field-label {
        color: #46382b;
        font-weight: 700;
        margin-bottom: .45rem;
    }

    .form-control,
    .form-select {
        background-color: rgba(255,255,255,0.72);
        border-color: rgba(82, 65, 48, 0.18);
        border-radius: .8rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #c07b2f;
        box-shadow: 0 0 0 .2rem rgba(192, 123, 47, .12);
    }

    .btn-submit {
        background: linear-gradient(180deg, #d9822f, #b35c16);
        border: 0;
        color: #fff;
        font-weight: 800;
        padding-inline: 2rem;
        border-radius: .9rem;
        box-shadow: 0 10px 24px rgba(179, 92, 22, 0.26);
    }

    .mini-hint {
        color: rgba(82, 65, 48, 0.82);
        font-size: .92rem;
    }

    .surprise-callout {
        background: linear-gradient(135deg, rgba(192, 123, 47, 0.14), rgba(213, 162, 76, 0.20));
        border: 1px solid rgba(192, 123, 47, 0.26);
        color: #5f3f18;
        border-radius: 1rem;
        padding: 1rem 1.1rem;
        box-shadow: 0 14px 30px rgba(88, 58, 18, 0.08);
    }

    .surprise-callout .surprise-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .25rem .7rem;
        border-radius: 999px;
        background: rgba(192, 123, 47, 0.16);
        color: #8a4f12;
        font-size: .76rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: .7rem;
    }

    .surprise-callout .surprise-title {
        font-size: 1.02rem;
        font-weight: 800;
        line-height: 1.4;
        margin-bottom: .35rem;
    }

    .surprise-callout .surprise-text {
        font-size: .96rem;
        line-height: 1.55;
        color: #6a4a1f;
    }

    .iti {
        width: 100%;
    }

    .choices {
        margin-bottom: 0;
        font-size: 1rem;
    }

    .choices__inner {
        min-height: calc(3.5rem + 2px);
        padding: .85rem 1rem;
        background: rgba(255,255,255,0.72);
        border-color: rgba(82, 65, 48, 0.18);
        border-radius: .8rem;
    }

    .choices__list--dropdown,
    .choices__list[aria-expanded] {
        border-color: rgba(82, 65, 48, 0.18);
        border-radius: .8rem;
        box-shadow: 0 18px 42px rgba(33, 29, 24, 0.16);
    }

    .choices__list--dropdown .choices__item--selectable.is-highlighted {
        background: #d9822f;
    }

    .choices__placeholder {
        opacity: 1;
        color: #6f6458;
    }
</style>
@endpush

<div class="form-stage py-4 py-md-5">
    <div class="paper-card">
        <div class="paper-header">
            <div class="paper-banner">
                @if ($publicImageUrl)
                    <img src="{{ $publicImageUrl }}" alt="Imagem do retiro">
                @else
                    <div class="fallback">{{ $settings['retreat_name'] }}</div>
                @endif
            </div>
        </div>

        <div class="paper-body">
            <div class="mb-4">
                <div class="section-eyebrow text-center mb-2">Vida Vitoriosa</div>
                <h1 class="form-title mb-3">Envie um depoimento especial para um participante do retiro</h1>
                <p class="form-subtitle mb-0">
                    Envie uma mensagem de carinho e encorajamento para um participante do retiro Vida Vitoriosa.
                    Sua mensagem será entregue de forma especial a quem você ama.
                </p>
            </div>

            <div class="surprise-callout mb-4">
                <div class="surprise-badge">Atenção: surpresa</div>
                <div class="surprise-title">Este depoimento é uma surpresa e não pode ser revelado ao participante.</div>
                <div class="surprise-text">
                    Não conte que você escreveu esta mensagem. Ela será entregue de forma especial e precisa permanecer em segredo.
                </div>
            </div>

            <form action="{{ route('testimonials.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                @csrf

                <div class="col-12">
                    <label for="sender_name" class="field-label">Seu nome <span class="text-danger">*</span></label>
                    <input type="text" name="sender_name" id="sender_name" class="form-control form-control-lg @error('sender_name') is-invalid @enderror" value="{{ old('sender_name') }}" placeholder="Digite seu nome">
                    @error('sender_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="phone" class="field-label">Telefone <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" id="phone" class="form-control form-control-lg @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="(00) 00000-0000" inputmode="tel" autocomplete="tel" required>
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="participant_id" class="field-label">Para qual participante <span class="text-danger">*</span></label>
                    <select name="participant_id" id="participant_id" class="form-select form-select-lg @error('participant_id') is-invalid @enderror">
                        <option value="">Selecione o participante...</option>
                        @foreach ($participants as $participant)
                            <option value="{{ $participant->id }}" @selected(old('participant_id') == $participant->id)>
                                {{ $participant->label }}
                            </option>
                        @endforeach
                    </select>
                    @error('participant_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="relationship" class="field-label">Relação <span class="text-danger">*</span></label>
                    <select name="relationship" id="relationship" class="form-select form-select-lg @error('relationship') is-invalid @enderror">
                        <option value="">Selecione...</option>
                        @foreach ($relationships as $relationship)
                            <option value="{{ $relationship }}" @selected(old('relationship') === $relationship)>{{ $relationship }}</option>
                        @endforeach
                    </select>
                    @error('relationship') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6" id="relationship-other-wrapper" style="{{ old('relationship') === 'Outro' ? '' : 'display:none;' }}">
                    <label for="relationship_other" class="field-label">Se for outro, informe</label>
                    <input type="text" name="relationship_other" id="relationship_other" class="form-control form-control-lg @error('relationship_other') is-invalid @enderror" value="{{ old('relationship_other') }}" placeholder="Ex.: vizinho, mentor">
                    @error('relationship_other') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label for="message" class="field-label">Depoimento <span class="text-danger">*</span></label>
                    <textarea name="message" id="message" rows="6" class="form-control @error('message') is-invalid @enderror" placeholder="Escreva sua mensagem de carinho e encorajamento...">{{ old('message') }}</textarea>
                    @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label for="photo" class="field-label">Foto (opcional)</label>
                    <input type="file" name="photo" id="photo" class="form-control @error('photo') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/*">
                    <div class="mini-hint mt-1">Max. 10MB</div>
                    @error('photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 text-center pt-2">
                    <button type="submit" class="btn btn-submit btn-lg px-5">Enviar Depoimento</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.15.0/build/js/intlTelInput.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    (function () {
        const relationship = document.getElementById('relationship');
        const wrapper = document.getElementById('relationship-other-wrapper');
        const phoneInput = document.getElementById('phone');
        const participantSelect = document.getElementById('participant_id');
        const form = document.querySelector('form[action="{{ route('testimonials.store') }}"]');
        let iti = null;
        let participantChoices = null;

        const toggle = () => {
            wrapper.style.display = relationship.value === 'Outro' ? '' : 'none';
        };

        if (phoneInput && window.intlTelInput) {
            iti = window.intlTelInput(phoneInput, {
                initialCountry: 'br',
                separateDialCode: true,
                nationalMode: true,
                autoPlaceholder: 'aggressive',
                formatAsYouType: true,
                formatOnDisplay: true,
                loadUtils: () => import('https://cdn.jsdelivr.net/npm/intl-tel-input@25.15.0/build/js/utils.js'),
            });

            if (phoneInput.value) {
                iti.setNumber(phoneInput.value);
            }

            phoneInput.addEventListener('countrychange', () => {
                const currentValue = phoneInput.value;

                if (!currentValue) {
                    return;
                }

                iti.setNumber(currentValue);
            });
        }

        if (participantSelect && window.Choices) {
            participantChoices = new window.Choices(participantSelect, {
                searchEnabled: true,
                searchPlaceholderValue: 'Digite para filtrar',
                itemSelectText: '',
                shouldSort: false,
                placeholder: true,
                placeholderValue: 'Selecione o participante...',
                searchResultLimit: 50,
                position: 'bottom',
            });

            participantSelect.addEventListener('showDropdown', () => {
                const searchInput = participantSelect.closest('.choices')?.querySelector('.choices__input');
                searchInput?.focus();
            });
        }

        relationship?.addEventListener('change', toggle);
        form?.addEventListener('submit', () => {
            if (iti && phoneInput?.value) {
                phoneInput.value = iti.getNumber(window.intlTelInputUtils?.numberFormat?.E164 || undefined) || phoneInput.value;
            }
        });
        toggle();
    })();
</script>
@endpush
