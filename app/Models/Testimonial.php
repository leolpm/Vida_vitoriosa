<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Testimonial extends Model
{
    use HasFactory;

    public const STATUS_LABELS = [
        'received' => 'Recebido',
        'reviewed' => 'Revisado',
        'approved' => 'Aprovado',
        'archived' => 'Arquivado',
    ];

    protected $fillable = [
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
        return $this->photo_path ? '/storage/' . ltrim($this->photo_path, '/') : null;
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
