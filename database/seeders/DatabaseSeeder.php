<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Setting;
use App\Models\User;
use App\Support\CurrentEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use LogicException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! in_array(app()->environment(), ['local', 'testing'], true)) {
            throw new LogicException('O DatabaseSeeder de demonstração só pode ser executado em local ou testing.');
        }

        $this->seedAdminUsers();

        $events = Event::query()->whereIn('slug', ['vida-vitoriosa', 'edd'])->get()->keyBy('slug');

        foreach ($events as $event) {
            app(CurrentEvent::class)->set($event);
            $this->seedSettings();
            $this->seedDefaultImages();
        }

        foreach (User::query()->where('role', 'admin')->get() as $user) {
            $user->events()->syncWithoutDetaching($events->mapWithKeys(fn (Event $event) => [
                $event->id => ['role' => 'admin', 'is_active' => true],
            ])->all());
        }

        $this->call(PrintFlowScenarioSeeder::class);
    }

    private function seedSettings(): void
    {
        foreach (Setting::seededDefaults() as $key => $value) {
            Setting::put($key, $value);
        }
    }

    private function seedDefaultImages(): void
    {
        $event = app(CurrentEvent::class)->get();

        if ($event->slug === 'edd') {
            $publicSource = public_path('images/events/edd/edd-public-banner.png');
            $headerSource = public_path('images/events/edd/edd-pdf-banner.png');
            $publicTarget = 'events/edd/settings/public-site-default.png';
            $headerTarget = 'events/edd/settings/pdf-header-default.png';

            if (file_exists($publicSource)) {
                Storage::disk('public')->put($publicTarget, file_get_contents($publicSource));
                Setting::put('public_site_image_path', $publicTarget);
            }

            if (file_exists($headerSource)) {
                Storage::disk('public')->put($headerTarget, file_get_contents($headerSource));
                Setting::put('pdf_header_image_path', $headerTarget);
            }

            return;
        }

        $headerSource = base_path('docs/assets/PDF.png');
        $publicSource = base_path('docs/assets/formulario.png');

        if (! file_exists($headerSource)) {
            $headerSource = base_path('ChatGPT Image 25 de mar. de 2026, 15_55_39.png');
        }

        if (! file_exists($publicSource)) {
            $publicSource = base_path('ChatGPT Image 25 de mar. de 2026, 15_55_39.png');
        }

        if (! file_exists($headerSource) || ! file_exists($publicSource)) {
            return;
        }

        $headerTarget = 'events/vida-vitoriosa/settings/pdf-header-default.png';
        $publicTarget = 'events/vida-vitoriosa/settings/public-site-default.png';

        $this->createHeaderCrop($headerSource, Storage::disk('public')->path($headerTarget));
        Storage::disk('public')->put($publicTarget, file_get_contents($publicSource));

        Setting::put('pdf_header_image_path', $headerTarget);
        Setting::put('public_site_image_path', $publicTarget);
    }

    private function seedAdminUsers(): void
    {
        $users = [
            ['email' => 'admin@vidavitoriosa.local', 'name' => 'Administrador', 'is_active' => true],
            ['email' => 'leolpm2@hotmail.com', 'name' => 'Leol P. M.', 'is_active' => true],
            ['email' => 'a_ariane@hotmail.com', 'name' => 'Ariane', 'is_active' => true],
            ['email' => 'coord@vidavitoriosa.local', 'name' => 'Coordenação', 'is_active' => true],
            ['email' => 'apoio@vidavitoriosa.local', 'name' => 'Apoio Administrativo', 'is_active' => true],
            ['email' => 'inativo@vidavitoriosa.local', 'name' => 'Usuário Inativo', 'is_active' => false],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => 'admin',
                    'is_active' => $user['is_active'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }

    private function createHeaderCrop(string $sourceImage, string $destinationPath): void
    {
        $directory = dirname($destinationPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (! extension_loaded('gd')) {
            copy($sourceImage, $destinationPath);

            return;
        }

        $info = getimagesize($sourceImage);

        if (! $info) {
            copy($sourceImage, $destinationPath);

            return;
        }

        [$width, $height, $type] = $info;
        $cropHeight = (int) round($height * 0.24);

        $source = match ($type) {
            IMAGETYPE_PNG => imagecreatefrompng($sourceImage),
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourceImage),
            default => null,
        };

        if (! $source) {
            copy($sourceImage, $destinationPath);

            return;
        }

        $crop = imagecreatetruecolor($width, $cropHeight);
        imagealphablending($crop, false);
        imagesavealpha($crop, true);
        $transparent = imagecolorallocatealpha($crop, 0, 0, 0, 127);
        imagefilledrectangle($crop, 0, 0, $width, $cropHeight, $transparent);
        imagecopyresampled($crop, $source, 0, 0, 0, 0, $width, $cropHeight, $width, $cropHeight);
        imagepng($crop, $destinationPath);

        imagedestroy($source);
        imagedestroy($crop);
    }
}
