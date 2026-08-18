<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'key',
        'value',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
