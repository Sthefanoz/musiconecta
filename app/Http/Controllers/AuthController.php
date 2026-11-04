<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credenciales, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Las credenciales no son correctas.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended($this->inicioSegunRol($request->user()));
    }

    public function showRegister(Request $request): View
    {
        return view('auth.register', ['rol' => $request->query('rol', 'cliente')]);
    }

    public function register(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'rol' => ['required', Rule::in([Rol::Cliente->value, Rol::Musico->value])],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create($datos);
        Auth::login($user);
        $request->session()->regenerate();

        return $user->esMusico()
            ? redirect()->route('musico.perfil.edit')->with('ok', '¡Bienvenido! Completa tu perfil de músico.')
            : redirect()->route('home')->with('ok', '¡Bienvenido a MusiConecta!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function inicioSegunRol(User $user): string
    {
        return match ($user->rol) {
            Rol::Admin => route('admin.dashboard'),
            Rol::Musico => route('musico.panel'),
            default => route('home'),
        };
    }
}
