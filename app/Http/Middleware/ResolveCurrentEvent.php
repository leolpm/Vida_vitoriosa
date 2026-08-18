<?php

namespace App\Http\Middleware;

use App\Models\EventDomain;
use App\Support\CurrentEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentEvent
{
    public function __construct(private readonly CurrentEvent $currentEvent)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('up')) {
            return $next($request);
        }

        $host = strtolower(rtrim($request->getHost(), '.'));
        $domain = EventDomain::query()
            ->with('event')
            ->where('host', $host)
            ->where('environment', config('events.environment'))
            ->where('is_active', true)
            ->first();

        if (! $domain || ! $domain->event) {
            Log::warning('Acesso por domínio de evento desconhecido.', [
                'host' => $host,
                'environment' => config('events.environment'),
            ]);

            return response()->view('errors.event-not-found', [], 404);
        }

        $this->currentEvent->set($domain->event);
        $request->attributes->set('current_event', $domain->event);
        View::share('currentEvent', $domain->event);

        return $next($request);
    }
}
