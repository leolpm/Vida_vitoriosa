<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('event_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('host');
            $table->string('environment');
            $table->string('scheme')->default('https');
            $table->unsignedSmallInteger('port')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['environment', 'host']);
            $table->index(['event_id', 'environment', 'is_primary']);
        });

        Schema::create('event_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'key']);
        });

        Schema::create('event_user', function (Blueprint $table): void {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('admin');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->primary(['event_id', 'user_id']);
        });

        foreach (['participants', 'pdf_batches', 'testimonials'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('event_id')->nullable()->index()->constrained()->restrictOnDelete();
            });
        }

        $now = now();
        $vidaId = DB::table('events')->insertGetId([
            'name' => 'Vida Vitoriosa',
            'slug' => 'vida-vitoriosa',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $eddId = DB::table('events')->insertGetId([
            'name' => 'EDD',
            'slug' => 'edd',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $domains = [
            [$vidaId, 'vidavitoriosa.atitudelaranja.com', 'production', 'https', null, true],
            [$eddId, 'edd.atitudelaranja.com', 'production', 'https', null, true],
            [$vidaId, 'vidavitoriosa.atitudelaranja.test', 'local', 'http', 8888, true],
            [$eddId, 'edd.atitudelaranja.test', 'local', 'http', 8888, true],
            [$vidaId, 'localhost', 'local', 'http', 8888, false],
            [$vidaId, '127.0.0.1', 'local', 'http', 8888, false],
        ];

        DB::table('event_domains')->insert(array_map(fn (array $domain): array => [
            'event_id' => $domain[0],
            'host' => $domain[1],
            'environment' => $domain[2],
            'scheme' => $domain[3],
            'port' => $domain[4],
            'is_primary' => $domain[5],
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $domains));

        DB::table('participants')->update(['event_id' => $vidaId]);
        DB::table('pdf_batches')->update(['event_id' => $vidaId]);
        DB::table('testimonials')->update(['event_id' => $vidaId]);

        $vidaDefaults = $this->vidaDefaults();
        $currentSettings = DB::table('settings')->pluck('value', 'key')->all();

        foreach ($vidaDefaults as $key => $default) {
            DB::table('event_settings')->insert([
                'event_id' => $vidaId,
                'key' => $key,
                'value' => $currentSettings[$key] ?? $default,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($this->eddDefaults() as $key => $value) {
            DB::table('event_settings')->insert([
                'event_id' => $eddId,
                'key' => $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $eventUsers = [];
        foreach (DB::table('users')->pluck('id') as $userId) {
            foreach ([$vidaId, $eddId] as $eventId) {
                $eventUsers[] = [
                    'event_id' => $eventId,
                    'user_id' => $userId,
                    'role' => 'admin',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($eventUsers !== []) {
            DB::table('event_user')->insert($eventUsers);
        }
    }

    public function down(): void
    {
        foreach (['testimonials', 'pdf_batches', 'participants'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('event_id');
            });
        }

        Schema::dropIfExists('event_user');
        Schema::dropIfExists('event_settings');
        Schema::dropIfExists('event_domains');
        Schema::dropIfExists('events');
    }

    private function vidaDefaults(): array
    {
        return [
            'retreat_name' => 'Vida Vitoriosa',
            'retreat_location' => 'Igreja Vida',
            'retreat_year' => '2026',
            'pdf_footer_text' => 'Vida Vitoriosa - Mensagens de carinho e fé',
            'testimonials_closes_at' => '',
            'public_site_image_path' => 'settings/public-site-default.png',
            'pdf_header_image_path' => 'settings/pdf-header-default.png',
            'recipient_term' => 'Participante',
            'form_title' => 'Envie um depoimento especial para um participante do retiro',
            'form_intro' => 'Envie uma mensagem de carinho e encorajamento para um participante do retiro Vida Vitoriosa. Sua mensagem será entregue de forma especial a quem você ama.',
            'surprise_title' => 'Este depoimento é uma surpresa e não pode ser revelado ao participante.',
            'surprise_text' => 'Não conte que você escreveu esta mensagem. Ela será entregue de forma especial e precisa permanecer em segredo.',
            'relationships_json' => json_encode(config('vida.relationships'), JSON_UNESCAPED_UNICODE),
        ];
    }

    private function eddDefaults(): array
    {
        return [
            'retreat_name' => 'EDD',
            'retreat_location' => 'Igreja Batista Atitude',
            'retreat_year' => '2026',
            'pdf_footer_text' => 'EDD - Encontro de Discipuladores com Deus',
            'testimonials_closes_at' => '',
            'public_site_image_path' => 'events/edd/settings/public-site-default.png',
            'pdf_header_image_path' => 'events/edd/settings/pdf-header-default.png',
            'recipient_term' => 'Liderado',
            'form_title' => 'Envie uma mensagem especial para seu liderado',
            'form_intro' => 'Líder ou supervisor, envie uma mensagem de carinho, gratidão e impulsionamento para o seu liderado que está participando do EDD. Sua mensagem será entregue de forma especial a quem você ama.',
            'surprise_title' => 'Esta mensagem é uma surpresa para o seu liderado.',
            'surprise_text' => 'Não conte que você escreveu esta mensagem. Ela será entregue de forma especial durante o EDD e precisa permanecer em segredo.',
            'relationships_json' => json_encode(['Líder', 'Supervisor', 'Pastor', 'Coordenador', 'Outro'], JSON_UNESCAPED_UNICODE),
        ];
    }
};
