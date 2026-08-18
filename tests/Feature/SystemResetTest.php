<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\PdfBatch;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reset_participants_testimonials_and_generated_files(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $participant = Participant::create([
            'name' => 'Ana Oliveira',
            'display_name' => 'Ana Oliveira',
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        $batch = PdfBatch::create([
            'participant_id' => $participant->id,
            'generation_mode' => 'only_new:approved',
            'generated_by' => $admin->id,
            'generated_at' => now(),
            'file_path' => 'events/vida-vitoriosa/pdf/participant-3/ana.pdf',
        ]);

        Testimonial::create([
            'participant_id' => $participant->id,
            'sender_name' => 'Leonardo',
            'phone' => '+5511999999999',
            'relationship' => 'Amigo',
            'message' => 'Mensagem de teste',
            'photo_path' => 'events/vida-vitoriosa/testimonials/foto-teste.jpg',
            'photo_original_name' => 'foto-teste.jpg',
            'photo_size' => 1024,
            'pdf_batch_id' => $batch->id,
            'status' => 'approved',
            'is_pdf_generated' => true,
            'pdf_generated_at' => now(),
        ]);

        Storage::disk('public')->put('events/vida-vitoriosa/testimonials/foto-teste.jpg', 'image');
        Storage::disk('public')->put('events/vida-vitoriosa/pdf/participant-3/ana.pdf', 'pdf');

        $edd = $this->useEvent('edd');
        $eddParticipant = Participant::create([
            'name' => 'Daniel Souza',
            'status' => 'active',
            'retreat_edition' => 'EDD 2026',
        ]);
        $eddTestimonial = Testimonial::create([
            'participant_id' => $eddParticipant->id,
            'sender_name' => 'Líder EDD',
            'phone' => '+5521999999999',
            'relationship' => 'Líder',
            'message' => 'Mensagem preservada',
            'photo_path' => 'events/edd/testimonials/preservar.jpg',
            'status' => 'approved',
        ]);
        Storage::disk('public')->put('events/edd/testimonials/preservar.jpg', 'image');

        $this->useEvent('vida-vitoriosa');

        $this->actingAs($admin);

        $response = $this->post(route('admin.settings.reset'), [
            'confirmation' => 'RESETAR VIDA VITORIOSA',
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('participants', ['id' => $participant->id]);
        $this->assertDatabaseMissing('testimonials', ['sender_name' => 'Leonardo']);
        $this->assertDatabaseCount('pdf_batches', 0);

        $this->assertDatabaseHas('participants', ['id' => $eddParticipant->id, 'event_id' => $edd->id]);
        $this->assertDatabaseHas('testimonials', ['id' => $eddTestimonial->id, 'event_id' => $edd->id]);

        Storage::disk('public')->assertMissing('events/vida-vitoriosa/testimonials/foto-teste.jpg');
        Storage::disk('public')->assertMissing('events/vida-vitoriosa/pdf/participant-3/ana.pdf');
        Storage::disk('public')->assertExists('events/edd/testimonials/preservar.jpg');
    }
}
