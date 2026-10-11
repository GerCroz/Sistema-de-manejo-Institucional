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
        $allInstitutions = \App\Models\Institution::all();
        $activeInstId = session('active_institution_id', $allInstitutions->first()?->id);
        $currentInst = $allInstitutions->firstWhere('id', $activeInstId) ?? $allInstitutions->first();
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
                    Dashboard
                </a>

                <a href="{{ route('institutions.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('institutions.*') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v12a2 2 0 01-2 2m-6 0h6"></path></svg>
                    Instituciones
                </a>

                <a href="{{ route('teachers.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('teachers.*') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Docentes
                </a>

                <a href="{{ route('courses.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('courses.*') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    Cursos y Materias
                </a>

                <a href="{{ route('students.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('students.*') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Alumnos
                </a>

                <a href="{{ route('attendance.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('attendance.*') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Asistencias
                </a>

                <a href="{{ route('grades.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('grades.*') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Notas y Evaluaciones
                </a>

                <a href="{{ route('import.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('import.*') ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Importar Planilla
                </a>
            </nav>

            <!-- Docente Profile Box -->
            <div class="p-4 border-t border-slate-800/60">
                <div class="p-3 rounded-xl bg-slate-800/40 border border-slate-700/40 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center font-bold text-slate-900 text-xs">
                        CM
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-bold text-white truncate">Prof. Carlos Mendoza</p>
                        <p class="text-[11px] text-slate-400 truncate">Docente Multinstitución</p>
                    </div>
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
                            <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $currentInst->logo_color ?? '#4f46e5' }}"></span>
                            <span class="max-w-[200px] truncate">{{ $currentInst->name ?? 'Seleccionar Institución' }}</span>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-transition class="absolute left-0 mt-2 w-72 glass-card rounded-xl shadow-2xl border border-slate-700/70 py-2 z-50">
                            <div class="px-4 py-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 mb-1">
                                Mis Instituciones ({{ count($allInstitutions) }})
                            </div>
                            @foreach($allInstitutions as $inst)
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
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Año Lectivo 2026
                    </span>
                </div>
            </header>

            <!-- Notification Messages -->
            @if(session('success'))
                <div class="m-6 mb-0 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            <!-- Main Page Section -->
            <main class="flex-1 p-6 space-y-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
