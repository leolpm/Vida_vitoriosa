<?php

namespace App\Providers;

use App\Models\Event;
use App\Services\EventUrlGenerator;
use App\Support\CurrentEvent;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentEvent::class, fn () => new CurrentEvent);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('layouts.admin', function ($view): void {
            $context = app(CurrentEvent::class);

            if (! $context->has()) {
                $view->with('eventSwitchLinks', collect());

                return;
            }

            $currentEvent = $context->get();
            $user = auth()->user();
            $urlGenerator = app(EventUrlGenerator::class);

            $links = Event::active()
                ->whereKeyNot($currentEvent->id)
                ->get()
                ->filter(fn (Event $event): bool => $user?->canAccessEvent($event) ?? false)
                ->map(fn (Event $event): array => [
                    'name' => $event->name,
                    'url' => $urlGenerator->forEvent($event),
                ])
                ->filter(fn (array $link): bool => $link['url'] !== null)
                ->values();

            $view->with('eventSwitchLinks', $links);
        });
    }
}
