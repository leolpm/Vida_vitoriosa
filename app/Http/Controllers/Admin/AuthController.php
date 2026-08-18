<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoginCodeService;
use App\Support\CurrentEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginCodeService $loginCodeService,
        private readonly CurrentEvent $currentEvent,
    )
    {
    }

    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->where('role', 'admin')
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return back()
                ->withInput()
                ->with('error', 'Nenhum usuário administrativo ativo foi encontrado com este e-mail.');
        }

        if (! $user->canAccessEvent($this->currentEvent->get())) {
            return back()
                ->withInput()
                ->with('error', 'Este usuário não possui acesso administrativo a este evento.');
        }

        $this->loginCodeService->send($user);

        $request->session()->put('admin_login_email', $user->email);
        $request->session()->put('admin_login_event_id', $this->currentEvent->id());

        return redirect()
            ->route('admin.login.verify')
            ->with('success', 'Enviamos um código de acesso para o seu e-mail.');
    }

    public function showVerifyForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('admin_login_email')) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.verify', [
            'email' => $request->session()->get('admin_login_email'),
        ]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->where('role', 'admin')
            ->where('is_active', true)
            ->first();

        if (! $user || ! $this->loginCodeService->verify($user, $validated['code'])) {
            return back()
                ->withInput()
                ->with('error', 'Código inválido ou expirado.');
        }

        if (! $user->canAccessEvent($this->currentEvent->get())) {
            return back()->with('error', 'Este usuário não possui acesso administrativo a este evento.');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('admin_login_email');
        $request->session()->forget('admin_login_event_id');
        $this->loginCodeService->clear($user);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Login realizado com sucesso.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
