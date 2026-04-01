@push('styles')
<style>
    .report-switcher {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
        margin-bottom: 1.25rem;
    }

    .report-switcher .nav-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        min-height: 2.9rem;
        padding: .85rem 1.15rem;
        border-radius: .95rem;
        border: 1px solid rgba(16, 27, 39, 0.14);
        background: #fff;
        color: #10202c;
        font-weight: 700;
        box-shadow: 0 8px 22px rgba(16, 27, 39, 0.06);
        transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, color .18s ease;
    }

    .report-switcher .nav-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 26px rgba(16, 27, 39, 0.10);
    }

    .report-switcher .nav-link.active {
        background: linear-gradient(180deg, #d4a24c 0%, #c58f3a 100%);
        border-color: #c58f3a;
        color: #fff;
        box-shadow: 0 12px 26px rgba(197, 143, 58, 0.24);
    }

    .report-toolbar {
        border: 1px solid rgba(16, 27, 39, 0.06);
        border-radius: 1.35rem;
        background: linear-gradient(180deg, rgba(255,255,255,.92), rgba(248,250,253,.92));
        box-shadow: 0 14px 34px rgba(16, 27, 39, 0.06);
    }
</style>
@endpush

<nav class="report-switcher" aria-label="Alternar relatórios">
    <a href="{{ route('admin.reports.participants') }}" class="nav-link {{ request()->routeIs('admin.reports.participants*') ? 'active' : '' }}">
        <i class="bi bi-people"></i>
        Participantes
    </a>
    <a href="{{ route('admin.reports.testimonials') }}" class="nav-link {{ request()->routeIs('admin.reports.testimonials*') ? 'active' : '' }}">
        <i class="bi bi-chat-square-heart"></i>
        Depoimentos
    </a>
</nav>
