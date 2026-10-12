<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Dos Factores (2FA) - EduSystem</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-panel {
            background: rgba(30, 41, 59, 0.65);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="h-full flex items-center justify-center p-4 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-indigo-950 via-slate-900 to-slate-950">
    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 via-teal-500 to-cyan-500 shadow-xl shadow-emerald-500/25 mb-4 border border-emerald-400/20">
                <svg class="w-7 h-7 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Verificación 2FA</h1>
            <p class="text-sm text-slate-400 mt-1">Segundo factor de autenticación</p>
        </div>

        <!-- 2FA Challenge Card -->
        <div class="glass-panel rounded-2xl p-7 shadow-2xl shadow-black/50">
            <div class="mb-6 text-center">
                <p class="text-xs text-slate-300">
                    Hola, <span class="font-bold text-white">{{ $user->name }}</span>.
                </p>
                <p class="text-xs text-slate-400 mt-1">
                    Ingresa el código temporal de 6 dígitos generado por tu aplicación móvil o uno de tus códigos de recuperación de respaldo.
                </p>
            </div>

            @if($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2.5">
                    <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('2fa.verify') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="code" class="block text-xs font-semibold text-slate-300 mb-2 text-center uppercase tracking-wider">Código de 6 dígitos o respaldo</label>
                    <input type="text" id="code" name="code" required autofocus autocomplete="one-time-code"
                        placeholder="Ej. 123456 ó ABCDE-12345"
                        class="w-full text-center tracking-widest text-lg font-mono py-3 rounded-xl bg-slate-900/90 border border-slate-700/80 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 text-white placeholder-slate-600 outline-none transition-all">
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-xl font-bold text-sm text-slate-950 bg-gradient-to-r from-emerald-400 to-teal-400 hover:from-emerald-300 hover:to-teal-300 shadow-lg shadow-emerald-500/30 transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Verificar y Acceder</span>
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-800 text-center">
                <a href="{{ route('login') }}" class="text-xs font-medium text-slate-400 hover:text-white transition-colors inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Cancelar y volver al inicio de sesión</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
