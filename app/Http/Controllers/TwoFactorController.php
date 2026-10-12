<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TwoFactorController extends Controller
{
    /**
     * Muestra la pantalla de desafío 2FA tras validar las credenciales.
     */
    public function showChallenge()
    {
        $userId = session('auth.2fa.user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.2fa-challenge', compact('user'));
    }

    /**
     * Valida el código TOTP o código de recuperación.
     */
    public function verifyChallenge(Request $request, TwoFactorAuthService $service)
    {
        $userId = session('auth.2fa.user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);

        $request->validate([
            'code' => 'required|string',
        ]);

        $code = trim($request->input('code'));
        $isValid = false;

        // 1. Intentar validar como código TOTP de 6 dígitos
        if (strlen($code) === 6 && ctype_digit($code) && ! empty($user->two_factor_secret)) {
            $isValid = $service->verify($user->two_factor_secret, $code);
        }

        // 2. Si no fue válido como TOTP, intentar como código de respaldo
        if (! $isValid) {
            $isValid = $service->verifyAndConsumeRecoveryCode($user, $code);
        }

        if (! $isValid) {
            return back()->withErrors([
                'code' => 'El código de verificación o código de respaldo no es válido.',
            ]);
        }

        // Autenticar al usuario
        $remember = session('auth.2fa.remember', false);
        session()->forget(['auth.2fa.user_id', 'auth.2fa.remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $defaultInstitution = $user->institutions()->first() ?? Institution::first();
        if ($defaultInstitution) {
            session(['active_institution_id' => $defaultInstitution->id]);
        }

        return redirect()->intended(route('dashboard'))->with('success', "Acceso seguro verificado. ¡Bienvenido, {$user->name}!");
    }

    /**
     * Muestra el panel de configuración de seguridad y 2FA del usuario activo.
     */
    public function showSecurity(Request $request, TwoFactorAuthService $service)
    {
        $user = $request->user();

        $pendingSecret = null;
        $otpAuthUrl = null;

        if (! $user->hasTwoFactorEnabled()) {
            $pendingSecret = session('2fa_pending_secret') ?? $service->generateSecretKey();
            session(['2fa_pending_secret' => $pendingSecret]);
            $appName = config('app.name', 'EduSystem');
            $otpAuthUrl = $service->getOtpAuthUrl($appName, $user->email, $pendingSecret);
        }

        return view('security.index', compact('user', 'pendingSecret', 'otpAuthUrl'));
    }

    /**
     * Confirma y activa el 2FA con el primer código TOTP ingresado.
     */
    public function confirm(Request $request, TwoFactorAuthService $service)
    {
        $user = $request->user();

        $request->validate([
            'code' => 'required|string|size:6',
            'password' => 'required|string',
        ]);

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'La contraseña actual no es correcta.']);
        }

        $secret = session('2fa_pending_secret');

        if (! $secret || ! $service->verify($secret, $request->code)) {
            return back()->withErrors(['code' => 'El código de 6 dígitos no coincide con la aplicación autenticadora.']);
        }

        $recoveryCodes = $service->generateRecoveryCodes(8);

        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->two_factor_confirmed_at = now();
        $user->save();

        session()->forget('2fa_pending_secret');

        return redirect()->route('security.index')->with('success', '¡Autenticación en Dos Pasos (2FA) activada con éxito! Guarda tus códigos de respaldo en un lugar seguro.');
    }

    /**
     * Desactiva el 2FA solicitando confirmación de contraseña.
     */
    public function disable(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'password' => 'required|string',
        ]);

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'La contraseña actual no es correcta.']);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return redirect()->route('security.index')->with('success', 'La autenticación de dos factores (2FA) ha sido desactivada.');
    }

    /**
     * Regenera nuevos códigos de recuperación.
     */
    public function regenerateRecoveryCodes(Request $request, TwoFactorAuthService $service)
    {
        $user = $request->user();

        $request->validate([
            'password' => 'required|string',
        ]);

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'La contraseña actual no es correcta.']);
        }

        $user->two_factor_recovery_codes = $service->generateRecoveryCodes(8);
        $user->save();

        return redirect()->route('security.index')->with('success', 'Nuevos códigos de recuperación generados correctamente.');
    }
}
