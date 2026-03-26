<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function index(Request $request): View
    {
        $query = Testimonial::with('participant')->latest();

        if ($request->filled('participant_id')) {
            $query->where('participant_id', $request->integer('participant_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('generated')) {
            $query->where('is_pdf_generated', $request->boolean('generated'));
        }

        return view('admin.testimonials.index', [
            'testimonials' => $query->paginate(10)->withQueryString(),
            'participants' => Participant::orderBy('name')->get(),
        ]);
    }

    public function show(Testimonial $testimonial): View
    {
        $testimonial->load('participant', 'pdfBatch');

        return view('admin.testimonials.show', compact('testimonial'));
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:received,reviewed,approved,archived'],
        ]);

        $testimonial->update($validated);

        return back()->with('success', 'Depoimento atualizado com sucesso.');
    }

    public function downloadPhoto(Testimonial $testimonial)
    {
        abort_unless($testimonial->photo_path, 404);

        return Storage::disk('public')->download(
            $testimonial->photo_path,
            $testimonial->photo_original_name ?: basename($testimonial->photo_path)
        );
    }
}
