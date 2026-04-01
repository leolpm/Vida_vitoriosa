@extends('layouts.admin')

@section('title', 'Relatórios')
@section('section', 'Relatórios')
@section('page-title', 'Relatório de depoimentos')

@section('content')
<div class="card-surface report-toolbar p-4 mb-4">
    @include('admin.reports.partials.nav')

    <form method="GET" action="{{ route('admin.reports.testimonials') }}" class="row g-3 align-items-end">
        <div class="col-12 col-xl-3">
            <label class="form-label fw-semibold" for="status">Status</label>
            <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                <option value="all" @selected($status === 'all')>Todos</option>
                <option value="received" @selected($status === 'received')>Recebido</option>
                <option value="reviewed" @selected($status === 'reviewed')>Revisado</option>
                <option value="approved" @selected($status === 'approved')>Aprovado</option>
                <option value="archived" @selected($status === 'archived')>Arquivado</option>
            </select>
        </div>
        <div class="col-12 col-xl-3">
            <label class="form-label fw-semibold" for="generated">PDF gerado</label>
            <select name="generated" id="generated" class="form-select" onchange="this.form.submit()">
                <option value="all" @selected($generated === 'all')>Todos</option>
                <option value="yes" @selected($generated === 'yes')>Sim</option>
                <option value="no" @selected($generated === 'no')>Não</option>
            </select>
        </div>
        <div class="col-12 col-xl-6">
            <div class="d-flex flex-wrap gap-2 justify-content-xl-end">
                <a href="{{ route('admin.reports.testimonials.print', request()->query()) }}" target="_blank" class="btn btn-outline-dark btn-lg">Imprimir</a>
                <a href="{{ route('admin.reports.testimonials.excel', request()->query()) }}" class="btn btn-gold btn-lg">Excel</a>
            </div>
        </div>
    </form>
</div>

<div class="card-surface p-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <div class="section-eyebrow text-secondary mb-1">Lista</div>
            <h2 class="h5 mb-0">Depoimentos</h2>
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
                    <th>Telefone</th>
                    <th>Participante</th>
                    <th>Status</th>
                    <th>PDF gerado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($testimonials as $testimonial)
                    <tr>
                        <td class="fw-semibold">{{ $testimonial->sender_name }}</td>
                        <td>{{ $testimonial->phone ?: '---' }}</td>
                        <td>{{ $testimonial->participant?->label ?: '---' }}</td>
                        <td><span class="badge {{ $testimonial->status_badge_class }}">{{ $testimonial->status_label }}</span></td>
                        <td>
                            <span class="badge {{ $testimonial->is_pdf_generated ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ $testimonial->is_pdf_generated ? 'Sim' : 'Não' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-secondary">Nenhum depoimento encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $testimonials->links() }}
    </div>
</div>
@endsection
