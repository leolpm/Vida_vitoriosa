<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSetting;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_isolated_demo_data_for_both_events(): void
    {
        Storage::fake('public');

        $this->seed(DatabaseSeeder::class);

        $vida = Event::where('slug', 'vida-vitoriosa')->firstOrFail();
        $edd = Event::where('slug', 'edd')->firstOrFail();

        $this->assertGreaterThan(0, Participant::withoutGlobalScopes()->where('event_id', $vida->id)->count());
        $this->assertGreaterThan(0, Participant::withoutGlobalScopes()->where('event_id', $edd->id)->count());
        $this->assertSame(2, Participant::withoutGlobalScopes()->where('name', 'Ana Oliveira')->count());

        $this->assertDatabaseHas('event_settings', [
            'event_id' => $edd->id,
            'key' => 'recipient_term',
            'value' => 'Liderado',
        ]);
        $this->assertSame(
            'Envie uma mensagem especial para seu liderado',
            EventSetting::where('event_id', $edd->id)->where('key', 'form_title')->value('value')
        );

        Storage::disk('public')->assertExists('events/edd/settings/public-site-default.png');
        Storage::disk('public')->assertExists('events/edd/settings/pdf-header-default.png');
        Storage::disk('public')->assertExists('events/vida-vitoriosa/settings/public-site-default.png');
        Storage::disk('public')->assertExists('events/vida-vitoriosa/settings/pdf-header-default.png');

        $admin = User::where('email', 'leolpm2@hotmail.com')->firstOrFail();
        $this->assertSame(2, $admin->events()->wherePivot('is_active', true)->count());
    }
}
