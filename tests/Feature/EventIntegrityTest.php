<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Testimonial;
use App\Support\CurrentEvent;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class EventIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_creation_requires_an_event_context(): void
    {
        app(CurrentEvent::class)->clear();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Um contexto de evento é obrigatório');

        Participant::create([
            'name' => 'Sem evento',
            'status' => 'active',
        ]);
    }

    public function test_testimonial_cannot_reference_participant_from_another_event(): void
    {
        $this->useEvent('vida-vitoriosa');
        $participant = Participant::create([
            'name' => 'Participante Vida',
            'status' => 'active',
        ]);

        $this->useEvent('edd');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('devem pertencer ao mesmo evento');

        Testimonial::create([
            'participant_id' => $participant->id,
            'sender_name' => 'Autor EDD',
            'phone' => '+5521999999999',
            'relationship' => 'Líder',
            'message' => 'Mensagem inválida.',
            'status' => 'received',
        ]);
    }

    public function test_database_rejects_operational_record_without_event(): void
    {
        $this->expectException(QueryException::class);

        Participant::withoutGlobalScopes()->getQuery()->insert([
            'name' => 'Registro sem evento',
            'status' => 'active',
            'event_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
