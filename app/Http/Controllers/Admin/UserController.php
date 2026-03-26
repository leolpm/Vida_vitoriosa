<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::where('role', 'admin')->orderBy('name')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => 'admin',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Usuário administrativo criado com sucesso.');
    }

    public function edit(User $user): View
    {
        abort_unless($user->role === 'admin', 404);

        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'admin', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $request->boolean('is_active', false),
            'role' => 'admin',
            'login_code_hash' => null,
            'login_code_expires_at' => null,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Usuário administrativo atualizado com sucesso.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->role === 'admin', 404);

        if (Auth::id() === $user->id) {
            return back()->with('error', 'Você não pode excluir o próprio usuário enquanto estiver autenticado.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Usuário administrativo removido com sucesso.');
    }
}
