<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Participant;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\CurrentEvent;
use Illuminate\Database\Seeder;

class EddDemoSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::where('slug', 'edd')->firstOrFail();
        app(CurrentEvent::class)->set($event);

        $daniel = Participant::updateOrCreate(
            ['name' => 'Daniel Souza'],
            [
                'display_name' => 'Daniel Souza',
                'status' => 'active',
                'retreat_edition' => 'EDD 2026',
            ]
        );

        Participant::updateOrCreate(
            ['name' => 'Ana Oliveira'],
            [
                'display_name' => 'Ana Oliveira',
                'status' => 'active',
                'retreat_edition' => 'EDD 2026',
            ]
        );

        Participant::updateOrCreate(
            ['name' => 'Mateus Ribeiro'],
            [
                'display_name' => 'Mateus Ribeiro',
                'status' => 'inactive',
                'retreat_edition' => 'EDD 2026',
            ]
        );

        Testimonial::firstOrCreate(
            [
                'participant_id' => $daniel->id,
                'sender_name' => 'Pr. André Martins',
            ],
            [
                'phone' => '+5521988882101',
                'relationship' => 'Líder',
                'message' => 'Daniel, sua disposição para servir inspira nossa equipe. Que o EDD fortaleça seu chamado e amplie sua intimidade com Deus.',
                'status' => 'approved',
                'is_pdf_generated' => false,
            ]
        );

        foreach (User::where('role', 'admin')->get() as $user) {
            $user->events()->syncWithoutDetaching([
                $event->id => ['role' => 'admin', 'is_active' => true],
            ]);
        }
    }
}
