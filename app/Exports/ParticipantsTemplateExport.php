<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ParticipantsTemplateExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        return collect([
            [
                'Ana Oliveira',
                'Ana Oliveira',
                'active',
                'Vida Vitoriosa 2026',
            ],
            [
                'João Santos',
                'João Santos',
                'inactive',
                'Vida Vitoriosa 2026',
            ],
            [
                'Maria Silva',
                '',
                'active',
                'Vida Vitoriosa 2026',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Nome',
            'Nome de exibição',
            'Status',
            'Edição do retiro',
        ];
    }
}
