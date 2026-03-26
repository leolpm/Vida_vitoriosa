<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
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
            'login_code_expires_minutes' => ['required', 'integer', 'min:1', 'max:240'],
            'public_site_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'pdf_header_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        foreach ([
            'retreat_name',
            'retreat_location',
            'retreat_year',
            'pdf_footer_text',
            'login_code_expires_minutes',
        ] as $key) {
            Setting::put($key, $validated[$key] ?? '');
        }

        if ($request->hasFile('public_site_image')) {
            Setting::put('public_site_image_path', $request->file('public_site_image')->store('settings', 'public'));
        }

        if ($request->hasFile('pdf_header_image')) {
            Setting::put('pdf_header_image_path', $request->file('pdf_header_image')->store('settings', 'public'));
        }

        return redirect()->route('admin.settings.index')->with('success', 'Configurações atualizadas com sucesso.');
    }

    private function allSettings(): array
    {
        $defaults = Setting::seededDefaults();
        $settings = [];

        foreach ($defaults as $key => $value) {
            $settings[$key] = Setting::valueFor($key, $value);
        }

        $settings['public_site_image_url'] = $settings['public_site_image_path']
            ? '/storage/' . ltrim($settings['public_site_image_path'], '/')
            : null;

        $settings['pdf_header_image_url'] = $settings['pdf_header_image_path']
            ? '/storage/' . ltrim($settings['pdf_header_image_path'], '/')
            : null;

        return $settings;
    }
}
