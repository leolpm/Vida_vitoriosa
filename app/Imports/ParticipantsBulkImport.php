<?php

namespace App\Imports;

use App\Models\Participant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ParticipantsBulkImport implements ToCollection, WithHeadingRow, SkipsEmptyRows, WithCustomCsvSettings
{
    private array $report = [
        'created_count' => 0,
        'skipped_count' => 0,
        'errors_count' => 0,
        'errors' => [],
    ];

    public function __construct(private readonly string $delimiter = ',')
    {
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => $this->delimiter,
            'enclosure' => '"',
            'input_encoding' => 'UTF-8',
        ];
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2;
            $data = $this->normalizeRow($row->toArray());

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'display_name' => ['nullable', 'string', 'max:255'],
                'status' => ['required', 'string', 'max:255'],
                'retreat_edition' => ['nullable', 'string', 'max:255'],
            ], [
                'name.required' => 'O campo nome é obrigatório.',
                'name.string' => 'O campo nome deve ser um texto.',
                'name.max' => 'O campo nome não pode ultrapassar 255 caracteres.',
                'display_name.string' => 'O campo nome de exibição deve ser um texto.',
                'display_name.max' => 'O campo nome de exibição não pode ultrapassar 255 caracteres.',
                'status.required' => 'O campo status é obrigatório.',
                'status.string' => 'O campo status deve ser um texto.',
                'status.max' => 'O campo status não pode ultrapassar 255 caracteres.',
                'retreat_edition.string' => 'O campo edição do retiro deve ser um texto.',
                'retreat_edition.max' => 'O campo edição do retiro não pode ultrapassar 255 caracteres.',
            ]);

            if ($validator->fails()) {
                $this->registerError($lineNumber, $validator->errors()->all(), $data);
                continue;
            }

            $name = $this->cleanString($data['name']);
            $displayName = $this->cleanString($data['display_name'] ?? null);
            $status = $this->normalizeStatus((string) $data['status']);
            $retreatEdition = $this->cleanString($data['retreat_edition'] ?? null);

            if (! in_array($status, ['active', 'inactive'], true)) {
                $this->registerError($lineNumber, ['O campo status deve ser Ativo ou Inativo.'], $data);
                continue;
            }

            if ($this->isDuplicate($name, $retreatEdition)) {
                $this->report['skipped_count']++;
                $this->registerError($lineNumber, ['Participante duplicado para a mesma edição.'], $data, true);
                continue;
            }

            Participant::create([
                'name' => $name,
                'display_name' => $displayName,
                'status' => $status,
                'retreat_edition' => $retreatEdition,
            ]);

            $this->report['created_count']++;
        }
    }

    public function report(): array
    {
        return $this->report;
    }

    private function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $slug = Str::of((string) $key)
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->toString();

            $normalized[$slug] = is_string($value) ? trim($value) : $value;
        }

        return [
            'name' => $normalized['nome'] ?? $normalized['name'] ?? null,
            'display_name' => $normalized['nome_de_exibicao'] ?? $normalized['nome_exibicao'] ?? $normalized['display_name'] ?? null,
            'status' => $normalized['status'] ?? null,
            'retreat_edition' => $normalized['edicao_do_retiro'] ?? $normalized['edicao_retiro'] ?? $normalized['retreat_edition'] ?? null,
        ];
    }

    private function cleanString(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }

    private function normalizeStatus(string $status): string
    {
        $normalized = Str::of($status)->ascii()->lower()->trim()->toString();

        return match ($normalized) {
            'active', 'ativo', 'ativ' => 'active',
            'inactive', 'inativo', 'inativ' => 'inactive',
            default => $normalized,
        };
    }

    private function isDuplicate(string $name, ?string $retreatEdition): bool
    {
        return Participant::query()
            ->where('name', $name)
            ->where(function ($query) use ($retreatEdition) {
                if ($retreatEdition === null) {
                    $query->whereNull('retreat_edition');

                    return;
                }

                $query->where('retreat_edition', $retreatEdition);
            })
            ->exists();
    }

    private function isEmptyRow(array $data): bool
    {
        foreach ($data as $value) {
            if (is_string($value) && trim($value) !== '') {
                return false;
            }

            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }

    private function registerError(int $lineNumber, array $messages, array $data, bool $isWarning = false): void
    {
        $this->report['errors_count']++;
        $this->report['errors'][] = [
            'line' => $lineNumber,
            'messages' => $messages,
            'warning' => $isWarning,
            'data' => $data,
        ];
    }
}
