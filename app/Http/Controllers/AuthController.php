<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $segundos = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Muitas tentativas. Tente novamente em {$segundos} segundos.",
            ]);
        }

        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'email' => 'Credenciais inválidas.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        if ($request->user()->profile?->type === 'admin') {
            return redirect()->intended(route('admin.users.index'));
        }

        $request->session()->forget('url.intended');

        return redirect()->route('home');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}