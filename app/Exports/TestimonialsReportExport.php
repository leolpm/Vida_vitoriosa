<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TestimonialsReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $testimonials)
    {
    }

    public function collection(): Collection
    {
        return $this->testimonials;
    }

    public function headings(): array
    {
        return ['Nome', 'Telefone', 'Participante', 'Status', 'PDF gerado'];
    }

    public function map($testimonial): array
    {
        return [
            $testimonial->sender_name,
            $testimonial->phone ?: '---',
            $testimonial->participant?->label ?: '---',
            $testimonial->status_label,
            $testimonial->is_pdf_generated ? 'Sim' : 'Não',
        ];
    }
}
