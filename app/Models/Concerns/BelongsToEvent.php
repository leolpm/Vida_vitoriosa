<?php

namespace App\Models\Concerns;

use App\Models\Event;
use App\Support\CurrentEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToEvent
{
    public static function bootBelongsToEvent(): void
    {
        static::addGlobalScope('event', function (Builder $builder): void {
            $context = app(CurrentEvent::class);

            if ($context->has()) {
                $builder->where($builder->qualifyColumn('event_id'), $context->id());
            }
        });

        static::creating(function ($model): void {
            if ($model->event_id !== null) {
                return;
            }

            $context = app(CurrentEvent::class);

            if ($context->has()) {
                $model->event_id = $context->id();
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
