<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\PdfBatch;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'participantsCount' => Participant::count(),
            'activeParticipantsCount' => Participant::active()->count(),
            'testimonialsCount' => Testimonial::count(),
            'pendingTestimonialsCount' => Testimonial::where('is_pdf_generated', false)->count(),
            'usersCount' => User::where('role', 'admin')->count(),
            'pdfBatchesCount' => PdfBatch::count(),
            'recentTestimonials' => Testimonial::with('participant')->latest()->take(6)->get(),
            'recentBatches' => PdfBatch::with(['participant', 'generatedBy'])->latest()->take(5)->get(),
        ]);
    }
}
