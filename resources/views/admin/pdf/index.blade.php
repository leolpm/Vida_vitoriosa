@extends('layouts.admin')

@section('title', 'PDFs')
@section('section', 'Exportação')
@section('page-title', 'Gerar PDFs')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card-surface p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold" for="participants_filter">Filtro de participantes</label>
                    <select name="participants_filter" id="participants_filter" class="form-select">
                        <option value="all" @selected(request('participants_filter', 'all') === 'all')>Todos os participantes</option>
                        <option value="approved_pending" @selected(request('participants_filter') === 'approved_pending')>Aprovados sem PDF</option>
                        <option value="approved" @selected(request('participants_filter') === 'approved')>Com depoimentos aprovados</option>
                        <option value="pending" @selected(request('participants_filter') === 'pending')>Com depoimentos pendentes</option>
                        <option value="without_testimonials" @selected(request('participants_filter') === 'without_testimonials')>Sem depoimentos</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold" for="participant_name">Nome do participante</label>
                    <input
                        type="text"
                        name="participant_name"
                        id="participant_name"
                        class="form-control"
                        value="{{ request('participant_name') }}"
                        placeholder="Digite parte do nome"
                    >
                </div>
                <div class="col-12 col-md-4 col-xl-4">
                    <div class="small text-secondary mb-2">
                        Use os filtros para priorizar participantes com depoimentos aprovados sem PDF, apenas aprovados, apenas pendentes ou sem depoimentos.
                    </div>
                    <button class="btn btn-outline-dark w-100" type="submit">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12 col-xl-7">
        <div class="card-surface p-4">
            <div class="section-eyebrow text-secondary mb-1">Participantes</div>
            <h2 class="h5 mb-3">Gerar PDF por participante</h2>

            <div class="row g-3">
                @forelse ($participants as $participant)
                    <div class="col-12">
                        <div class="border rounded-4 p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div>
                                <div class="fw-semibold">{{ $participant->label }}</div>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge rounded-pill text-bg-light border text-dark" data-bs-toggle="tooltip" data-bs-title="Total de depoimentos cadastrados para este participante.">
                                        Total: {{ $participant->testimonials_count }}
                                    </span>
                                    <span class="badge rounded-pill text-bg-success" data-bs-toggle="tooltip" data-bs-title="Depoimentos com status Aprovado.">
                                        Aprovados: {{ $participant->approved_testimonials_count }}
                                    </span>
                                    <span class="badge rounded-pill text-bg-warning text-dark" data-bs-toggle="tooltip" data-bs-title="Depoimentos com status Aprovado que ainda não foram gerados em PDF.">
                                        Aprovados sem PDF: {{ $participant->approved_pending_testimonials_count }}
                                    </span>
                                    <span class="badge rounded-pill text-bg-secondary" data-bs-toggle="tooltip" data-bs-title="Depoimentos que não estão aprovados, como recebidos, revisados ou arquivados.">
                                        Pendentes: {{ $participant->pending_testimonials_count }}
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                                <div class="d-grid gap-2">
                                    <form action="{{ route('admin.pdf.generate', $participant) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="mode" value="only_new">
                                        <input type="hidden" name="status_filter" value="approved">
                                        <button class="btn btn-sm btn-gold w-100" type="submit">Novos aprovados</button>
                                    </form>
                                    <form action="{{ route('admin.pdf.generate', $participant) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="mode" value="full_regeneration">
                                        <input type="hidden" name="status_filter" value="approved">
                                        <button class="btn btn-sm btn-outline-dark w-100" type="submit">Regerar aprovados</button>
                                    </form>
                                </div>
                                <div class="d-grid gap-2">
                                    <form action="{{ route('admin.pdf.generate', $participant) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="mode" value="only_new">
                                        <input type="hidden" name="status_filter" value="all">
                                        <button class="btn btn-sm btn-secondary w-100" type="submit">Novos todos</button>
                                    </form>
                                    <form action="{{ route('admin.pdf.generate', $participant) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="mode" value="full_regeneration">
                                        <input type="hidden" name="status_filter" value="all">
                                        <button class="btn btn-sm btn-dark w-100" type="submit">Regerar todos</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-secondary">Nenhum participante encontrado.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card-surface p-4">
            <div class="section-eyebrow text-secondary mb-1">Lotes recentes</div>
            <h2 class="h5 mb-3">Histórico de exportação</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <tbody>
                    @forelse ($batches as $batch)
                        @php
                            $parts = explode(':', $batch->generation_mode);
                            $modeLabel = ($parts[0] ?? '') === 'only_new' ? 'novos' : 'regerar';
                            $statusLabel = ($parts[1] ?? 'all') === 'approved' ? 'aprovados' : 'todos';
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $batch->participant?->label }}</div>
                                <div class="small text-secondary">{{ $modeLabel }} / {{ $statusLabel }} • {{ $batch->generated_at?->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="text-end">
                                @if ($batch->file_path)
                                    <a href="{{ route('admin.pdf.download', $batch) }}" class="btn btn-sm btn-outline-dark">Baixar</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-secondary">Ainda não há lotes exportados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tooltipTriggers = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggers.forEach((trigger) => new bootstrap.Tooltip(trigger));
});
</script>
@endpush
