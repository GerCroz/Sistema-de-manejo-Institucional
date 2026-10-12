<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Muestra la pantalla de inicio de sesión.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Procesa las credenciales del usuario con protección de tasa de intentos (rate limit).
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = Str::transliterate(Str::lower($validated['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Demasiados intentos fallidos. Por favor, reintenta en {$seconds} segundos.",
            ])->onlyInput('email');
        }

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'email' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);

        // Si el usuario tiene 2FA activado, solicitar segundo factor
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('auth.2fa.user_id', $user->id);
            $request->session()->put('auth.2fa.remember', $request->boolean('remember'));

            return redirect()->route('2fa.challenge');
        }

        // Inicio de sesión directo si no requiere 2FA
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Establecer institución activa por defecto asociada al usuario
        $defaultInstitution = $user->institutions()->first() ?? Institution::first();
        if ($defaultInstitution) {
            session(['active_institution_id' => $defaultInstitution->id]);
        }

        return redirect()->intended(route('dashboard'))->with('success', "Bienvenido/a al sistema, {$user->name}");
    }

    /**
     * Cierra la sesión activa.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Has cerrado tu sesión con éxito.');
    }
}
