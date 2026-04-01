<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_correct_testimonial_metrics(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $participant = Participant::create([
            'name' => 'Ana Oliveira',
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        Participant::create([
            'name' => 'Carlos Pereira',
            'status' => 'inactive',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        Testimonial::create([
            'participant_id' => $participant->id,
            'sender_name' => 'Leonardo',
            'phone' => '+5511999999999',
            'relationship' => 'Amigo',
            'message' => 'Aprovado sem PDF',
            'status' => 'approved',
            'is_pdf_generated' => false,
        ]);

        Testimonial::create([
            'participant_id' => $participant->id,
            'sender_name' => 'Marta',
            'phone' => '+5511999999999',
            'relationship' => 'Amiga',
            'message' => 'Aprovado com PDF',
            'status' => 'approved',
            'is_pdf_generated' => true,
        ]);

        Testimonial::create([
            'participant_id' => $participant->id,
            'sender_name' => 'João',
            'phone' => '+5511999999999',
            'relationship' => 'Amigo',
            'message' => 'Recebido',
            'status' => 'received',
            'is_pdf_generated' => false,
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSeeText('Participantes');
        $response->assertSeeText('Ativos');
        $response->assertSeeText('Depoimentos');
        $response->assertSeeText('Aprovados');
        $response->assertSeeText('Aprovados sem PDF');
        $response->assertSeeText('Pendentes');
        $response->assertSeeText('2');
        $response->assertSeeText('3');
        $response->assertSeeText('1');
    }
}
