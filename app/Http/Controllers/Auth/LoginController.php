<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        // is_active is part of the credentials rather than a check after the fact, so a
        // deactivated account fails at the query and is never authenticated even briefly.
        // The error is the same one a wrong password gets, which keeps the form from
        // confirming that a given address exists - see UC-17 and MASTER_SCHEMA.md §2.
        if (Auth::attempt([
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'is_active' => true,
        ], $request->boolean('remember'))) {
            $request->clearRateLimit();
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        $request->hitRateLimit();

        throw ValidationException::withMessages([
            'email' => 'These credentials do not match our records.',
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
