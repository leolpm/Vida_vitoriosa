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
            break-inside: auto;
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

        .testimonial-body {
            margin-top: 8px;
        }

        .testimonial-body::after {
            content: '';
            display: block;
            clear: both;
        }

        .testimonial-photo {
            float: right;
            width: 45%;
            max-width: 82mm;
            margin: 10mm 0 8mm 10mm;
            box-sizing: border-box;
        }

        .photo-frame {
            width: 100%;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #e5cfab;
            background: #fff;
            text-align: center;
        }

        .photo-frame img {
            width: 100%;
            height: auto;
            max-height: 110mm;
            object-fit: contain;
            display: block;
        }

        .photo-placeholder {
            width: 100%;
            min-height: 92mm;
            border-radius: 14px;
            border: 1px dashed #d8bf93;
            color: #9a8a76;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 16px;
            box-sizing: border-box;
            font-size: 11px;
            background: #fff;
        }

        .testimonial-message {
            font-size: 12.5px;
            line-height: 1.85;
            color: #2f241b;
            padding: 0 7mm 0 7mm;
            box-sizing: border-box;
        }

        .testimonial-message p {
            margin: 0 0 10px;
        }

        .emoji-inline {
            width: 1.05em;
            height: 1.05em;
            vertical-align: -0.16em;
        }

        .page-content {
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

            <div class="testimonial-body">
                <div class="testimonial-photo">
                    @if (($testimonial->pdf_photo_local_path ?? $photoPath) && file_exists($testimonial->pdf_photo_local_path ?? $photoPath))
                        <div class="photo-frame">
                            <img src="{{ $testimonial->pdf_photo_local_path ?? $photoPath }}" alt="Foto do depoimento">
                        </div>
                    @else
                        <div class="photo-placeholder">Espaço reservado para a foto do depoimento</div>
                    @endif
                </div>

                <div class="testimonial-message">
                    {!! $testimonial->pdf_message_html !!}
                </div>
            </div>
        </div>

        <div class="footer">
            <div>{{ $footerText }}</div>
        </div>
    </section>
@endforeach
</body>
</html>
