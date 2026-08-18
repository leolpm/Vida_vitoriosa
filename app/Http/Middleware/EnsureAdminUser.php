<?php

namespace App\Http\Middleware;

use App\Support\CurrentEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminUser
{
    public function __construct(private readonly CurrentEvent $currentEvent)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->with('error', 'Acesso restrito à área administrativa.');
        }

        if (! $this->currentEvent->has() || ! $user->canAccessEvent($this->currentEvent->get())) {
            abort(403, 'Você não possui acesso administrativo a este evento.');
        }

        return $next($request);
    }
}
