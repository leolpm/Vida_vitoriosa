<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\PdfBatch;
use App\Models\PrintFlow;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $minimumTestimonials = (int) Setting::valueFor(
            'print_flow_min_testimonials',
            Setting::seededDefaults()['print_flow_min_testimonials']
        );

        return view('admin.dashboard', [
            'participantsCount' => Participant::count(),
            'activeParticipantsCount' => Participant::active()->count(),
            'testimonialsCount' => Testimonial::count(),
            'approvedTestimonialsCount' => Testimonial::where('status', 'approved')->count(),
            'approvedWithoutPdfTestimonialsCount' => Testimonial::where('status', 'approved')->where('is_pdf_generated', false)->count(),
            'pendingTestimonialsCount' => Testimonial::where('status', '!=', 'approved')->count(),
            'usersCount' => User::where('role', 'admin')->count(),
            'pdfBatchesCount' => PdfBatch::count(),
            'openPrintFlowsCount' => PrintFlow::whereIn('status', PrintFlow::OPEN_STATUSES)->count(),
            'criticalParticipantsCount' => Participant::active()
                ->criticalForPrintFlow($minimumTestimonials)
                ->count(),
            'recentTestimonials' => Testimonial::with('participant')->latest()->take(6)->get(),
            'recentBatches' => PdfBatch::with(['participant', 'generatedBy'])->latest()->take(5)->get(),
        ]);
    }
}
