<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ParticipantsTemplateExport;
use App\Imports\ParticipantsBulkImport;
use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ParticipantController extends Controller
{
    public function index(): View
    {
        $participantName = request()->string('participant_name')->toString();

        $participantsQuery = Participant::query()->orderBy('name');

        if ($participantName !== '') {
            $participantsQuery->where(function ($query) use ($participantName) {
                $query->where('name', 'like', '%' . $participantName . '%')
                    ->orWhere('display_name', 'like', '%' . $participantName . '%');
            });
        }

        return view('admin.participants.index', [
            'participants' => $participantsQuery->paginate(10)->withQueryString(),
            'participantName' => $participantName,
        ]);
    }

    public function create(): View
    {
        return view('admin.participants.form', [
            'participant' => new Participant(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'retreat_edition' => ['nullable', 'string', 'max:255'],
        ]);

        Participant::create($validated);

        return redirect()->route('admin.participants.index')->with('success', 'Participante criado com sucesso.');
    }

    public function edit(Participant $participant): View
    {
        return view('admin.participants.form', compact('participant'));
    }

    public function update(Request $request, Participant $participant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'retreat_edition' => ['nullable', 'string', 'max:255'],
        ]);

        $participant->update($validated);

        return redirect()->route('admin.participants.index')->with('success', 'Participante atualizado com sucesso.');
    }

    public function destroy(Participant $participant): RedirectResponse
    {
        $participant->delete();

        return redirect()->route('admin.participants.index')->with('success', 'Participante removido com sucesso.');
    }

    public function importForm(): View
    {
        return view('admin.participants.import');
    }

    public function downloadTemplate()
    {
        return Excel::download(new ParticipantsTemplateExport(), 'modelo_participantes.xlsx');
    }

    public function importStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xls,xlsx', 'max:20480'],
        ]);

        $uploadedFile = $validated['file'];
        $delimiter = $this->detectCsvDelimiter($uploadedFile);
        $import = new ParticipantsBulkImport($delimiter);

        try {
            Excel::import($import, $uploadedFile);
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->withInput()->with('error', 'Não foi possível ler o arquivo enviado. Verifique se ele está em UTF-8 e se as colunas seguem o modelo.');
        }

        $report = $import->report();

        $status = $report['errors_count'] > 0
            ? ($report['created_count'] > 0 ? 'warning' : 'error')
            : 'success';

        return redirect()
            ->route('admin.participants.index')
            ->with('import_report', $report + ['status' => $status]);
    }

    private function detectCsvDelimiter($uploadedFile): string
    {
        if (! is_object($uploadedFile) || ! method_exists($uploadedFile, 'getClientOriginalExtension')) {
            return ',';
        }

        if (strtolower($uploadedFile->getClientOriginalExtension()) !== 'csv') {
            return ',';
        }

        $path = $uploadedFile->getRealPath();

        if (! $path || ! file_exists($path)) {
            return ',';
        }

        $handle = fopen($path, 'rb');

        if (! $handle) {
            return ',';
        }

        try {
            $line = fgets($handle);

            if ($line === false) {
                return ',';
            }

            $semicolonCount = substr_count($line, ';');
            $commaCount = substr_count($line, ',');

            return $semicolonCount > $commaCount ? ';' : ',';
        } finally {
            fclose($handle);
        }
    }
}
