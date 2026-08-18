<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'host',
        'environment',
        'scheme',
        'port',
        'is_primary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
