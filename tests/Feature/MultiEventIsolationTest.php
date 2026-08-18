<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MultiEventIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_forms_use_the_correct_identity_and_participants(): void
    {
        $this->useEvent('vida-vitoriosa');
        Participant::create([
            'name' => 'Participante Vida Exclusivo',
            'status' => 'active',
            'retreat_edition' => 'Vida 2026',
        ]);

        $this->useEvent('edd');
        Participant::create([
            'name' => 'Liderado EDD Exclusivo',
            'status' => 'active',
            'retreat_edition' => 'EDD 2026',
        ]);

        $vidaResponse = $this->get($this->eventUrl('vida-vitoriosa'));
        $vidaResponse->assertOk();
        $vidaResponse->assertSeeText('Vida Vitoriosa');
        $vidaResponse->assertSeeText('Participante Vida Exclusivo');
        $vidaResponse->assertDontSeeText('Liderado EDD Exclusivo');
        $vidaResponse->assertSee('<body class="event-vida-vitoriosa">', false);

        $eddResponse = $this->get($this->eventUrl('edd'));
        $eddResponse->assertOk();
        $eddResponse->assertSeeText('Envie uma mensagem especial para seu liderado');
        $eddResponse->assertSeeText('Liderado EDD Exclusivo');
        $eddResponse->assertDontSeeText('Participante Vida Exclusivo');
        $eddResponse->assertSee('<body class="event-edd">', false);
        $eddResponse->assertSee('images/events/edd/edd-public-banner.png', false);
    }

    public function test_edd_submission_uses_host_event_and_rejects_foreign_participant(): void
    {
        Storage::fake('public');

        $vida = $this->useEvent('vida-vitoriosa');
        $vidaParticipant = Participant::create([
            'name' => 'Participante Vida',
            'status' => 'active',
        ]);

        $edd = $this->useEvent('edd');
        $eddParticipant = Participant::create([
            'name' => 'Liderado EDD',
            'status' => 'active',
        ]);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2nJAAAAAASUVORK5CYII=');
        $photo = UploadedFile::fake()->createWithContent('foto.png', $png);

        $response = $this->post($this->eventUrl('edd', '/depoimentos/enviar'), [
            'event_id' => $vida->id,
            'sender_name' => 'Supervisora EDD',
            'phone' => '+5521999999999',
            'participant_id' => $eddParticipant->id,
            'relationship' => 'Supervisor',
            'message' => 'Mensagem exclusiva do EDD.',
            'photo' => $photo,
        ]);

        $response->assertRedirect($this->eventUrl('edd', '/depoimentos/sucesso'));
        $this->assertDatabaseHas('testimonials', [
            'event_id' => $edd->id,
            'participant_id' => $eddParticipant->id,
            'sender_name' => 'Supervisora EDD',
        ]);
        $this->assertDatabaseMissing('testimonials', [
            'event_id' => $vida->id,
            'sender_name' => 'Supervisora EDD',
        ]);

        $testimonial = Testimonial::withoutGlobalScopes()->where('sender_name', 'Supervisora EDD')->firstOrFail();
        $this->assertStringStartsWith('events/edd/testimonials/', $testimonial->photo_path);
        Storage::disk('public')->assertExists($testimonial->photo_path);

        $invalidResponse = $this->from($this->eventUrl('edd'))
            ->post($this->eventUrl('edd', '/depoimentos/enviar'), [
                'sender_name' => 'Tentativa cruzada',
                'phone' => '+5521988888888',
                'participant_id' => $vidaParticipant->id,
                'relationship' => 'Líder',
                'message' => 'Não deve ser salva.',
            ]);

        $invalidResponse->assertRedirect($this->eventUrl('edd'));
        $invalidResponse->assertSessionHasErrors('participant_id');
        $this->assertDatabaseMissing('testimonials', ['sender_name' => 'Tentativa cruzada']);
    }

    public function test_closing_window_is_independent_for_each_event(): void
    {
        $this->useEvent('vida-vitoriosa');
        Setting::put('testimonials_closes_at', now()->subMinute()->format('Y-m-d\TH:i'));

        $this->useEvent('edd');
        Setting::put('testimonials_closes_at', now()->addDay()->format('Y-m-d\TH:i'));

        $this->get($this->eventUrl('vida-vitoriosa'))
            ->assertOk()
            ->assertSeeText('O período para envio de depoimentos foi encerrado.');

        $this->get($this->eventUrl('edd'))
            ->assertOk()
            ->assertSeeText('Enviar mensagem')
            ->assertDontSeeText('O período para envio de depoimentos foi encerrado.');
    }

    public function test_event_permission_and_route_binding_fail_closed(): void
    {
        $vida = $this->useEvent('vida-vitoriosa');
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        $admin->events()->attach($vida->id, ['role' => 'admin', 'is_active' => true]);

        $this->useEvent('edd');
        $eddParticipant = Participant::create([
            'name' => 'Somente EDD',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get($this->eventUrl('vida-vitoriosa', '/admin/dashboard'))
            ->assertOk();

        $this->get($this->eventUrl('edd', '/admin/dashboard'))->assertForbidden();

        $globalAdmin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($globalAdmin)
            ->get($this->eventUrl('vida-vitoriosa', "/admin/participants/{$eddParticipant->id}/edit"))
            ->assertNotFound();
    }

    public function test_unknown_domain_does_not_expose_event_data(): void
    {
        Participant::create([
            'name' => 'Nome que não pode vazar',
            'status' => 'active',
        ]);

        $this->get('http://evento-invalido.atitudelaranja.test:8888/')
            ->assertNotFound()
            ->assertSeeText('Não encontramos um evento neste endereço.')
            ->assertDontSeeText('Nome que não pode vazar');
    }
}
