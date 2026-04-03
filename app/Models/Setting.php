<?php

namespace App\Models;

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
        $value = static::query()->where('key', $key)->value('value');

        return $value === null || $value === '' ? $default : $value;
    }

    public static function put(string $key, mixed $value): self
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_string($value) ? $value : (string) $value]
        );
    }

    public static function seededDefaults(): array
    {
        return [
            'retreat_name' => 'Vida Vitoriosa',
            'retreat_location' => 'Igreja Vida',
            'retreat_year' => '2026',
            'pdf_footer_text' => 'Vida Vitoriosa - Mensagens de carinho e fé',
            'testimonials_closes_at' => '',
            'public_site_image_path' => 'settings/public-site-default.png',
            'pdf_header_image_path' => 'settings/pdf-header-default.png',
            'login_code_expires_minutes' => '15',
        ];
    }
}
