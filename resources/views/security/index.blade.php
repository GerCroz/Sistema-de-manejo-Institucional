@extends('layouts.app')

@section('title', 'Seguridad y 2FA')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                <span class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </span>
                Seguridad de la Cuenta y 2FA
            </h1>
            <p class="text-sm text-slate-400 mt-1">Configuración de autenticación de dos factores y resguardo de identidad</p>
        </div>

        <div class="flex items-center gap-2">
            @if($user->hasTwoFactorEnabled())
                <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    2FA Habilitado y Activo
                </span>
            @else
                <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    2FA Desactivado
                </span>
            @endif
        </div>
    </div>

    <!-- User Security Profile Card -->
    <div class="glass-card rounded-2xl p-6 border border-slate-800">
        <h2 class="text-base font-bold text-white mb-4">Información de la Cuenta</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 rounded-xl bg-slate-800/40 border border-slate-700/40">
                <span class="text-xs text-slate-400 font-medium">Nombre Completo</span>
                <p class="text-sm font-semibold text-white mt-1">{{ $user->name }}</p>
            </div>
            <div class="p-4 rounded-xl bg-slate-800/40 border border-slate-700/40">
                <span class="text-xs text-slate-400 font-medium">Correo Electrónico</span>
                <p class="text-sm font-semibold text-white mt-1">{{ $user->email }}</p>
            </div>
            <div class="p-4 rounded-xl bg-slate-800/40 border border-slate-700/40">
                <span class="text-xs text-slate-400 font-medium">Rol y Permisos</span>
                <div class="mt-1">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-bold {{ $user->isAdmin() ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' }}">
                        {{ $user->isAdmin() ? 'Administrador General' : 'Docente Titular' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    @if(!$user->hasTwoFactorEnabled())
        <!-- Activar 2FA: Flujo de Configuración -->
        <div class="glass-card rounded-2xl p-6 border border-slate-800 space-y-6">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Activar Autenticación en Dos Pasos (TOTP)
                </h2>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Añade una capa extra de protección a tu cuenta solicitando un código de 6 dígitos cada vez que inicies sesión. Compatible con Google Authenticator, Authy, 1Password, Microsoft Authenticator o cualquier app TOTP estándar.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <!-- Paso 1: Escaneo de QR o clave manual -->
                <div class="p-5 rounded-xl bg-slate-800/60 border border-slate-700/60 flex flex-col items-center text-center">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-400 mb-3">Paso 1: Escanear Código QR</span>
                    
                    <div class="p-3 bg-white rounded-2xl shadow-xl shadow-black/40 mb-4">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($otpAuthUrl) }}" alt="QR Code 2FA" class="w-44 h-44 object-contain">
                    </div>

                    <p class="text-xs text-slate-400 mb-2">O ingresa manualmente esta clave secreta:</p>
                    <div class="flex items-center gap-2 w-full max-w-xs">
                        <code class="flex-1 py-1.5 px-3 bg-slate-900 rounded-lg text-xs font-mono font-bold text-indigo-300 border border-slate-700 select-all text-center">
                            {{ chunk_split($pendingSecret, 4, ' ') }}
                        </code>
                        <button type="button" onclick="navigator.clipboard.writeText('{{ $pendingSecret }}'); alert('¡Clave secreta copiada!');" class="p-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-slate-200 transition-colors" title="Copiar clave">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Paso 2: Confirmación con código y password -->
                <div class="p-5 rounded-xl bg-slate-800/60 border border-slate-700/60">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-400 block mb-3">Paso 2: Confirmar Activación</span>
                    
                    <form action="{{ route('security.2fa.confirm') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label for="code" class="block text-xs font-semibold text-slate-300 mb-1">Código de 6 dígitos de la App</label>
                            <input type="text" id="code" name="code" required maxlength="6" placeholder="000000" autocomplete="off"
                                class="w-full text-center tracking-widest font-mono text-base py-2.5 rounded-xl bg-slate-900 border border-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white outline-none">
                            <span class="text-[11px] text-slate-500 mt-1 block">Ingresa el código temporal mostrado en tu app autenticadora.</span>
                        </div>

                        <div>
                            <label for="password" class="block text-xs font-semibold text-slate-300 mb-1">Contraseña Actual</label>
                            <input type="password" id="password" name="password" required placeholder="Tu contraseña actual"
                                class="w-full py-2.5 px-3.5 rounded-xl bg-slate-900 border border-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-sm text-white outline-none">
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <span>Confirmar y Activar 2FA</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <!-- 2FA ya está activo: Códigos de Respaldo y Opción de Desactivar -->
        <div class="glass-card rounded-2xl p-6 border border-emerald-500/30 space-y-6">
            <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300">
                <svg class="w-6 h-6 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                <div>
                    <h3 class="text-sm font-bold text-white">Autenticación de Dos Factores Activa</h3>
                    <p class="text-xs text-emerald-400/90 mt-0.5">Tu cuenta está protegida. Cada inicio de sesión requiere tu clave y el código TOTP temporal.</p>
                </div>
            </div>

            <!-- Códigos de Recuperación -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-white">Códigos de Recuperación de Respaldo</h3>
                        <p class="text-xs text-slate-400">Guarda estos códigos en un lugar seguro. Si pierdes tu celular, podrás usarlos para ingresar.</p>
                    </div>
                    <button type="button" onclick="copyRecoveryCodes()" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-indigo-300 border border-slate-700 flex items-center gap-1.5 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        <span>Copiar Códigos</span>
                    </button>
                </div>

                <div id="recoveryCodesList" class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-4 rounded-xl bg-slate-950/70 border border-slate-800">
                    @forelse($user->two_factor_recovery_codes ?? [] as $code)
                        <div class="font-mono text-xs font-bold text-slate-300 text-center py-2 px-1 rounded-lg bg-slate-900/90 border border-slate-800/80">
                            {{ $code }}
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 col-span-4 text-center py-2">No hay códigos generados actualmente.</p>
                    @endforelse
                </div>

                <!-- Formulario Regenerar Códigos -->
                <div class="mt-4 pt-4 border-t border-slate-800/80" x-data="{ open: false }">
                    <button @click="open = !open" type="button" class="text-xs text-slate-400 hover:text-indigo-300 flex items-center gap-1 font-semibold transition-colors">
                        <span>¿Deseas generar nuevos códigos de recuperación?</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>

                    <form x-show="open" action="{{ route('security.2fa.recovery_codes') }}" method="POST" class="mt-3 flex items-center gap-3 max-w-md">
                        @csrf
                        <input type="password" name="password" required placeholder="Confirma tu contraseña" class="flex-1 py-1.5 px-3 rounded-lg bg-slate-900 border border-slate-700 text-xs text-white outline-none">
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-colors">
                            Regenerar
                        </button>
                    </form>
                </div>
            </div>

            <!-- Desactivar 2FA -->
            <div class="pt-5 border-t border-slate-800" x-data="{ openDisable: false }">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-rose-300">Desactivar 2FA</h4>
                        <p class="text-xs text-slate-400">Si desactivas esta opción, tu cuenta solo requerirá contraseña para ingresar.</p>
                    </div>
                    <button @click="openDisable = !openDisable" type="button" class="px-3 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-bold transition-colors">
                        Desactivar...
                    </button>
                </div>

                <form x-show="openDisable" action="{{ route('security.2fa.disable') }}" method="POST" class="mt-4 p-4 rounded-xl bg-rose-950/20 border border-rose-500/20 flex items-center gap-3 max-w-md">
                    @csrf
                    <input type="password" name="password" required placeholder="Ingresa tu contraseña para confirmar" class="flex-1 py-1.5 px-3 rounded-lg bg-slate-900 border border-slate-700 text-xs text-white outline-none">
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition-colors">
                        Confirmar Desactivación
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>

<script>
function copyRecoveryCodes() {
    const codes = Array.from(document.querySelectorAll('#recoveryCodesList div')).map(el => el.textContent.trim()).join('\n');
    navigator.clipboard.writeText(codes).then(() => {
        alert('¡Códigos de recuperación copiados al portapapeles!');
    });
}
</script>
@endsection
