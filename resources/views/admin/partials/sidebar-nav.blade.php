<div>
    <div class="brand">Vida Vitoriosa</div>
    <div class="small text-white-50">Painel administrativo</div>
</div>

<nav class="admin-nav d-grid gap-1">
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="{{ route('admin.participants.index') }}" class="{{ request()->routeIs('admin.participants.*') ? 'active' : '' }}">
        <i class="bi bi-people"></i> Participantes
    </a>
    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
        <i class="bi bi-person-badge"></i> Usuários
    </a>
    <a href="{{ route('admin.testimonials.index') }}" class="{{ request()->routeIs('admin.testimonials.*') ? 'active' : '' }}">
        <i class="bi bi-chat-square-heart"></i> Depoimentos
    </a>
    <a href="{{ route('admin.reports.participants') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
        <i class="bi bi-graph-up"></i> Relatórios
    </a>
    <a href="{{ route('admin.pdf.index') }}" class="{{ request()->routeIs('admin.pdf.*') ? 'active' : '' }}">
        <i class="bi bi-file-earmark-pdf"></i> PDFs
    </a>
    <a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
        <i class="bi bi-sliders"></i> Configurações
    </a>
</nav>

<div class="mt-auto">
    <div class="small text-white-50 mb-2">Conectado como</div>
    <div class="fw-semibold text-white">{{ auth()->user()?->name }}</div>
    <div class="small text-white-50">{{ auth()->user()?->email }}</div>
    <form action="{{ route('admin.logout') }}" method="POST" class="mt-3">
        @csrf
        <button class="btn btn-outline-light w-100" type="submit">
            <i class="bi bi-box-arrow-right me-1"></i> Sair
        </button>
    </form>
</div>
