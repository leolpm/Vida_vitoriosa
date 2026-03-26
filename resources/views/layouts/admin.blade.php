<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Painel Administrativo')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar: #101b27;
            --sidebar-accent: #17344a;
            --panel-bg: #f5f7fb;
            --card-bg: #ffffff;
            --accent: #c58f3a;
            --accent-2: #8d5a4b;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(180deg, #f7f8fb 0%, #eef2f7 100%);
            color: #10202c;
        }

        .admin-shell {
            min-height: 100vh;
            display: flex;
            background: linear-gradient(180deg, #f7f8fb 0%, #eef2f7 100%);
        }

        .admin-sidebar {
            width: 288px;
            background: linear-gradient(180deg, var(--sidebar) 0%, #0b121b 100%);
            color: rgba(255, 255, 255, 0.82);
            padding: 1.5rem;
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .admin-sidebar .brand {
            font-family: 'Fraunces', serif;
            color: #fff;
            font-size: 1.4rem;
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            gap: .75rem;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.84);
            padding: .85rem 1rem;
            border-radius: 1rem;
            transition: all .2s ease;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            transform: translateX(2px);
        }

        .admin-main {
            flex: 1;
            padding: 1.25rem;
        }

        .admin-topbar {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(16, 27, 39, 0.06);
            box-shadow: 0 14px 36px rgba(16, 27, 39, 0.08);
            border-radius: 1.5rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
            backdrop-filter: blur(14px);
        }

        .card-surface {
            background: var(--card-bg);
            border: 1px solid rgba(16, 27, 39, 0.06);
            box-shadow: 0 18px 40px rgba(16, 27, 39, 0.07);
            border-radius: 1.5rem;
        }

        .stat-card {
            border-radius: 1.5rem;
            border: 1px solid rgba(16, 27, 39, 0.06);
            box-shadow: 0 14px 32px rgba(16, 27, 39, 0.06);
            overflow: hidden;
            background: linear-gradient(135deg, #fff 0%, #fbfcfe 100%);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(197, 143, 58, 0.16), rgba(141, 90, 75, 0.16));
            color: #6f4b1d;
        }

        .badge-soft {
            background: rgba(197, 143, 58, 0.12);
            color: #815e1f;
        }

        .btn-gold {
            --bs-btn-color: #fff;
            --bs-btn-bg: var(--accent);
            --bs-btn-border-color: var(--accent);
            --bs-btn-hover-bg: #b27926;
            --bs-btn-hover-border-color: #b27926;
        }

        .section-eyebrow {
            text-transform: uppercase;
            letter-spacing: .22em;
            font-size: .72rem;
        }

        .admin-offcanvas {
            background: linear-gradient(180deg, var(--sidebar) 0%, #0b121b 100%);
            color: rgba(255, 255, 255, 0.82);
        }

        .admin-menu-toggle {
            display: none;
        }

        @media (max-width: 991px) {
            .admin-shell {
                flex-direction: column;
            }

            .admin-sidebar {
                display: none;
            }

            .admin-main {
                padding: .9rem;
            }

            .admin-topbar {
                padding: .9rem 1rem;
                margin-bottom: 1rem;
            }

            .admin-menu-toggle {
                display: inline-flex;
                align-items: center;
                gap: .4rem;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
@php($currentUser = auth()->user())
<div class="admin-shell">
    <aside class="admin-sidebar d-none d-lg-flex">
        @include('admin.partials.sidebar-nav')
    </aside>

    <main class="admin-main">
        <div class="admin-topbar d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-dark admin-menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebarMobile" aria-controls="adminSidebarMobile">
                    <i class="bi bi-list"></i>
                    <span>Menu</span>
                </button>
                <div>
                    <div class="section-eyebrow text-uppercase small text-secondary">@yield('section', 'Administração')</div>
                    <h1 class="h4 mb-0">@yield('page-title', 'Painel administrativo')</h1>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('testimonials.create') }}" class="btn btn-outline-dark">Ver site público</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success border-0 card-surface">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger border-0 card-surface">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 card-surface">
                <strong>Corrija os erros do formulário e tente novamente.</strong>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<div class="offcanvas offcanvas-start admin-offcanvas text-white" tabindex="-1" id="adminSidebarMobile" aria-labelledby="adminSidebarMobileLabel">
    <div class="offcanvas-header border-bottom border-white border-opacity-10">
        <h5 class="offcanvas-title brand" id="adminSidebarMobileLabel">Vida Vitoriosa</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column gap-4">
        @include('admin.partials.sidebar-nav')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
