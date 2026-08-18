@php
    use Illuminate\Support\Facades\Storage;
    $headerUrl = $settings['pdf_header_image_path'] ? '/storage/'.ltrim($settings['pdf_header_image_path'], '/') : null;
    if ($currentEvent->slug === 'edd' && (! $settings['pdf_header_image_path'] || ! Storage::disk('public')->exists($settings['pdf_header_image_path']))) {
        $headerUrl = asset('images/events/edd/edd-pdf-banner.png');
    }
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Impressão · {{ $flow->participant->label }}</title>
    <style>
        @page { size:A4 portrait; margin:0; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Arial,sans-serif; color:#17212b; background:#edf1f5; }
        .print-toolbar { max-width:1100px; margin:20px auto; padding:20px; background:#fff; border-radius:18px; box-shadow:0 12px 30px rgba(0,0,0,.08); }
        .print-summary { display:flex; gap:20px; flex-wrap:wrap; align-items:center; justify-content:space-between; }
        .print-button { border:0; border-radius:10px; background:#17212b; color:#fff; padding:12px 18px; font-weight:700; cursor:pointer; }
        .letter-pages { display:grid; gap:18px; justify-content:center; padding:0 0 30px; }
        .print-page { width:210mm; min-height:297mm; background:#fff; padding:10mm 13mm 14mm; position:relative; break-after:page; overflow:hidden; }
        .print-page:last-child { break-after:auto; }
        .event-header { width:100%; height:49mm; object-fit:cover; object-position:center; display:block; margin-bottom:7mm; }
        .event-title { text-transform:uppercase; letter-spacing:.25em; font-size:9pt; color:#7b8791; text-align:center; }
        .participant-name { font-family:Georgia,serif; font-style:italic; font-size:32pt; text-align:center; margin:3mm 0; }
        .letter-meta { text-align:center; text-transform:uppercase; letter-spacing:.15em; font-size:9pt; color:#68737c; margin-bottom:8mm; }
        .photo { float:right; width:72mm; max-height:105mm; object-fit:contain; margin:2mm 0 7mm 9mm; border:1px solid #d7dce0; border-radius:10px; }
        .message { font-size:11.5pt; line-height:1.7; white-space:pre-wrap; overflow-wrap:anywhere; }
        .continuation { color:#7b8791; text-transform:uppercase; letter-spacing:.15em; font-size:8pt; margin-bottom:6mm; }
        .footer { position:absolute; left:13mm; right:13mm; bottom:7mm; border-top:1px solid #d8dde2; padding-top:3mm; text-align:center; text-transform:uppercase; letter-spacing:.14em; font-size:7.5pt; color:#7b8791; }
        .empty { width:210mm; min-height:160mm; margin:auto; background:#fff; display:flex; align-items:center; justify-content:center; text-align:center; padding:20mm; }
        @media print { body { background:#fff; } .print-toolbar { display:none!important; } .letter-pages { display:block; padding:0; } .print-page { box-shadow:none; } }
        @media screen and (max-width:900px) { .print-page { transform-origin:top left; } .letter-pages { overflow:auto; justify-content:start; } }
    </style>
</head>
<body>
<div class="print-toolbar">
    <div class="print-summary">
        <div><strong>{{ $flow->participant->label }}</strong><br><span>{{ count($pages['letters']) }} carta(s) · {{ $pages['total_pages'] }} página(s)</span></div>
        <div>
            @foreach($pages['letters'] as $letter)<span style="display:inline-block;margin-right:12px">{{ $letter['testimonial']->sender_name }}: {{ $letter['page_count'] }} pág.</span>@endforeach
        </div>
        <button class="print-button" type="button" onclick="window.print()">Imprimir</button>
    </div>
</div>

@if($pages['letters'] === [])
    <div class="empty"><div><h1>Nenhuma carta aprovada</h1><p>Volte ao fluxo e revise as decisões antes de imprimir.</p></div></div>
@else
<main class="letter-pages">
@foreach($pages['letters'] as $letter)
    @foreach($letter['segments'] as $segment)
        <section class="print-page">
            @if($loop->first && $headerUrl)<img src="{{ $headerUrl }}" class="event-header" alt="Identidade do evento">@endif
            @if($loop->first)
                <div class="event-title">{{ $settings['retreat_name'] }} · {{ $settings['retreat_year'] }}</div>
                <div class="participant-name">{{ $flow->participant->label }}</div>
                <div class="letter-meta">{{ $letter['testimonial']->relationship }} · {{ $letter['testimonial']->sender_name }}</div>
                @if($letter['testimonial']->photo_url)<img src="{{ $letter['testimonial']->photo_url }}" class="photo" alt="Foto da carta">@endif
            @else
                <div class="continuation">Continuação da carta de {{ $letter['testimonial']->sender_name }}</div>
            @endif
            <div class="message">{{ $segment }}</div>
            <div class="footer">{{ $settings['pdf_footer_text'] }}</div>
        </section>
    @endforeach
@endforeach
</main>
@endif
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 350));</script>
</body>
</html>
