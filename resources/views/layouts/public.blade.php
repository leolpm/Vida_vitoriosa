<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #153243;
            --sand: #f5efe6;
            --rose: #b55b5b;
            --gold: #d4a24c;
            --deep: #0f1f2c;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(181, 91, 91, 0.12), transparent 28%),
                radial-gradient(circle at bottom right, rgba(212, 162, 76, 0.16), transparent 24%),
                linear-gradient(180deg, #fcfaf6 0%, #f3ede4 100%);
            color: var(--ink);
            min-height: 100vh;
        }

        .brand-title {
            font-family: 'Fraunces', serif;
            letter-spacing: .02em;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(21, 50, 67, 0.08);
            box-shadow: 0 20px 60px rgba(21, 50, 67, 0.12);
        }

        .hero-panel {
            border-radius: 2rem;
            overflow: hidden;
            min-height: 260px;
            background: linear-gradient(135deg, rgba(15, 31, 44, 0.92), rgba(40, 74, 98, 0.92));
            color: white;
            position: relative;
        }

        .hero-panel::after {
            content: '';
            position: absolute;
            inset: auto -15% -35% auto;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(212, 162, 76, 0.24);
            filter: blur(8px);
        }

        .section-eyebrow {
            text-transform: uppercase;
            letter-spacing: .24em;
            font-size: .72rem;
            color: #8d6b3f;
        }

        .btn-gold {
            --bs-btn-color: #fff;
            --bs-btn-bg: var(--gold);
            --bs-btn-border-color: var(--gold);
            --bs-btn-hover-bg: #bf8f35;
            --bs-btn-hover-border-color: #bf8f35;
        }

        .soft-shadow {
            box-shadow: 0 12px 40px rgba(21, 50, 67, 0.08);
        }

        .site-navbar .navbar-brand {
            min-width: 0;
        }

        .site-navbar .navbar-actions {
            display: flex;
            gap: .5rem;
            align-items: center;
        }

        @media (max-width: 767.98px) {
            .site-navbar .container {
                flex-direction: column;
                align-items: center;
                gap: .75rem;
            }

            .site-navbar .navbar-brand {
                justify-content: center;
                text-align: center;
            }

            .site-navbar .navbar-actions {
                justify-content: center;
            }

            .site-navbar .navbar-actions .btn,
            .site-navbar .navbar-actions form {
                width: 100%;
            }

            .site-navbar .navbar-actions .btn {
                display: inline-flex;
                justify-content: center;
            }

            .site-navbar .navbar-actions form {
                display: flex;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light py-3 site-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-3" href="{{ route('testimonials.create') }}">
            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-dark text-white" style="width: 42px; height: 42px;">
                <i class="bi bi-heart-fill"></i>
            </span>
            <span>
                <span class="d-block fw-bold">{{ config('app.name') }}</span>
                <small class="text-secondary">Retiro Vida Vitoriosa</small>
            </span>
        </a>
        <div class="navbar-actions">
            @auth
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-dark btn-sm">Painel</a>
                <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-dark btn-sm" type="submit">Sair</button>
                </form>
            @else
                <a href="{{ route('admin.login') }}" class="btn btn-outline-dark btn-sm">Área administrativa</a>
            @endauth
        </div>
    </div>
</nav>

<main class="pb-5">
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success border-0 soft-shadow">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger border-0 soft-shadow">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 soft-shadow">
                <strong>Verifique os campos abaixo.</strong>
            </div>
        @endif

        @yield('content')
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
