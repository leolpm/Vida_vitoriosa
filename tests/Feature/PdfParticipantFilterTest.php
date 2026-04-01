<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfParticipantFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_shows_only_participants_with_approved_testimonials_pending_pdf(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $approvedPending = Participant::create([
            'name' => 'Ana Oliveira',
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        $approvedGenerated = Participant::create([
            'name' => 'Carlos Pereira',
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        $pendingOnly = Participant::create([
            'name' => 'Mariana Costa',
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        Testimonial::create([
            'participant_id' => $approvedPending->id,
            'sender_name' => 'Ricardo',
            'phone' => '+5511999999999',
            'relationship' => 'Amigo',
            'message' => 'Mensagem aprovada pendente',
            'status' => 'approved',
            'is_pdf_generated' => false,
        ]);

        Testimonial::create([
            'participant_id' => $approvedGenerated->id,
            'sender_name' => 'Ricardo',
            'phone' => '+5511999999999',
            'relationship' => 'Amigo',
            'message' => 'Mensagem aprovada com PDF',
            'status' => 'approved',
            'is_pdf_generated' => true,
        ]);

        Testimonial::create([
            'participant_id' => $pendingOnly->id,
            'sender_name' => 'Ricardo',
            'phone' => '+5511999999999',
            'relationship' => 'Amigo',
            'message' => 'Mensagem pendente',
            'status' => 'received',
            'is_pdf_generated' => false,
        ]);

        $response = $this->get(route('admin.pdf.index', [
            'participants_filter' => 'approved_pending',
        ]));

        $response->assertOk();
        $response->assertSee('Ana Oliveira');
        $response->assertDontSee('Carlos Pereira');
        $response->assertDontSee('Mariana Costa');
    }

    public function test_filter_shows_only_participants_with_pending_testimonials_in_other_statuses(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $pendingParticipant = Participant::create([
            'name' => 'Joana Lima',
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        $approvedParticipant = Participant::create([
            'name' => 'Paulo Mendes',
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        Testimonial::create([
            'participant_id' => $pendingParticipant->id,
            'sender_name' => 'Ricardo',
            'phone' => '+5511999999999',
            'relationship' => 'Amigo',
            'message' => 'Mensagem pendente',
            'status' => 'received',
            'is_pdf_generated' => false,
        ]);

        Testimonial::create([
            'participant_id' => $approvedParticipant->id,
            'sender_name' => 'Ricardo',
            'phone' => '+5511999999999',
            'relationship' => 'Amigo',
            'message' => 'Mensagem aprovada',
            'status' => 'approved',
            'is_pdf_generated' => false,
        ]);

        $response = $this->get(route('admin.pdf.index', [
            'participants_filter' => 'pending',
        ]));

        $response->assertOk();
        $response->assertSee('Joana Lima');
        $response->assertDontSee('Paulo Mendes');
    }

    public function test_filter_shows_participants_without_testimonials(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $participantWithoutTestimonials = Participant::create([
            'name' => 'Sem Mensagens',
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        $participantWithTestimonials = Participant::create([
            'name' => 'Com Mensagens',
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        Testimonial::create([
            'participant_id' => $participantWithTestimonials->id,
            'sender_name' => 'Ricardo',
            'phone' => '+5511999999999',
            'relationship' => 'Amigo',
            'message' => 'Mensagem',
            'status' => 'approved',
            'is_pdf_generated' => false,
        ]);

        $response = $this->get(route('admin.pdf.index', [
            'participants_filter' => 'without_testimonials',
        ]));

        $response->assertOk();
        $response->assertSee('Sem Mensagens');
        $response->assertDontSee('Com Mensagens');
    }
}
