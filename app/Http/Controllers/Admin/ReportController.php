<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ParticipantsReportExport;
use App\Exports\TestimonialsReportExport;
use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function participants(Request $request): View
    {
        $filter = $this->participantFilter($request);
        $query = $this->participantsQuery($filter);

        return view('admin.reports.participants', [
            'participants' => (clone $query)->orderBy('name')->paginate(20)->withQueryString(),
            'filter' => $filter,
            'totalCount' => (clone $query)->count(),
            'filterLabel' => $this->participantFilterLabel($filter),
        ]);
    }

    public function participantsPrint(Request $request): View
    {
        $filter = $this->participantFilter($request);

        return view('admin.reports.print.participants', [
            'participants' => $this->participantsQuery($filter)->orderBy('name')->get(),
            'filterLabel' => $this->participantFilterLabel($filter),
        ]);
    }

    public function participantsExcel(Request $request)
    {
        $filter = $this->participantFilter($request);

        return Excel::download(
            new ParticipantsReportExport($this->participantsQuery($filter)->orderBy('name')->get()),
            'relatorio_participantes.xlsx'
        );
    }

    public function testimonials(Request $request): View
    {
        $status = $this->testimonialStatusFilter($request);
        $generated = $this->testimonialGeneratedFilter($request);
        $query = $this->testimonialsQuery($status, $generated);

        return view('admin.reports.testimonials', [
            'testimonials' => (clone $query)->latest()->paginate(20)->withQueryString(),
            'status' => $status,
            'generated' => $generated,
            'totalCount' => (clone $query)->count(),
            'statusLabel' => $this->testimonialStatusLabel($status),
            'generatedLabel' => $this->testimonialGeneratedLabel($generated),
        ]);
    }

    public function testimonialsPrint(Request $request): View
    {
        $status = $this->testimonialStatusFilter($request);
        $generated = $this->testimonialGeneratedFilter($request);

        return view('admin.reports.print.testimonials', [
            'testimonials' => $this->testimonialsQuery($status, $generated)->latest()->get(),
            'statusLabel' => $this->testimonialStatusLabel($status),
            'generatedLabel' => $this->testimonialGeneratedLabel($generated),
        ]);
    }

    public function testimonialsExcel(Request $request)
    {
        $status = $this->testimonialStatusFilter($request);
        $generated = $this->testimonialGeneratedFilter($request);

        return Excel::download(
            new TestimonialsReportExport($this->testimonialsQuery($status, $generated)->latest()->get()),
            'relatorio_depoimentos.xlsx'
        );
    }

    private function participantsQuery(string $filter): Builder
    {
        $query = Participant::query();

        return match ($filter) {
            'approved_pending' => $query->whereHas('testimonials', fn ($testimonials) => $testimonials->where('status', 'approved')->where('is_pdf_generated', false)),
            'approved' => $query->whereHas('testimonials', fn ($testimonials) => $testimonials->where('status', 'approved')),
            'pending' => $query->whereHas('testimonials', fn ($testimonials) => $testimonials->where('status', '!=', 'approved')),
            'without_testimonials' => $query->whereDoesntHave('testimonials'),
            default => $query,
        };
    }

    private function participantFilter(Request $request): string
    {
        $filter = $request->string('participants_filter')->toString();

        return in_array($filter, ['all', 'approved_pending', 'approved', 'pending', 'without_testimonials'], true)
            ? $filter
            : 'all';
    }

    private function participantFilterLabel(string $filter): string
    {
        return match ($filter) {
            'approved_pending' => 'Aprovados sem PDF',
            'approved' => 'Com depoimentos aprovados',
            'pending' => 'Com depoimentos pendentes',
            'without_testimonials' => 'Sem depoimentos',
            default => 'Todos os participantes',
        };
    }

    private function testimonialsQuery(string $status, string $generated): Builder
    {
        $query = Testimonial::query()->with('participant');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($generated === 'yes') {
            $query->where('is_pdf_generated', true);
        }

        if ($generated === 'no') {
            $query->where('is_pdf_generated', false);
        }

        return $query;
    }

    private function testimonialStatusFilter(Request $request): string
    {
        $status = $request->string('status')->toString();

        return in_array($status, ['all', 'received', 'reviewed', 'approved', 'archived'], true)
            ? $status
            : 'all';
    }

    private function testimonialGeneratedFilter(Request $request): string
    {
        $generated = $request->string('generated')->toString();

        return in_array($generated, ['all', 'yes', 'no'], true) ? $generated : 'all';
    }

    private function testimonialStatusLabel(string $status): string
    {
        return match ($status) {
            'received' => 'Recebido',
            'reviewed' => 'Revisado',
            'approved' => 'Aprovado',
            'archived' => 'Arquivado',
            default => 'Todos os status',
        };
    }

    private function testimonialGeneratedLabel(string $generated): string
    {
        return match ($generated) {
            'yes' => 'PDF gerado: Sim',
            'no' => 'PDF gerado: Não',
            default => 'PDF gerado: Todos',
        };
    }
}
