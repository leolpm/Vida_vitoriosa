<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ParticipantBulkImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_participants_from_utf8_csv(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $csv = <<<'CSV'
Nome,Nome de exibição,Status,Edição do retiro
João Santos,João Santos,active,Vida Vitoriosa 2026
Maria Oliveira,,Ativo,Vida Vitoriosa 2026
CSV;

        $response = $this->post(route('admin.participants.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('participantes.csv', $csv),
        ]);

        $response->assertRedirect(route('admin.participants.index'));

        $this->assertDatabaseHas('participants', [
            'name' => 'João Santos',
            'display_name' => 'João Santos',
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);

        $this->assertDatabaseHas('participants', [
            'name' => 'Maria Oliveira',
            'display_name' => null,
            'status' => 'active',
            'retreat_edition' => 'Vida Vitoriosa 2026',
        ]);
    }

    public function test_admin_can_download_participant_template(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.participants.template'));

        $response->assertOk();
        $response->assertDownload('modelo_participantes.xlsx');
    }
}
