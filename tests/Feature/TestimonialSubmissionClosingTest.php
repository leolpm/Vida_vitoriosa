<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestimonialSubmissionClosingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_form_is_replaced_when_submission_window_is_closed(): void
    {
        Setting::put('testimonials_closes_at', now()->subHour()->format('Y-m-d\TH:i'));

        $response = $this->get(route('testimonials.create'));

        $response->assertOk();
        $response->assertSeeText('O período para envio de depoimentos foi encerrado.');
        $response->assertDontSeeText('Enviar Depoimento');
    }

    public function test_store_rejects_submissions_after_closure(): void
    {
        Setting::put('testimonials_closes_at', now()->subHour()->format('Y-m-d\TH:i'));

        $participant = Participant::create([
            'name' => 'Ana Oliveira',
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        $response = $this->post(route('testimonials.store'), [
            'sender_name' => 'Leandro',
            'phone' => '+5511999999999',
            'participant_id' => $participant->id,
            'relationship' => 'Amigo',
            'message' => 'Mensagem de teste',
        ]);

        $response->assertRedirect(route('testimonials.create'));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('testimonials', 0);
    }
}
