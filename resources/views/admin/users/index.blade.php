@extends('layouts.admin')

@section('title', 'Usuários administrativos')
@section('section', 'Acesso')
@section('page-title', 'Usuários administrativos')

@section('content')
<div class="card-surface p-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="section-eyebrow text-secondary mb-1">Controle interno</div>
            <h2 class="h5 mb-0">Usuários do painel</h2>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-gold">Novo usuário</a>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Status</th>
                <th>Último login</th>
                <th class="text-end">Ações</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td class="fw-semibold">{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $user->is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </td>
                    <td>{{ $user->last_login_at?->format('d/m/Y H:i') ?: '---' }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-dark">Editar</a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Excluir este usuário?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-secondary">Nenhum usuário cadastrado.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
@endsection
