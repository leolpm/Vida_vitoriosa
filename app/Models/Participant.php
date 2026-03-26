<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'status',
        'retreat_edition',
    ];

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function pdfBatches(): HasMany
    {
        return $this->hasMany(PdfBatch::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function getLabelAttribute(): string
    {
        return $this->display_name ?: $this->name;
    }
}
