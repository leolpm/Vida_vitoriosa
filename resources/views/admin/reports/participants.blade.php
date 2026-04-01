@extends('layouts.admin')

@section('title', 'Relatórios')
@section('section', 'Relatórios')
@section('page-title', 'Relatório de participantes')

@section('content')
<div class="card-surface report-toolbar p-4 mb-4">
    @include('admin.reports.partials.nav')

    <form method="GET" action="{{ route('admin.reports.participants') }}" class="row g-3 align-items-end">
        <div class="col-12 col-xl-5">
            <label class="form-label fw-semibold" for="participants_filter">Filtro de participantes</label>
            <select name="participants_filter" id="participants_filter" class="form-select" onchange="this.form.submit()">
                <option value="all" @selected($filter === 'all')>Todos os participantes</option>
                <option value="approved_pending" @selected($filter === 'approved_pending')>Aprovados sem PDF</option>
                <option value="approved" @selected($filter === 'approved')>Com depoimentos aprovados</option>
                <option value="pending" @selected($filter === 'pending')>Com depoimentos pendentes</option>
                <option value="without_testimonials" @selected($filter === 'without_testimonials')>Sem depoimentos</option>
            </select>
        </div>
        <div class="col-12 col-xl-7">
            <div class="d-flex flex-wrap gap-2 justify-content-xl-end">
                <a href="{{ route('admin.reports.participants.print', request()->query()) }}" target="_blank" class="btn btn-outline-dark btn-lg">Imprimir</a>
                <a href="{{ route('admin.reports.participants.excel', request()->query()) }}" class="btn btn-gold btn-lg">Excel</a>
            </div>
        </div>
    </form>
</div>

<div class="card-surface p-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <div class="section-eyebrow text-secondary mb-1">Lista</div>
            <h2 class="h5 mb-0">Participantes</h2>
        </div>
        <div class="text-secondary small">
            {{ $totalCount }} registro(s) encontrado(s)
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Nome</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($participants as $participant)
                    <tr>
                        <td class="fw-semibold">{{ $participant->name }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-secondary">Nenhum participante encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $participants->links() }}
    </div>
</div>
@endsection
