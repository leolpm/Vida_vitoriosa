@extends('layouts.admin')

@section('title', 'Equipe')
@section('section', 'Operação global')
@section('page-title', 'Equipe do Fluxo de Impressão')

@section('content')
<div class="card-surface p-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="section-eyebrow text-secondary mb-1">Equipe compartilhada</div>
            <h2 class="h5 mb-1">Membros autorizados por evento</h2>
            <p class="text-secondary mb-0">O limite considera tarefas abertas em todos os eventos.</p>
        </div>
        <a href="{{ route('admin.team.create') }}" class="btn btn-gold"><i class="bi bi-person-plus me-1"></i>Novo membro</a>
    </div>

    <form method="GET" class="row g-3 align-items-end mb-4">
        <div class="col-12 col-md-5">
            <label class="form-label fw-semibold" for="name">Nome</label>
            <input class="form-control" id="name" name="name" value="{{ $name }}" placeholder="Digite o nome">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label fw-semibold" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">Todos</option>
                <option value="active" @selected($status === 'active')>Ativos</option>
                <option value="inactive" @selected($status === 'inactive')>Inativos</option>
            </select>
        </div>
        <div class="col-12 col-md-auto d-flex gap-2">
            <button class="btn btn-outline-dark" data-loading-text="Aplicando filtros...">Filtrar</button>
            <a href="{{ route('admin.team.index') }}" class="btn btn-outline-secondary">Limpar</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Nome</th><th>Telefone</th><th>Eventos</th><th>Tarefas abertas</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
            <tbody>
            @forelse ($members as $member)
                <tr>
                    <td class="fw-semibold">{{ $member->name }}</td>
                    <td>{{ $member->phone }}</td>
                    <td>
                        @forelse ($member->events as $event)
                            <span class="badge text-bg-light border">{{ $event->name }}</span>
                        @empty
                            <span class="text-secondary">Sem autorização</span>
                        @endforelse
                    </td>
                    <td><span class="badge {{ $member->open_tasks_count ? 'text-bg-warning' : 'text-bg-light border' }}">{{ $member->open_tasks_count }}</span></td>
                    <td><span class="badge {{ $member->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $member->status === 'active' ? 'Ativo' : 'Inativo' }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('admin.team.edit', $member) }}" class="btn btn-sm btn-outline-dark">Editar</a>
                        <form method="POST" action="{{ route('admin.team.destroy', $member) }}" class="d-inline" onsubmit="return confirm('Excluir este membro?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" data-loading-text="Excluindo...">Excluir</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-secondary py-4">Nenhum membro encontrado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $members->links() }}
</div>
@endsection
