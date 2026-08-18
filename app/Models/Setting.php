<?php

namespace App\Models;

use App\Support\CurrentEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function valueFor(string $key, mixed $default = null): mixed
    {
        if (static::isEventScoped($key) && app(CurrentEvent::class)->has()) {
            $value = EventSetting::query()
                ->where('event_id', app(CurrentEvent::class)->id())
                ->where('key', $key)
                ->value('value');

            return $value === null || $value === '' ? $default : $value;
        }

        $value = static::query()->where('key', $key)->value('value');

        return $value === null || $value === '' ? $default : $value;
    }

    public static function put(string $key, mixed $value): self
    {
        if (static::isEventScoped($key) && app(CurrentEvent::class)->has()) {
            EventSetting::query()->updateOrCreate(
                [
                    'event_id' => app(CurrentEvent::class)->id(),
                    'key' => $key,
                ],
                ['value' => is_string($value) ? $value : (string) $value]
            );

            return new self(['key' => $key, 'value' => $value]);
        }

        return static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_string($value) ? $value : (string) $value]
        );
    }

    public static function seededDefaults(): array
    {
        $eventSlug = app(CurrentEvent::class)->has()
            ? app(CurrentEvent::class)->get()->slug
            : 'vida-vitoriosa';

        if ($eventSlug === 'edd') {
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
                'login_code_expires_minutes' => '15',
            ];
        }

        return [
            'retreat_name' => 'Vida Vitoriosa',
            'retreat_location' => 'Igreja Vida',
            'retreat_year' => '2026',
            'pdf_footer_text' => 'Vida Vitoriosa - Mensagens de carinho e fé',
            'testimonials_closes_at' => '',
            'public_site_image_path' => 'settings/public-site-default.png',
            'pdf_header_image_path' => 'settings/pdf-header-default.png',
            'login_code_expires_minutes' => '15',
            'recipient_term' => 'Participante',
            'form_title' => 'Envie um depoimento especial para um participante do retiro',
            'form_intro' => 'Envie uma mensagem de carinho e encorajamento para um participante do retiro Vida Vitoriosa. Sua mensagem será entregue de forma especial a quem você ama.',
            'surprise_title' => 'Este depoimento é uma surpresa e não pode ser revelado ao participante.',
            'surprise_text' => 'Não conte que você escreveu esta mensagem. Ela será entregue de forma especial e precisa permanecer em segredo.',
            'relationships_json' => json_encode(config('vida.relationships'), JSON_UNESCAPED_UNICODE),
        ];
    }

    public static function relationships(): array
    {
        $encoded = static::valueFor('relationships_json', json_encode(config('vida.relationships'), JSON_UNESCAPED_UNICODE));
        $relationships = json_decode((string) $encoded, true);

        return is_array($relationships) && $relationships !== []
            ? array_values(array_filter($relationships, 'is_string'))
            : config('vida.relationships');
    }

    public static function isEventScoped(string $key): bool
    {
        return $key !== 'login_code_expires_minutes';
    }
}
