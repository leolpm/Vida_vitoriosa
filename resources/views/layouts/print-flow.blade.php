<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Fluxo de Impressão')</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon-atitude.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --flow-accent: {{ $currentEvent->slug === 'edd' ? '#1559c7' : '#c58f3a' }};
            --flow-deep: {{ $currentEvent->slug === 'edd' ? '#071e57' : '#10202c' }};
            --flow-soft: {{ $currentEvent->slug === 'edd' ? '#e9f2ff' : '#faf3e8' }};
        }
        body {
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #10202c;
            background:
                radial-gradient(circle at 8% 8%, color-mix(in srgb, var(--flow-accent) 17%, transparent), transparent 30%),
                linear-gradient(180deg, #f7f9fc 0%, #eef3f8 100%);
        }
        .flow-shell { max-width: 1120px; margin: 0 auto; padding: 1.25rem; }
        .flow-brand, .flow-card {
            background: rgba(255,255,255,.94);
            border: 1px solid rgba(16,32,44,.08);
            box-shadow: 0 18px 45px rgba(16,32,44,.08);
            border-radius: 1.5rem;
        }
        .flow-card { padding: 1.5rem; }
        .flow-mark {
            width: 48px; height: 48px; border-radius: 1rem; display: inline-flex;
            align-items: center; justify-content: center; color: #fff;
            background: linear-gradient(135deg, var(--flow-accent), var(--flow-deep));
        }
        .flow-eyebrow { text-transform: uppercase; letter-spacing: .2em; font-size: .7rem; color: var(--flow-accent); font-weight: 800; }
        .flow-title { font-family: 'Fraunces', serif; }
        .flow-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: .65rem; }
        .flow-step { padding: .8rem 1rem; border-radius: 1rem; background: #edf1f5; color: #77818b; font-size: .83rem; font-weight: 700; }
        .flow-step.active { background: var(--flow-accent); color: #fff; }
        .flow-step.done { background: var(--flow-soft); color: var(--flow-deep); }
        .btn-flow { --bs-btn-color:#fff; --bs-btn-bg:var(--flow-accent); --bs-btn-border-color:var(--flow-accent); --bs-btn-hover-color:#fff; --bs-btn-hover-bg:var(--flow-deep); --bs-btn-hover-border-color:var(--flow-deep); }
        @media (max-width: 767px) {
            .flow-shell { padding: .8rem; }
            .flow-card { padding: 1rem; border-radius: 1.2rem; }
            .flow-steps { grid-template-columns: 1fr; }
            .flow-brand .event-copy { text-align: center; width: 100%; }
        }
    </style>
    @stack('styles')
</head>
<body class="event-{{ $currentEvent->slug }}">
<div class="flow-shell">
    <header class="flow-brand p-3 p-md-4 mb-4 d-flex align-items-center gap-3 flex-wrap">
        <div class="flow-mark"><i class="bi bi-printer-fill"></i></div>
        <div class="event-copy">
            <div class="flow-eyebrow">{{ $currentEvent->name }}</div>
            <div class="h5 mb-0">Fluxo de Impressão</div>
        </div>
    </header>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4">{{ session('error') }}</div>
    @endif

    @yield('content')
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
