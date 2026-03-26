@php
    use Illuminate\Support\Facades\Storage;

    $headerImage = $settings['pdf_header_image_local_path'] ?? null;
    $footerText = $settings['pdf_footer_text'] ?? 'Vida Vitoriosa';
    $scriptFontData = base64_encode(file_get_contents(storage_path('app/fonts/segoesc.ttf')));
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @font-face {
            font-family: 'VidaScript';
            src: url('data:font/truetype;charset=utf-8;base64,{{ $scriptFontData }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @page {
            margin: 10mm 12mm 14mm 12mm;
        }

        html, body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #2d241c;
            font-size: 12px;
            line-height: 1.6;
            background: #fff;
        }

        .page {
            position: relative;
            height: 273mm;
            overflow: hidden;
            break-inside: avoid;
        }

        .header {
            margin-bottom: 8px;
        }

        .header img {
            width: 100%;
            display: block;
            border-radius: 0;
            height: auto;
        }

        .topline {
            font-size: 8.5px;
            letter-spacing: .30em;
            color: #8d6d45;
            text-transform: uppercase;
            margin: 6px 0 2px;
        }

        .participant-name {
            font-family: 'VidaScript', 'Segoe Script', 'Brush Script MT', cursive;
            font-size: 46px;
            font-weight: 400;
            line-height: 1;
            text-align: center;
            margin: 10px 0 4px;
            color: #2f251d;
        }

        .meta-line {
            display: flex;
            justify-content: center;
            align-items: baseline;
            gap: 10px;
            text-align: center;
            font-size: 12px;
            color: #8d6d45;
            text-transform: uppercase;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .meta-line .relationship-label {
            letter-spacing: .24em;
        }

        .meta-line .sender-name {
            letter-spacing: .14em;
            color: #6f5d49;
        }

        .meta-line .separator {
            color: #c7a97b;
            letter-spacing: 0;
        }

        .rule {
            width: 72px;
            height: 1px;
            margin: 8px auto 10px;
            background: #d2b58b;
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .message-column {
            font-size: 12.5px;
            line-height: 1.85;
            color: #2f241b;
            padding: 0 8mm 0 7mm;
            box-sizing: border-box;
        }

        .message-column p {
            margin: 0 0 10px;
        }

        .message-cell {
            width: 66%;
            vertical-align: top;
            padding-right: 10px;
        }

        .photo-cell {
            width: 33%;
            vertical-align: top;
            padding-left: 5mm;
            padding-right: 9mm;
            box-sizing: border-box;
        }

        .photo-column {
            padding-top: 4px;
        }

        .photo-frame {
            width: 100%;
            height: 196px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e5cfab;
            background: #fff;
            text-align: center;
        }

        .photo-frame img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .emoji-inline {
            width: 1.05em;
            height: 1.05em;
            vertical-align: -0.16em;
        }

        .photo-placeholder {
            width: 100%;
            height: 196px;
            border-radius: 12px;
            border: 1px dashed #d8bf93;
            color: #9a8a76;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 16px;
            box-sizing: border-box;
            font-size: 11px;
        }

        .page-content {
            min-height: 100%;
            padding-bottom: 20mm;
            box-sizing: border-box;
        }

        .footer {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            margin-top: 0;
            padding-top: 8px;
            border-top: 1px solid #d9c39a;
            color: #7e7467;
            font-size: 8.5px;
            letter-spacing: .16em;
            text-transform: uppercase;
            text-align: center;
        }

        .page-number {
            position: absolute;
            right: 0;
            top: 8px;
            color: #7e7467;
        }
    </style>
</head>
<body>
@foreach ($testimonials as $testimonial)
    @php
        $photoPath = $testimonial->photo_path ? str_replace('\\', '/', Storage::disk('public')->path($testimonial->photo_path)) : null;
        $relationshipLabel = $testimonial->relationship === 'Outro'
            ? ($testimonial->relationship_other ?: 'Outro')
            : $testimonial->relationship;
    @endphp
    <section class="page" style="{{ $loop->last ? '' : 'page-break-after: always;' }}">
        <div class="page-content">
            <div class="header">
                @if ($headerImage && file_exists($headerImage))
                    <img src="{{ $headerImage }}" alt="Cabeçalho do retiro">
                @endif
            </div>

            @unless ($headerImage && file_exists($headerImage))
                <div class="topline" style="text-align:center; margin-top: 0;">{{ $settings['retreat_name'] }} | {{ $settings['retreat_location'] }} | {{ $settings['retreat_year'] }}</div>
            @endunless
            <div class="participant-name">{{ $participant->label }}</div>
            <div class="rule"></div>
            <div class="meta-line">
                <span class="relationship-label">{{ $relationshipLabel }}</span>
                <span class="separator">•</span>
                <span class="sender-name">{{ $testimonial->sender_name }}</span>
            </div>

            <table class="content-table">
                <tr>
                    <td class="message-cell">
                        <div class="message-column">
                            {!! $testimonial->pdf_message_html !!}
                        </div>
                    </td>
                    <td class="photo-cell">
                        <div class="photo-column">
                            @if (($testimonial->pdf_photo_local_path ?? $photoPath) && file_exists($testimonial->pdf_photo_local_path ?? $photoPath))
                                <div class="photo-frame">
                                    <img src="{{ $testimonial->pdf_photo_local_path ?? $photoPath }}" alt="Foto do depoimento">
                                </div>
                            @else
                                <div class="photo-placeholder">Espaço reservado para a foto do depoimento</div>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <div>{{ $footerText }}</div>
            <div class="page-number">{{ $loop->iteration }}</div>
        </div>
    </section>
@endforeach
</body>
</html>
