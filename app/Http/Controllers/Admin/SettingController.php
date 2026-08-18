<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\PdfBatch;
use App\Models\PrintFlow;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Support\CurrentEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(private readonly CurrentEvent $currentEvent) {}

    public function index(): View
    {
        return view('admin.settings.index', [
            'settings' => $this->allSettings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'retreat_name' => ['required', 'string', 'max:255'],
            'retreat_location' => ['required', 'string', 'max:255'],
            'retreat_year' => ['required', 'string', 'max:20'],
            'pdf_footer_text' => ['nullable', 'string', 'max:255'],
            'testimonials_closes_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'recipient_term' => ['required', 'string', 'max:80'],
            'form_title' => ['required', 'string', 'max:255'],
            'form_intro' => ['required', 'string', 'max:1000'],
            'surprise_title' => ['required', 'string', 'max:255'],
            'surprise_text' => ['required', 'string', 'max:1000'],
            'relationships_text' => ['required', 'string', 'max:2000'],
            'login_code_expires_minutes' => ['required', 'integer', 'min:1', 'max:240'],
            'print_flow_global_task_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'print_flow_link_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'print_flow_access_limit' => ['required', 'integer', 'min:1', 'max:20'],
            'print_flow_min_testimonials' => ['required', 'integer', 'min:1', 'max:100'],
            'public_site_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'pdf_header_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        foreach ([
            'retreat_name',
            'retreat_location',
            'retreat_year',
            'pdf_footer_text',
            'testimonials_closes_at',
            'recipient_term',
            'form_title',
            'form_intro',
            'surprise_title',
            'surprise_text',
            'login_code_expires_minutes',
            'print_flow_global_task_limit',
            'print_flow_link_minutes',
            'print_flow_access_limit',
            'print_flow_min_testimonials',
        ] as $key) {
            Setting::put($key, $validated[$key] ?? '');
        }

        $relationships = collect(preg_split('/[\r\n,;]+/u', $validated['relationships_text']) ?: [])
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
        Setting::put('relationships_json', json_encode($relationships, JSON_UNESCAPED_UNICODE));

        if ($request->hasFile('public_site_image')) {
            Setting::put('public_site_image_path', $request->file('public_site_image')->store(
                'events/'.$this->currentEvent->get()->slug.'/settings',
                'public'
            ));
        }

        if ($request->hasFile('pdf_header_image')) {
            Setting::put('pdf_header_image_path', $request->file('pdf_header_image')->store(
                'events/'.$this->currentEvent->get()->slug.'/settings',
                'public'
            ));
        }

        return redirect()->route('admin.settings.index')->with('success', 'Configurações atualizadas com sucesso.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $confirmation = 'RESETAR '.mb_strtoupper($this->currentEvent->get()->name, 'UTF-8');

        $request->validate([
            'confirmation' => ['required', 'string', Rule::in([$confirmation])],
        ]);

        DB::transaction(function (): void {
            PrintFlow::query()->delete();
            Testimonial::query()->delete();
            PdfBatch::query()->delete();
            Participant::query()->delete();
        });

        $this->purgeResetArtifacts();

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Os dados do evento '.$this->currentEvent->get()->name.' foram apagados com sucesso.');
    }

    private function allSettings(): array
    {
        $defaults = Setting::seededDefaults();
        $settings = [];

        foreach ($defaults as $key => $value) {
            $settings[$key] = Setting::valueFor($key, $value);
        }

        $settings['public_site_image_url'] = $settings['public_site_image_path']
            ? '/storage/'.ltrim($settings['public_site_image_path'], '/')
            : null;

        $settings['pdf_header_image_url'] = $settings['pdf_header_image_path']
            ? '/storage/'.ltrim($settings['pdf_header_image_path'], '/')
            : null;

        if ($this->currentEvent->get()->slug === 'edd') {
            if (! $settings['public_site_image_path'] || ! Storage::disk('public')->exists($settings['public_site_image_path'])) {
                $settings['public_site_image_url'] = asset('images/events/edd/edd-public-banner.png');
            }

            if (! $settings['pdf_header_image_path'] || ! Storage::disk('public')->exists($settings['pdf_header_image_path'])) {
                $settings['pdf_header_image_url'] = asset('images/events/edd/edd-pdf-banner.png');
            }
        }

        $settings['relationships_text'] = implode(PHP_EOL, Setting::relationships());
        $settings['reset_confirmation'] = 'RESETAR '.mb_strtoupper($this->currentEvent->get()->name, 'UTF-8');

        return $settings;
    }

    private function purgeResetArtifacts(): void
    {
        Storage::disk('public')->deleteDirectory('events/'.$this->currentEvent->get()->slug.'/testimonials');
        Storage::disk('public')->deleteDirectory('events/'.$this->currentEvent->get()->slug.'/pdf');

        if ($this->currentEvent->get()->slug === 'vida-vitoriosa') {
            Storage::disk('public')->deleteDirectory('testimonials');
            Storage::disk('public')->deleteDirectory('pdf');
            Storage::disk('public')->deleteDirectory('tmp');
        }
    }
}
