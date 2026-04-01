<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\PdfBatch;
use App\Models\Setting;
use App\Models\Testimonial;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PdfController extends Controller
{
    public function index(): View
    {
        $participantsFilter = request()->string('participants_filter')->toString() ?: 'all';
        $participantName = request()->string('participant_name')->toString();

        $participantsQuery = Participant::query()->withCount([
            'testimonials',
            'testimonials as approved_testimonials_count' => fn ($query) => $query->where('status', 'approved'),
            'testimonials as approved_pending_testimonials_count' => fn ($query) => $query->where('status', 'approved')->where('is_pdf_generated', false),
            'testimonials as pending_testimonials_count' => fn ($query) => $query->where('status', '!=', 'approved'),
        ]);

        if ($participantsFilter === 'approved_pending') {
            $participantsQuery->whereHas('testimonials', fn ($query) => $query->where('status', 'approved')->where('is_pdf_generated', false));
        }

        if ($participantsFilter === 'approved') {
            $participantsQuery->whereHas('testimonials', fn ($query) => $query->where('status', 'approved'));
        }

        if ($participantsFilter === 'pending') {
            $participantsQuery->whereHas('testimonials', fn ($query) => $query->where('status', '!=', 'approved'));
        }

        if ($participantsFilter === 'without_testimonials') {
            $participantsQuery->whereDoesntHave('testimonials');
        }

        if ($participantName !== '') {
            $participantsQuery->where(function ($query) use ($participantName) {
                $query->where('name', 'like', '%' . $participantName . '%')
                    ->orWhere('display_name', 'like', '%' . $participantName . '%');
            });
        }

        return view('admin.pdf.index', [
            'participants' => $participantsQuery->orderBy('name')->get(),
            'participantsFilter' => $participantsFilter,
            'participantName' => $participantName,
            'batches' => PdfBatch::with(['participant', 'generatedBy'])->latest()->take(10)->get(),
        ]);
    }

    public function generate(Request $request, Participant $participant): RedirectResponse
    {
        if (! extension_loaded('gd')) {
            return back()->with('error', 'A extensão GD do PHP precisa estar habilitada para gerar PDFs com imagens.');
        }

        if (! is_dir(storage_path('fonts'))) {
            mkdir(storage_path('fonts'), 0777, true);
        }

        $validated = $request->validate([
            'mode' => ['required', 'in:only_new,full_regeneration'],
            'status_filter' => ['required', 'in:approved,all'],
        ]);

        $query = $participant->testimonials()->with('participant')->orderBy('created_at');

        if ($validated['status_filter'] === 'approved') {
            $query->where('status', 'approved');
        }

        if ($validated['mode'] === 'only_new') {
            $query->where('is_pdf_generated', false);
        }

        $testimonials = $query->get()->map(function (Testimonial $testimonial) {
            $testimonial->pdf_photo_local_path = $testimonial->photo_path
                ? $this->prepareImageForPdf(Storage::disk('public')->path($testimonial->photo_path))
                : null;
            $testimonial->pdf_message_html = $this->renderMessageForPdf($testimonial->message);

            return $testimonial;
        });

        if ($testimonials->isEmpty()) {
            return back()->with('error', 'Não há depoimentos disponíveis para gerar o PDF neste modo.');
        }

        $generatedAt = now();

        $batch = DB::transaction(function () use ($participant, $validated, $generatedAt) {
            return PdfBatch::create([
                'participant_id' => $participant->id,
                'generation_mode' => $validated['mode'] . ':' . $validated['status_filter'],
                'generated_by' => Auth::id(),
                'generated_at' => $generatedAt,
            ]);
        });

        $settings = $this->resolveSettings();

        $pdf = Pdf::loadView('pdf.participant', [
            'participant' => $participant,
            'testimonials' => $testimonials,
            'settings' => $settings,
            'batch' => $batch,
        ])->setPaper('a4', 'portrait');

        $fileName = Str::slug($participant->label, '-') . '_' . $generatedAt->format('Y-m-d_H-i-s') . '.pdf';
        $filePath = "pdf/participant-{$participant->id}/{$fileName}";
        Storage::disk('public')->put($filePath, $pdf->output());

        $batch->update([
            'file_path' => $filePath,
        ]);

        Testimonial::whereIn('id', $testimonials->pluck('id'))->update([
            'is_pdf_generated' => true,
            'pdf_generated_at' => now(),
            'pdf_batch_id' => $batch->id,
        ]);

        return redirect()
            ->route('admin.pdf.index')
            ->with('success', 'PDF gerado com sucesso. O arquivo está disponível para download.');
    }

    public function download(PdfBatch $batch)
    {
        abort_unless($batch->file_path && Storage::disk('public')->exists($batch->file_path), 404);

        return Storage::disk('public')->download($batch->file_path);
    }

    private function resolveSettings(): array
    {
        $defaults = Setting::seededDefaults();
        $settings = [];

        foreach ($defaults as $key => $value) {
            $settings[$key] = Setting::valueFor($key, $value);
        }

        $settings['public_site_image_url'] = $settings['public_site_image_path']
            ? '/storage/' . ltrim($settings['public_site_image_path'], '/')
            : null;

        $settings['public_site_image_local_path'] = $settings['public_site_image_path']
            ? str_replace('\\', '/', Storage::disk('public')->path($settings['public_site_image_path']))
            : null;

        $settings['pdf_header_image_url'] = $settings['pdf_header_image_path']
            ? '/storage/' . ltrim($settings['pdf_header_image_path'], '/')
            : null;

        $settings['pdf_header_image_local_path'] = $settings['pdf_header_image_path']
            ? str_replace('\\', '/', Storage::disk('public')->path($settings['pdf_header_image_path']))
            : null;

        return $settings;
    }

    private function prepareImageForPdf(string $sourcePath): string
    {
        if (! file_exists($sourcePath)) {
            return $sourcePath;
        }

        $info = @getimagesize($sourcePath);

        if (! $info) {
            return $sourcePath;
        }

        [$width, $height, $type] = $info;
        $source = $this->createImageFromFile($sourcePath, $type);

        if (! $source) {
            return $sourcePath;
        }

        $orientation = $type === IMAGETYPE_JPEG ? $this->readJpegOrientation($sourcePath) : 1;

        $normalized = $this->applyExifOrientation($source, $orientation);

        if ($normalized !== $source) {
            imagedestroy($source);
            $source = $normalized;
        }

        if ($orientation === 1) {
            imagedestroy($source);
            return $sourcePath;
        }

        $tempDir = storage_path('app/pdf-temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $extension = match ($type) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_WEBP => 'webp',
            default => 'png',
        };
        $tempPath = $tempDir . '/' . md5($sourcePath . '|' . filemtime($sourcePath) . '|' . $orientation) . '.' . $extension;

        match ($extension) {
            'jpg' => imagejpeg($source, $tempPath, 92),
            'webp' => function_exists('imagewebp') ? imagewebp($source, $tempPath, 92) : imagepng($source, $tempPath),
            default => imagepng($source, $tempPath),
        };
        imagedestroy($source);

        return $tempPath;
    }

    private function createImageFromFile(string $sourcePath, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($sourcePath) : null,
            default => null,
        };
    }

    private function applyExifOrientation($image, int $orientation)
    {
        if ($orientation === 1) {
            return $image;
        }

        switch ($orientation) {
            case 2:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                return $image;
            case 3:
                return imagerotate($image, 180, 0);
            case 4:
                imageflip($image, IMG_FLIP_VERTICAL);
                return $image;
            case 5:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                return imagerotate($image, 90, 0);
            case 6:
                return imagerotate($image, -90, 0);
            case 7:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                return imagerotate($image, -90, 0);
            case 8:
                return imagerotate($image, 90, 0);
            default:
                return $image;
        }
    }

    private function renderMessageForPdf(string $message): string
    {
        $paragraphs = preg_split("/\R{2,}/u", trim($message)) ?: [$message];

        return implode('', array_map(function (string $paragraph) {
            return '<p>' . $this->renderEmojiText(e(trim($paragraph))) . '</p>';
        }, $paragraphs));
    }

    private function renderEmojiText(string $escapedText): string
    {
        if ($escapedText === '') {
            return '';
        }

        preg_match_all('/\X/u', $escapedText, $matches);

        return implode('', array_map(function (string $cluster) {
            if ($this->isEmojiCluster($cluster)) {
                return $this->emojiImageTag($cluster);
            }

            return $cluster;
        }, $matches[0] ?? []));
    }

    private function isEmojiCluster(string $cluster): bool
    {
        return preg_match('/[\x{1F1E6}-\x{1FAFF}\x{2300}-\x{27BF}]/u', $cluster) === 1
            || str_contains($cluster, "\u{FE0F}")
            || preg_match('/\p{Extended_Pictographic}/u', $cluster) === 1;
    }

    private function emojiImageTag(string $cluster): string
    {
        $codepoints = $this->emojiCodepoints($cluster);

        if ($codepoints === '') {
            return $cluster;
        }

        $cacheDir = storage_path('app/pdf-emojis');

        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $cachePath = $cacheDir . '/' . $codepoints . '.png';

        if (! file_exists($cachePath)) {
            $url = 'https://cdnjs.cloudflare.com/ajax/libs/twemoji/14.0.2/72x72/' . $codepoints . '.png';

            try {
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    file_put_contents($cachePath, $response->body());
                }
            } catch (\Throwable $e) {
                // Fallback below.
            }
        }

        if (! file_exists($cachePath)) {
            return $cluster;
        }

        return '<img class="emoji-inline" src="' . str_replace('\\', '/', $cachePath) . '" alt="">';
    }

    private function emojiCodepoints(string $cluster): string
    {
        $chars = preg_split('//u', $cluster, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $codepoints = [];

        foreach ($chars as $char) {
            $codepoints[] = strtolower(dechex(mb_ord($char, 'UTF-8')));
        }

        return implode('-', $codepoints);
    }

    private function readJpegOrientation(string $path): int
    {
        $handle = @fopen($path, 'rb');

        if (! $handle) {
            return 1;
        }

        try {
            if (fread($handle, 2) !== "\xFF\xD8") {
                return 1;
            }

            while (! feof($handle)) {
                $markerPrefix = fread($handle, 1);

                if ($markerPrefix !== "\xFF") {
                    continue;
                }

                $marker = ord(fread($handle, 1));

                if (in_array($marker, [0xD9, 0xDA], true)) {
                    break;
                }

                $lengthBytes = fread($handle, 2);

                if (strlen($lengthBytes) !== 2) {
                    break;
                }

                $length = unpack('n', $lengthBytes)[1];
                $segment = fread($handle, $length - 2);

                if ($marker !== 0xE1 || ! str_starts_with($segment, "Exif\0\0")) {
                    continue;
                }

                $tiff = substr($segment, 6);

                if (strlen($tiff) < 8) {
                    return 1;
                }

                $littleEndian = substr($tiff, 0, 2) === 'II';
                $unpackShort = $littleEndian ? 'v' : 'n';
                $unpackLong = $littleEndian ? 'V' : 'N';

                $magic = unpack($unpackShort, substr($tiff, 2, 2))[1] ?? 0;

                if ($magic !== 42) {
                    return 1;
                }

                $ifdOffset = unpack($unpackLong, substr($tiff, 4, 4))[1] ?? 0;
                $ifdStart = $ifdOffset;

                if (strlen($tiff) < $ifdStart + 2) {
                    return 1;
                }

                $entries = unpack($unpackShort, substr($tiff, $ifdStart, 2))[1] ?? 0;
                $cursor = $ifdStart + 2;

                for ($i = 0; $i < $entries; $i++) {
                    if (strlen($tiff) < $cursor + 12) {
                        break;
                    }

                    $tag = unpack($unpackShort, substr($tiff, $cursor, 2))[1] ?? 0;

                    if ($tag === 0x0112) {
                        return unpack($unpackShort, substr($tiff, $cursor + 8, 2))[1] ?? 1;
                    }

                    $cursor += 12;
                }
            }
        } finally {
            fclose($handle);
        }

        return 1;
    }
}
