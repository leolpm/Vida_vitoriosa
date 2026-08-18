<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Support\CurrentEvent;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class TestimonialSubmissionController extends Controller
{
    public function __construct(private readonly CurrentEvent $currentEvent)
    {
    }

    public function create(): View
    {
        $participants = Participant::active()->orderBy('name')->get();
        $publicImagePath = Setting::valueFor('public_site_image_path');
        $publicImageUrl = $publicImagePath && Storage::disk('public')->exists($publicImagePath)
            ? '/storage/' . ltrim($publicImagePath, '/')
            : ($this->currentEvent->get()->slug === 'edd' ? asset('images/events/edd/edd-public-banner.png') : null);
        $settings = Setting::seededDefaults();

        foreach ($settings as $key => $value) {
            $settings[$key] = Setting::valueFor($key, $value);
        }

        $closingAt = $this->testimonialClosingAt();

        return view('public.testimonials.create', [
            'participants' => $participants,
            'publicImageUrl' => $publicImageUrl,
            'settings' => $settings,
            'relationships' => Setting::relationships(),
            'eventInactive' => ! $this->currentEvent->get()->isActive(),
            'testimonialsClosed' => ! $this->currentEvent->get()->isActive() || ($closingAt !== null && now()->greaterThanOrEqualTo($closingAt)),
            'testimonialsClosesAtLabel' => $closingAt?->format('d/m/Y \à\s H:i'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->currentEvent->get()->isActive()) {
            return redirect()
                ->route('testimonials.create')
                ->with('error', 'Este evento não está recebendo novas mensagens no momento.');
        }

        if ($this->testimonialSubmissionClosed()) {
            return redirect()
                ->route('testimonials.create')
                ->with('error', 'O período para envio de depoimentos foi encerrado. Obrigado pelo carinho e pela participação.');
        }

        $validated = $request->validate([
            'sender_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:25'],
            'participant_id' => ['required', 'integer', Rule::exists('participants', 'id')
                ->where('event_id', $this->currentEvent->id())
                ->where('status', 'active')],
            'relationship' => ['required', 'string', Rule::in(Setting::relationships())],
            'relationship_other' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('relationship') === 'Outro')],
            'message' => ['required', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . config('vida.testimonial_upload_max_kb')],
        ]);

        $photoPath = null;
        $photoOriginalName = null;
        $photoSize = null;

        if ($request->hasFile('photo')) {
            $photoOriginalName = $request->file('photo')->getClientOriginalName();
            $photoSize = $request->file('photo')->getSize();
            $photoPath = $request->file('photo')->store(
                'events/' . $this->currentEvent->get()->slug . '/testimonials',
                'public'
            );
        }

        Testimonial::create([
            'participant_id' => $validated['participant_id'],
            'sender_name' => $validated['sender_name'],
            'phone' => $validated['phone'] ?? null,
            'relationship' => $validated['relationship'],
            'relationship_other' => $validated['relationship_other'] ?? null,
            'message' => $validated['message'],
            'photo_path' => $photoPath,
            'photo_original_name' => $photoOriginalName,
            'photo_size' => $photoSize,
            'status' => 'received',
        ]);

        return redirect()
            ->route('testimonials.success')
            ->with('success', 'Seu depoimento foi enviado com sucesso.');
    }

    public function success(): View
    {
        return view('public.testimonials.success', [
            'settings' => $this->resolvedSettings(),
        ]);
    }

    private function testimonialSubmissionClosed(): bool
    {
        $closingAt = $this->testimonialClosingAt();

        return $closingAt !== null && now()->greaterThanOrEqualTo($closingAt);
    }

    private function testimonialClosingAt(): ?Carbon
    {
        $value = Setting::valueFor('testimonials_closes_at');

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d\TH:i', $value, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolvedSettings(): array
    {
        $settings = Setting::seededDefaults();

        foreach ($settings as $key => $value) {
            $settings[$key] = Setting::valueFor($key, $value);
        }

        return $settings;
    }
}
