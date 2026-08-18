<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class PdfBatch extends Model
{
    use BelongsToEvent, HasFactory;

    protected $fillable = [
        'event_id',
        'participant_id',
        'generation_mode',
        'generated_by',
        'generated_at',
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PdfBatch $batch): void {
            if (! $batch->participant_id || ! $batch->event_id) {
                return;
            }

            $participantEventId = Participant::withoutGlobalScopes()
                ->whereKey($batch->participant_id)
                ->value('event_id');

            if ((int) $participantEventId !== (int) $batch->event_id) {
                throw new LogicException('O lote PDF e o participante devem pertencer ao mesmo evento.');
            }
        });
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }
}
