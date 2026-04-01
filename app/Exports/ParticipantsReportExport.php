<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ParticipantsReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Collection $participants)
    {
    }

    public function collection(): Collection
    {
        return $this->participants;
    }

    public function headings(): array
    {
        return ['Nome'];
    }

    public function map($participant): array
    {
        return [
            $participant->name,
        ];
    }
}
