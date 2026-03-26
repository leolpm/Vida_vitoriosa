<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TestimonialSubmissionController extends Controller
{
    public function create(): View
    {
        $participants = Participant::active()->orderBy('name')->get();
        $publicImagePath = Setting::valueFor('public_site_image_path');
        $publicImageUrl = $publicImagePath ? '/storage/' . ltrim($publicImagePath, '/') : null;
        $settings = Setting::seededDefaults();

        foreach ($settings as $key => $value) {
            $settings[$key] = Setting::valueFor($key, $value);
        }

        return view('public.testimonials.create', compact('participants', 'publicImageUrl', 'settings'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sender_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:25'],
            'participant_id' => ['required', 'integer', Rule::exists('participants', 'id')->where('status', 'active')],
            'relationship' => ['required', 'string', Rule::in(config('vida.relationships'))],
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
            $photoPath = $request->file('photo')->store('testimonials', 'public');
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
        return view('public.testimonials.success');
    }
}
