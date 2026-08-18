<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class Testimonial extends Model
{
    use BelongsToEvent, HasFactory;

    public const STATUS_LABELS = [
        'received' => 'Recebido',
        'reviewed' => 'Revisado',
        'approved' => 'Aprovado',
        'archived' => 'Arquivado',
    ];

    protected $fillable = [
        'event_id',
        'participant_id',
        'sender_name',
        'phone',
        'relationship',
        'relationship_other',
        'message',
        'photo_path',
        'photo_original_name',
        'photo_size',
        'is_pdf_generated',
        'pdf_generated_at',
        'pdf_batch_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_pdf_generated' => 'boolean',
            'pdf_generated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Testimonial $testimonial): void {
            if (! $testimonial->participant_id || ! $testimonial->event_id) {
                return;
            }

            $participantEventId = Participant::withoutGlobalScopes()
                ->whereKey($testimonial->participant_id)
                ->value('event_id');

            if ((int) $participantEventId !== (int) $testimonial->event_id) {
                throw new LogicException('O depoimento e o participante devem pertencer ao mesmo evento.');
            }

            if (! $testimonial->pdf_batch_id) {
                return;
            }

            $batchEventId = PdfBatch::withoutGlobalScopes()
                ->whereKey($testimonial->pdf_batch_id)
                ->value('event_id');

            if ((int) $batchEventId !== (int) $testimonial->event_id) {
                throw new LogicException('O depoimento e o lote PDF devem pertencer ao mesmo evento.');
            }
        });
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function pdfBatch(): BelongsTo
    {
        return $this->belongsTo(PdfBatch::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? '/storage/'.ltrim($this->photo_path, '/') : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'received' => 'text-bg-secondary',
            'reviewed' => 'text-bg-info',
            'approved' => 'text-bg-success',
            'archived' => 'text-bg-dark',
            default => 'text-bg-secondary',
        };
    }
}
