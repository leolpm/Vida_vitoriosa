<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Impressão')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fff;
            color: #111827;
        }

        .print-shell {
            padding: 24px;
        }

        .print-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .print-subtitle {
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .print-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-bottom: 1rem;
        }

        .print-meta .badge {
            font-weight: 600;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff;
            }

            .print-shell {
                padding: 0;
            }
        }
    </style>
</head>
<body>
<div class="print-shell">
    @yield('content')
</div>
<script>
window.addEventListener('load', () => {
    window.print();
});
</script>
</body>
</html>
