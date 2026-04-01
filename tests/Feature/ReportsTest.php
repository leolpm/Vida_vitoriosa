<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_participants_report_filters_approved_without_pdf(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $approved = Participant::create([
            'name' => 'Ana Oliveira',
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        $other = Participant::create([
            'name' => 'Carlos Pereira',
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        Testimonial::create([
            'participant_id' => $approved->id,
            'sender_name' => 'Leandro',
            'phone' => '+5511999999999',
            'relationship' => 'Amigo',
            'message' => 'Mensagem',
            'status' => 'approved',
            'is_pdf_generated' => false,
        ]);

        Testimonial::create([
            'participant_id' => $other->id,
            'sender_name' => 'Leandro',
            'phone' => '+5511999999999',
            'relationship' => 'Amigo',
            'message' => 'Mensagem',
            'status' => 'received',
            'is_pdf_generated' => false,
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.reports.participants', [
            'participants_filter' => 'approved_pending',
        ]));

        $response->assertOk();
        $response->assertSeeText('Ana Oliveira');
        $response->assertDontSeeText('Carlos Pereira');
    }

    public function test_participants_report_exports_excel(): void
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

        $this->actingAs($admin);

        $response = $this->get(route('admin.reports.participants.excel', [
            'participants_filter' => 'all',
        ]));

        $response->assertDownload('relatorio_participantes.xlsx');
        $this->assertDatabaseHas('participants', ['id' => $participant->id]);
    }

    public function test_testimonials_report_filters_status_and_pdf_flag(): void
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

        Testimonial::create([
            'participant_id' => $participant->id,
            'sender_name' => 'Leandro',
            'phone' => '+5511999999999',
            'relationship' => 'Amigo',
            'message' => 'Mensagem aprovada',
            'status' => 'approved',
            'is_pdf_generated' => false,
        ]);

        Testimonial::create([
            'participant_id' => $participant->id,
            'sender_name' => 'Joana',
            'phone' => '+5511888888888',
            'relationship' => 'Amiga',
            'message' => 'Mensagem recebida',
            'status' => 'received',
            'is_pdf_generated' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.reports.testimonials', [
            'status' => 'approved',
            'generated' => 'no',
        ]));

        $response->assertOk();
        $response->assertSeeText('Leandro');
        $response->assertSeeText('Ana Oliveira');
        $response->assertDontSeeText('Joana');
    }

    public function test_testimonials_report_exports_excel(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.reports.testimonials.excel', [
            'status' => 'all',
            'generated' => 'all',
        ]));

        $response->assertDownload('relatorio_depoimentos.xlsx');
    }
}
