<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamMember extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'status', 'task_limit'];

    protected function casts(): array
    {
        return ['task_limit' => 'integer'];
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function printFlows(): HasMany
    {
        return $this->hasMany(PrintFlow::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function isAuthorizedFor(Event $event): bool
    {
        return $this->status === 'active'
            && $this->events()->whereKey($event->id)->wherePivot('is_active', true)->exists();
    }

    public function openTasksCount(): int
    {
        return $this->printFlows()->withoutGlobalScopes()->whereIn('status', PrintFlow::OPEN_STATUSES)->count();
    }
}
