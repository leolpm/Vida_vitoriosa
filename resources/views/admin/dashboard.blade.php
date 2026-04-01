@extends('layouts.admin')

@section('title', 'Dashboard')
@section('section', 'Resumo geral')
@section('page-title', 'Dashboard')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 col-xl-6">
        <div class="card-surface p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="section-eyebrow text-secondary mb-1">Panorama</div>
                    <h2 class="h5 mb-1">Métricas operacionais</h2>
                    <p class="text-secondary mb-0">Indicadores gerais do retiro, cadastro e acesso ao painel.</p>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-md-2 g-3">
                <div class="col">
                    <div class="stat-card p-3 h-100" data-bs-toggle="tooltip" data-bs-title="Total de participantes cadastrados no sistema.">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-secondary small">Participantes</div>
                                <div class="fs-3 fw-bold">{{ $participantsCount }}</div>
                            </div>
                            <div class="stat-icon"><i class="bi bi-people"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card p-3 h-100" data-bs-toggle="tooltip" data-bs-title="Participantes com status ativo.">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-secondary small">Ativos</div>
                                <div class="fs-3 fw-bold">{{ $activeParticipantsCount }}</div>
                            </div>
                            <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card p-3 h-100" data-bs-toggle="tooltip" data-bs-title="Total de depoimentos cadastrados no sistema.">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-secondary small">Depoimentos</div>
                                <div class="fs-3 fw-bold">{{ $testimonialsCount }}</div>
                            </div>
                            <div class="stat-icon"><i class="bi bi-chat-square-heart"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card p-3 h-100" data-bs-toggle="tooltip" data-bs-title="Quantidade de usuários administrativos ativos com acesso ao painel.">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-secondary small">Usuários</div>
                                <div class="fs-3 fw-bold">{{ $usersCount }}</div>
                            </div>
                            <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card-surface p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="section-eyebrow text-secondary mb-1">Exportação</div>
                    <h2 class="h5 mb-1">Métricas de PDFs</h2>
                    <p class="text-secondary mb-0">Indicadores do fluxo de aprovação e geração de arquivos PDF.</p>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-md-2 g-3">
                <div class="col">
                    <div class="stat-card p-3 h-100" data-bs-toggle="tooltip" data-bs-title="Depoimentos com status Aprovado, independentemente de já terem PDF.">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-secondary small">Aprovados</div>
                                <div class="fs-3 fw-bold">{{ $approvedTestimonialsCount }}</div>
                            </div>
                            <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card p-3 h-100" data-bs-toggle="tooltip" data-bs-title="Depoimentos aprovados que ainda não foram exportados em PDF.">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-secondary small">Aprovados sem PDF</div>
                                <div class="fs-3 fw-bold">{{ $approvedWithoutPdfTestimonialsCount }}</div>
                            </div>
                            <div class="stat-icon"><i class="bi bi-file-earmark-excel"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card p-3 h-100" data-bs-toggle="tooltip" data-bs-title="Depoimentos que ainda não estão aprovados, como recebidos, revisados ou arquivados.">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-secondary small">Pendentes</div>
                                <div class="fs-3 fw-bold">{{ $pendingTestimonialsCount }}</div>
                            </div>
                            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card p-3 h-100" data-bs-toggle="tooltip" data-bs-title="Total de lotes de PDF já gerados no sistema.">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-secondary small">Lotes PDF</div>
                                <div class="fs-3 fw-bold">{{ $pdfBatchesCount }}</div>
                            </div>
                            <div class="stat-icon"><i class="bi bi-file-earmark-pdf"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-7">
        <div class="card-surface p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="section-eyebrow text-secondary mb-1">Atalhos</div>
                    <h2 class="h5 mb-0">Ações rápidas</h2>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.participants.create') }}" class="btn btn-gold">Novo participante</a>
                <a href="{{ route('admin.users.create') }}" class="btn btn-outline-dark">Novo usuário</a>
                <a href="{{ route('admin.testimonials.index') }}" class="btn btn-outline-dark">Ver depoimentos</a>
                <a href="{{ route('admin.pdf.index') }}" class="btn btn-outline-dark">Gerar PDF</a>
                <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-dark">Configurações</a>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-5">
        <div class="card-surface p-4 h-100">
            <div class="section-eyebrow text-secondary mb-1">Últimos lotes</div>
            <h2 class="h5 mb-3">PDFs recentes</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <tbody>
                    @forelse ($recentBatches as $batch)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $batch->participant?->label }}</div>
                                <div class="small text-secondary">{{ $batch->generation_mode }}</div>
                            </td>
                            <td class="text-end">
                                @if ($batch->file_path)
                                    <a href="{{ route('admin.pdf.download', $batch) }}" class="btn btn-sm btn-outline-dark">Baixar</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-secondary">Nenhum lote gerado ainda.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-12 col-xl-6">
        <div class="card-surface p-4 h-100">
            <div class="section-eyebrow text-secondary mb-1">Fluxo</div>
            <h2 class="h5 mb-3">Depoimentos recentes</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Remetente</th>
                        <th>Participante</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentTestimonials as $testimonial)
                        <tr>
                            <td>{{ $testimonial->sender_name }}</td>
                            <td>{{ $testimonial->participant?->label }}</td>
                            <td><span class="badge {{ $testimonial->status_badge_class }}">{{ $testimonial->status_label }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-secondary">Ainda não há depoimentos cadastrados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="card-surface p-4 h-100">
            <div class="section-eyebrow text-secondary mb-1">Resumo</div>
            <h2 class="h5 mb-3">Atenção operacional</h2>
            <p class="text-secondary mb-0">
                O painel foi desenhado para acompanhar o fluxo do retiro com foco em depoimentos, geração de PDFs e gestão de usuários administrativos sem dependência de senha.
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        new bootstrap.Tooltip(element);
    });
});
</script>
@endpush
