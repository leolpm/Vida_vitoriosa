<?php

namespace Database\Seeders;

use App\Models\Participant;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedDefaultImages();
        $this->seedAdminUser();
        $this->seedParticipants();
        $this->seedAdditionalAdminUsers();
        $this->seedTestimonials();
    }

    private function seedSettings(): void
    {
        foreach (Setting::seededDefaults() as $key => $value) {
            Setting::put($key, $value);
        }
    }

    private function seedDefaultImages(): void
    {
        $headerSource = base_path('doc/PDF.png');
        $publicSource = base_path('doc/formulario.png');

        if (! file_exists($headerSource)) {
            $headerSource = base_path('ChatGPT Image 25 de mar. de 2026, 15_55_39.png');
        }

        if (! file_exists($publicSource)) {
            $publicSource = base_path('ChatGPT Image 25 de mar. de 2026, 15_55_39.png');
        }

        if (! file_exists($headerSource) || ! file_exists($publicSource)) {
            return;
        }

        $this->createHeaderCrop($headerSource, Storage::disk('public')->path('settings/pdf-header-default.png'));

        Storage::disk('public')->put(
            'settings/public-site-default.png',
            file_get_contents($publicSource)
        );

        Setting::put('pdf_header_image_path', 'settings/pdf-header-default.png');
        Setting::put('public_site_image_path', 'settings/public-site-default.png');
    }

    private function seedAdminUser(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@vidavitoriosa.local'],
            [
                'name' => 'Administrador',
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }

    private function seedAdditionalAdminUsers(): void
    {
        $users = [
            [
                'email' => 'leolpm2@hotmail.com',
                'name' => 'Leol P. M.',
                'is_active' => true,
            ],
            [
                'email' => 'a_ariane@hotmail.com',
                'name' => 'Ariane',
                'is_active' => true,
            ],
            [
                'email' => 'coord@vidavitoriosa.local',
                'name' => 'Coordenação',
                'is_active' => true,
            ],
            [
                'email' => 'apoio@vidavitoriosa.local',
                'name' => 'Apoio Administrativo',
                'is_active' => true,
            ],
            [
                'email' => 'inativo@vidavitoriosa.local',
                'name' => 'Usuário Inativo',
                'is_active' => false,
            ],
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

    private function seedParticipants(): void
    {
        $participants = [
            ['name' => 'Maria Silva', 'display_name' => 'Maria Silva', 'status' => 'active', 'retreat_edition' => 'Vida Vitoriosa 2026'],
            ['name' => 'João Santos', 'display_name' => 'João Santos', 'status' => 'active', 'retreat_edition' => 'Vida Vitoriosa 2026'],
            ['name' => 'Ana Oliveira', 'display_name' => 'Ana Oliveira', 'status' => 'active', 'retreat_edition' => 'Vida Vitoriosa 2026'],
            ['name' => 'Carlos Pereira', 'display_name' => 'Carlos Pereira', 'status' => 'active', 'retreat_edition' => 'Vida Vitoriosa 2026'],
            ['name' => 'Juliana Costa', 'display_name' => 'Juliana Costa', 'status' => 'active', 'retreat_edition' => 'Vida Vitoriosa 2026'],
            ['name' => 'Pedro Lima', 'display_name' => 'Pedro Lima', 'status' => 'active', 'retreat_edition' => 'Vida Vitoriosa 2026'],
            ['name' => 'Fernanda Rocha', 'display_name' => 'Fernanda Rocha', 'status' => 'inactive', 'retreat_edition' => 'Vida Vitoriosa 2026'],
            ['name' => 'Rafael Almeida', 'display_name' => 'Rafael Almeida', 'status' => 'inactive', 'retreat_edition' => 'Vida Vitoriosa 2026'],
        ];

        foreach ($participants as $participant) {
            Participant::query()->updateOrCreate(
                ['name' => $participant['name']],
                $participant
            );
        }
    }

    private function seedTestimonials(): void
    {
        $photoPath = $this->seedDemoAsset();
        $participants = Participant::query()->where('status', 'active')->orderBy('name')->get()->keyBy('name');

        $testimonials = [
            [
                'participant' => 'Maria Silva',
                'sender_name' => 'Ana Paula',
                'phone' => '+55 11 98888-1001',
                'relationship' => 'Mãe',
                'relationship_other' => null,
                'message' => 'Maria, que Deus continue fortalecendo seu coração. Você é resposta de oração e motivo de orgulho para nossa família. Este retiro será um marco na sua caminhada.',
                'status' => 'received',
                'photo' => true,
            ],
            [
                'participant' => 'Maria Silva',
                'sender_name' => 'João Pedro',
                'phone' => '+55 11 98888-1002',
                'relationship' => 'Irmão',
                'relationship_other' => null,
                'message' => 'Você sempre foi exemplo de fé e perseverança. Quero que saiba que estamos orando para que cada palavra deste retiro toque profundamente sua vida.',
                'status' => 'reviewed',
                'photo' => false,
            ],
            [
                'participant' => 'João Santos',
                'sender_name' => 'Pastora Helena',
                'phone' => '+55 11 98888-1003',
                'relationship' => 'Pastor',
                'relationship_other' => null,
                'message' => 'João, sua entrega e seu coração servo são testemunhos vivos. Que este tempo de retiro renove suas forças e reafirme seu chamado.',
                'status' => 'approved',
                'photo' => true,
            ],
            [
                'participant' => 'João Santos',
                'sender_name' => 'Carla Mendes',
                'phone' => '+55 11 98888-1004',
                'relationship' => 'Amiga',
                'relationship_other' => null,
                'message' => 'Estar perto de você sempre traz alegria. Que o Senhor te cubra de paz, direção e coragem para viver o que foi preparado para sua vida.',
                'status' => 'received',
                'photo' => false,
            ],
            [
                'participant' => 'Ana Oliveira',
                'sender_name' => 'Ricardo Oliveira',
                'phone' => '+55 11 98888-1005',
                'relationship' => 'Cônjuge',
                'relationship_other' => null,
                'message' => 'Ana, você é apoio, cuidado e inspiração dentro da nossa casa. Oro para que este retiro seja um encontro de renovo e esperança para você.',
                'status' => 'archived',
                'photo' => true,
            ],
            [
                'participant' => 'Carlos Pereira',
                'sender_name' => 'Sônia Pereira',
                'phone' => '+55 11 98888-1006',
                'relationship' => 'Outro',
                'relationship_other' => 'Tia de consideração',
                'message' => 'Carlos, sua caminhada tem sido marcada por amadurecimento e graça. Que esta edição do Vida Vitoriosa reforce tudo que Deus já começou em você.',
                'status' => 'reviewed',
                'photo' => false,
            ],
            [
                'participant' => 'Juliana Costa',
                'sender_name' => 'Fernanda Costa',
                'phone' => '+55 11 98888-1007',
                'relationship' => 'Mãe',
                'relationship_other' => null,
                'message' => 'Juliana, o Senhor vê cada batalha sua e cada conquista. Receba este retiro como um tempo de cura, descanso e direção divina.',
                'status' => 'received',
                'photo' => true,
            ],
            [
                'participant' => 'Pedro Lima',
                'sender_name' => 'Marcos Lima',
                'phone' => '+55 11 98888-1008',
                'relationship' => 'Pai',
                'relationship_other' => null,
                'message' => 'Meu filho, continue firme. Você foi separado para coisas grandes e este momento é mais uma página do que Deus está escrevendo na sua vida.',
                'status' => 'approved',
                'photo' => false,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            $participant = $participants->get($testimonial['participant']);
            $demoPhotoExists = $testimonial['photo'] && file_exists(Storage::disk('public')->path($photoPath));

            if (! $participant) {
                continue;
            }

            Testimonial::query()->updateOrCreate(
                [
                    'participant_id' => $participant->id,
                    'sender_name' => $testimonial['sender_name'],
                    'message' => $testimonial['message'],
                ],
                [
                    'relationship' => $testimonial['relationship'],
                    'phone' => $testimonial['phone'] ?? null,
                    'relationship_other' => $testimonial['relationship_other'],
                    'photo_path' => $demoPhotoExists ? $photoPath : null,
                    'photo_original_name' => $demoPhotoExists ? 'foto-de-teste.png' : null,
                    'photo_size' => $demoPhotoExists ? filesize(Storage::disk('public')->path($photoPath)) : null,
                    'status' => $testimonial['status'],
                    'is_pdf_generated' => false,
                    'pdf_generated_at' => null,
                    'pdf_batch_id' => null,
                ]
            );
        }
    }

    private function seedDemoAsset(): string
    {
        $sourceImage = base_path('ChatGPT Image 25 de mar. de 2026, 15_55_39.png');
        $targetPath = 'demo/foto-teste.png';

        if (file_exists($sourceImage)) {
            Storage::disk('public')->put($targetPath, file_get_contents($sourceImage));
        }

        return $targetPath;
    }

    private function createHeaderCrop(string $sourceImage, string $destinationPath): void
    {
        $directory = dirname($destinationPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
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
