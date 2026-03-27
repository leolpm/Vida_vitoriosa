<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_participants_by_name(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        Participant::create([
            'name' => 'Ana Oliveira',
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        Participant::create([
            'name' => 'Carlos Pereira',
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        $response = $this->get(route('admin.participants.index', [
            'participant_name' => 'Ana',
        ]));

        $response->assertOk();
        $response->assertSee('Ana Oliveira');
        $response->assertDontSee('Carlos Pereira');
    }
}
