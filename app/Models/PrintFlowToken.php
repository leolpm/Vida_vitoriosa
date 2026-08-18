<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintFlowToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'print_flow_id', 'token_hash', 'expires_at', 'max_accesses', 'accesses_used',
        'first_accessed_at', 'last_accessed_at', 'invalidated_at', 'invalidation_reason',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'first_accessed_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'invalidated_at' => 'datetime',
            'max_accesses' => 'integer',
            'accesses_used' => 'integer',
        ];
    }

    public function printFlow(): BelongsTo
    {
        return $this->belongsTo(PrintFlow::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasCapacity(): bool
    {
        return $this->accesses_used < $this->max_accesses;
    }

    public function isUsable(): bool
    {
        return $this->invalidated_at === null && ! $this->isExpired() && $this->hasCapacity();
    }
}
