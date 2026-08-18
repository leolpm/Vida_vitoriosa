<?php

namespace App\Models\Concerns;

use App\Models\Event;
use App\Support\CurrentEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

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

        static::saving(function ($model): void {
            if ($model->event_id !== null) {
                return;
            }

            $context = app(CurrentEvent::class);

            if ($context->has()) {
                $model->event_id = $context->id();

                return;
            }

            throw new LogicException('Um contexto de evento é obrigatório para criar '.class_basename($model).'.');
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
