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
            'file_path' => 'pdf/participant-3/ana.pdf',
        ]);

        Testimonial::create([
            'participant_id' => $participant->id,
            'sender_name' => 'Leonardo',
            'phone' => '+5511999999999',
            'relationship' => 'Amigo',
            'message' => 'Mensagem de teste',
            'photo_path' => 'testimonials/foto-teste.jpg',
            'photo_original_name' => 'foto-teste.jpg',
            'photo_size' => 1024,
            'pdf_batch_id' => $batch->id,
            'status' => 'approved',
            'is_pdf_generated' => true,
            'pdf_generated_at' => now(),
        ]);

        Storage::disk('public')->put('testimonials/foto-teste.jpg', 'image');
        Storage::disk('public')->put('pdf/participant-3/ana.pdf', 'pdf');
        Storage::disk('public')->put('tmp/test-batch-1.pdf', 'tmp');
        Storage::disk('local')->put('pdf-temp/tmp-image.png', 'tmp');
        Storage::disk('local')->put('pdf-emojis/emoji.png', 'emoji');

        $this->actingAs($admin);

        $response = $this->post(route('admin.settings.reset'), [
            'confirmation' => 'RESETAR',
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('participants', 0);
        $this->assertDatabaseCount('testimonials', 0);
        $this->assertDatabaseCount('pdf_batches', 0);

        Storage::disk('public')->assertMissing('testimonials/foto-teste.jpg');
        Storage::disk('public')->assertMissing('pdf/participant-3/ana.pdf');
        Storage::disk('public')->assertMissing('tmp/test-batch-1.pdf');
        Storage::disk('local')->assertMissing('pdf-temp/tmp-image.png');
        Storage::disk('local')->assertMissing('pdf-emojis/emoji.png');
    }
}
