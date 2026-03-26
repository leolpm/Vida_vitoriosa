@extends('layouts.admin')

@section('title', 'Participantes')
@section('section', 'Cadastro')
@section('page-title', 'Participantes')

@section('content')
<div class="card-surface p-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="section-eyebrow text-secondary mb-1">Lista</div>
            <h2 class="h5 mb-0">Participantes do retiro</h2>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.participants.import.form') }}" class="btn btn-outline-dark">Importar planilha</a>
            <a href="{{ route('admin.participants.template') }}" class="btn btn-outline-secondary">Baixar modelo</a>
            <a href="{{ route('admin.participants.create') }}" class="btn btn-gold">Novo participante</a>
        </div>
    </div>

    @if (session('import_report'))
        @php($importReport = session('import_report'))
        <div class="alert {{ $importReport['status'] === 'error' ? 'alert-danger' : ($importReport['status'] === 'warning' ? 'alert-warning' : 'alert-success') }} mb-4">
            <div class="fw-semibold mb-1">Importação concluída</div>
            <div>{{ $importReport['created_count'] }} participante(s) importado(s), {{ $importReport['skipped_count'] }} ignorado(s) e {{ $importReport['errors_count'] }} problema(s) encontrado(s).</div>

            @if (!empty($importReport['errors']))
                <div class="mt-3">
                    <div class="fw-semibold mb-2">Detalhes</div>
                    <ul class="mb-0">
                        @foreach (array_slice($importReport['errors'], 0, 5) as $error)
                            <li>Linha {{ $error['line'] }}: {{ implode(' ', $error['messages']) }}</li>
                        @endforeach
                    </ul>
                    @if (count($importReport['errors']) > 5)
                        <div class="small mt-2">Mostrando apenas os 5 primeiros problemas.</div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Nome de exibição</th>
                <th>Edição</th>
                <th>Status</th>
                <th class="text-end">Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($participants as $participant)
                <tr>
                    <td class="fw-semibold">{{ $participant->name }}</td>
                    <td>{{ $participant->display_name ?: '---' }}</td>
                    <td>{{ $participant->retreat_edition ?: '---' }}</td>
                    <td>
                        <span class="badge {{ $participant->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $participant->status === 'active' ? 'Ativo' : 'Inativo' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.participants.edit', $participant) }}" class="btn btn-sm btn-outline-dark">Editar</a>
                        <form action="{{ route('admin.participants.destroy', $participant) }}" method="POST" class="d-inline" onsubmit="return confirm('Excluir este participante?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-secondary">Nenhum participante cadastrado.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $participants->links() }}
</div>
@endsection
