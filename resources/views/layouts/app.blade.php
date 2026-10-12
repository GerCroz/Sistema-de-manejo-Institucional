<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-900 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gestión Escolar Multi-Institución') - Laravel 13</title>
    <!-- Tailwind CSS CDN for instant glassmorphic rendering -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="h-full flex flex-col font-sans antialiased">
    @php
        $authUser = auth()->user();
        $isTeacher = $authUser && ! $authUser->isAdmin();
        
        $allInstitutions = $isTeacher ? $authUser->institutions : \App\Models\Institution::all();
        $activeInstId = session('active_institution_id', $allInstitutions->first()?->id);
        $currentInst = $allInstitutions->firstWhere('id', $activeInstId) ?? $allInstitutions->first();

        $nameParts = explode(' ', $authUser?->name ?? 'Usuario');
        $initials = count($nameParts) >= 2 
            ? mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[1], 0, 1) 
            : mb_substr($authUser?->name ?? 'U', 0, 2);
        $initials = strtoupper($initials);
    @endphp

    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Sidebar Navigation -->
        <aside class="w-full md:w-64 glass-card border-r border-slate-800 flex-shrink-0 flex flex-col">
            <div class="p-5 border-b border-slate-800/60 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center font-black text-xl text-white shadow-lg shadow-indigo-500/30">
                        E
                    </div>
                    <div>
                        <h1 class="font-bold text-base text-white leading-tight">EduSystem</h1>
                        <span class="text-xs text-indigo-400 font-semibold">Laravel 13</span>
                    </div>
                </div>
            </div>

            <!-- Nav Links -->
            <nav class="p-4 space-y-1.5 flex-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('dashboard') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span>Dashboard</span>
                </a>

                {{-- Opciones exclusivas de Administrador --}}
                @if($authUser && $authUser->isAdmin())
                    <a href="{{ route('institutions.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('institutions.*') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v12a2 2 0 01-2 2m-6 0h6"></path></svg>
                        <span>Instituciones</span>
                        <span class="ml-auto text-[10px] uppercase font-bold bg-indigo-500/20 text-indigo-300 px-1.5 py-0.5 rounded">Admin</span>
                    </a>

                    <a href="{{ route('teachers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('teachers.*') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span>Docentes</span>
                        <span class="ml-auto text-[10px] uppercase font-bold bg-indigo-500/20 text-indigo-300 px-1.5 py-0.5 rounded">Admin</span>
                    </a>
                @endif

                {{-- Módulos Académicos --}}
                <a href="{{ route('courses.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('courses.*') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    <span>{{ $isTeacher ? 'Mis Cursos y Materias' : 'Cursos y Materias' }}</span>
                </a>

                <a href="{{ route('students.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('students.*') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span>{{ $isTeacher ? 'Mis Alumnos' : 'Alumnos' }}</span>
                </a>

                <a href="{{ route('attendance.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('attendance.*') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    <span>Asistencias</span>
                </a>

                <a href="{{ route('grades.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('grades.*') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Notas y Evaluaciones</span>
                </a>

                <a href="{{ route('import.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('import.*') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>Importar Planilla</span>
                </a>

                {{-- Seguridad y 2FA --}}
                <div class="pt-2 border-t border-slate-800/40">
                    <a href="{{ route('security.index') }}" class="flex items-center justify-between px-4 py-2.5 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('security.*') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <span>Seguridad (2FA)</span>
                        </div>
                        @if($authUser?->hasTwoFactorEnabled())
                            <span class="w-2 h-2 rounded-full bg-emerald-400 shadow-sm shadow-emerald-400/50" title="2FA Activado"></span>
                        @else
                            <span class="w-2 h-2 rounded-full bg-amber-400/60" title="2FA Inactivo"></span>
                        @endif
                    </a>
                </div>
            </nav>

            <!-- User Profile Box & Logout -->
            <div class="p-4 border-t border-slate-800/60">
                <div class="p-3 rounded-xl bg-slate-800/50 border border-slate-700/50 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 shrink-0 rounded-full {{ $authUser?->isAdmin() ? 'bg-gradient-to-tr from-indigo-500 to-purple-500 text-white' : 'bg-gradient-to-tr from-emerald-500 to-teal-400 text-slate-900' }} flex items-center justify-center font-bold text-xs shadow-md">
                            {{ $initials }}
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-xs font-bold text-white truncate">{{ $authUser?->name ?? 'Usuario' }}</p>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded {{ $authUser?->isAdmin() ? 'bg-indigo-500/20 text-indigo-300' : 'bg-emerald-500/20 text-emerald-300' }}">
                                    {{ $authUser?->isAdmin() ? 'Admin' : 'Docente' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="shrink-0">
                        @csrf
                        <button type="submit" title="Cerrar Sesión" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-700/50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar with Multi-Institution Context Switcher -->
            <header class="h-16 glass-card border-b border-slate-800 px-6 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-3">
                    <span class="text-xs uppercase tracking-wider font-bold text-slate-400">Contexto Activo:</span>
                    
                    <!-- Multi-Institution Switcher Dropdown -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800 border border-slate-700 hover:border-slate-600 text-sm font-semibold text-white transition-all">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $currentInst->logo_color ?? '#4f46e5' }}"></span>
                            <span class="max-w-[200px] truncate">{{ $currentInst->name ?? 'Seleccionar Institución' }}</span>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-transition class="absolute left-0 mt-2 w-72 glass-card rounded-xl shadow-2xl border border-slate-700/70 py-2 z-50">
                            <div class="px-4 py-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 mb-1">
                                {{ $isTeacher ? 'Mis Instituciones' : 'Todas las Instituciones' }} ({{ count($allInstitutions) }})
                            </div>
                            @forelse($allInstitutions as $inst)
                                <form action="{{ route('institutions.switch', $inst->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2.5 text-sm flex items-center justify-between hover:bg-slate-800/80 transition-colors {{ $currentInst && $currentInst->id === $inst->id ? 'bg-indigo-600/20 text-indigo-300 font-semibold' : 'text-slate-300' }}">
                                        <div class="flex items-center gap-2.5 truncate">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $inst->logo_color }}"></span>
                                            <span class="truncate">{{ $inst->name }}</span>
                                        </div>
                                        @if($currentInst && $currentInst->id === $inst->id)
                                            <span class="text-xs bg-indigo-500/20 text-indigo-300 px-2 py-0.5 rounded font-bold">Activa</span>
                                        @endif
                                    </button>
                                </form>
                            @empty
                                <div class="px-4 py-2 text-xs text-slate-400">Sin instituciones asignadas</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('security.index') }}" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $authUser?->hasTwoFactorEnabled() ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-300 border border-slate-700' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span>2FA: {{ $authUser?->hasTwoFactorEnabled() ? 'Activo' : 'Desactivado' }}</span>
                    </a>

                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Año Lectivo 2026
                    </span>
                </div>
            </header>

            <!-- Notification Messages -->
            @if(session('success'))
                <div class="m-6 mb-0 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm flex items-center justify-between shadow-lg shadow-emerald-950/20">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error') || $errors->any())
                <div class="m-6 mb-0 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm flex items-center justify-between shadow-lg shadow-rose-950/20">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span>{{ session('error') ?? $errors->first() }}</span>
                    </div>
                </div>
            @endif

            <!-- Main Page Section -->
            <main class="flex-1 p-6 space-y-6">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Global Confirmation Deletion Modal -->
    <div x-data="{
        open: false,
        title: '',
        message: '',
        confirmText: 'Sí, Eliminar Definitivamente',
        actionUrl: '',
        extraInputs: [],
        showModal(detail) {
            this.title = detail.title || '¿Confirmar eliminación?';
            this.message = detail.message || 'Esta acción no se puede deshacer y borrará los datos asociados de forma permanente.';
            this.actionUrl = detail.action || '';
            this.confirmText = detail.confirmText || 'Sí, Eliminar Definitivamente';
            this.extraInputs = detail.extraInputs || [];
            this.open = true;
        },
        closeModal() {
            this.open = false;
        }
    }"
    @open-delete-modal.window="showModal($event.detail)"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
    style="display: none;">
        <div @click.outside="closeModal()" 
             x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-lg glass-card rounded-2xl border border-rose-500/30 p-6 shadow-2xl shadow-rose-950/50 space-y-5">
            
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/15 border border-rose-500/30 text-rose-400 flex items-center justify-center shrink-0 shadow-lg shadow-rose-500/10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-rose-500/20 text-rose-300 border border-rose-500/30 mb-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-pulse"></span>
                        Acción Destructiva Irreversible
                    </div>
                    <h3 class="text-lg font-bold text-white tracking-tight leading-snug" x-text="title"></h3>
                    <p class="text-xs text-slate-300 mt-2 leading-relaxed" x-text="message"></p>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800 text-slate-400 text-xs flex items-center gap-2.5">
                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Por seguridad, confirma que realmente deseas proceder con la eliminación.</span>
            </div>

            <form :action="actionUrl" method="POST" class="pt-2 flex items-center justify-end gap-3">
                @csrf
                @method('DELETE')
                <template x-for="input in extraInputs" :key="input.name">
                    <input type="hidden" :name="input.name" :value="input.value">
                </template>
                <button type="button" @click="closeModal()" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 hover:text-white text-xs font-bold transition-all">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 text-white text-xs font-bold shadow-lg shadow-rose-600/30 transition-all flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span x-text="confirmText"></span>
                </button>
            </form>
        </div>
    </div>
</body>
</html>
