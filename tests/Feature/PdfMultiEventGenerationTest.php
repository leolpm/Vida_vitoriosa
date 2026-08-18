<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\PdfBatch;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfMultiEventGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_edd_pdf_is_generated_and_cannot_be_downloaded_from_another_event(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('A extensão GD é necessária para validar a geração real do PDF.');
        }

        Storage::fake('public');

        $edd = $this->useEvent('edd');
        $participant = Participant::create([
            'name' => 'Daniel Souza',
            'status' => 'active',
            'retreat_edition' => 'EDD 2026',
        ]);
        $testimonial = Testimonial::create([
            'participant_id' => $participant->id,
            'sender_name' => 'Pr. André Martins',
            'phone' => '+5521988882101',
            'relationship' => 'Líder',
            'message' => 'Mensagem exclusiva do EDD.',
            'status' => 'approved',
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(
            $this->eventUrl('edd', "/admin/pdf/participants/{$participant->id}/generate"),
            [
                'mode' => 'only_new',
                'status_filter' => 'approved',
            ],
        );

        $response->assertRedirect($this->eventUrl('edd', '/admin/pdf'));
        $response->assertSessionHas('success');

        $batch = PdfBatch::withoutGlobalScopes()->sole();
        $this->assertSame($edd->id, $batch->event_id);
        $this->assertStringStartsWith("events/edd/pdf/participant-{$participant->id}/daniel-souza_", $batch->file_path);
        Storage::disk('public')->assertExists($batch->file_path);

        $testimonial->refresh();
        $this->assertTrue($testimonial->is_pdf_generated);
        $this->assertSame($batch->id, $testimonial->pdf_batch_id);

        $this->get($this->eventUrl('vida-vitoriosa', "/admin/pdf/batches/{$batch->id}/download"))
            ->assertNotFound();
    }
}
