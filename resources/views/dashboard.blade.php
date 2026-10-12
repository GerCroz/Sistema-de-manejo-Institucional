@extends('layouts.app')

@section('title', 'Dashboard Principal')

@section('content')
<div class="space-y-6">
    <!-- Header Title & Switch Info -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight">Panel de Control General</h1>
            <p class="text-sm text-slate-400 mt-1">
                Mostrando métricas e información académica para: <strong class="text-indigo-400 font-bold">{{ $activeInstitution->name ?? 'Institución General' }}</strong>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('attendance.index') }}" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tomar Asistencia Hoy
            </a>
        </div>
    </div>

    @if(!empty($isTeacher))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 flex items-center justify-between text-xs font-semibold">
            <div class="flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>Vista Docente: Mostrando estadísticas y alumnos correspondientes a tus materias asignadas.</span>
            </div>
            <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-200 uppercase tracking-wider text-[10px]">Rol Docente</span>
        </div>
    @endif

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Card 1: Total Alumnos -->
        <div class="glass-card p-5 rounded-2xl border border-slate-800 relative overflow-hidden group hover:border-indigo-500/50 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Alumnos</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $totalStudents }}</span>
                <span class="text-xs text-slate-400 ml-2">estudiantes inscritos</span>
            </div>
            <div class="mt-3 text-xs text-emerald-400 font-semibold flex items-center gap-1">
                <span>Activos en {{ $totalCourses }} Cursos</span>
            </div>
        </div>

        <!-- Card 2: Asistencia General -->
        <div class="glass-card p-5 rounded-2xl border border-slate-800 relative overflow-hidden group hover:border-emerald-500/50 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">% Asistencia Promedio</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $avgAttendance }}%</span>
                <span class="text-xs text-slate-400 ml-2">asistencia acumulada</span>
            </div>
            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-3 overflow-hidden">
                <div class="bg-emerald-400 h-1.5 rounded-full" style="width: {{ min(100, $avgAttendance) }}%"></div>
            </div>
        </div>

        <!-- Card 3: Promedio Académico General -->
        <div class="glass-card p-5 rounded-2xl border border-slate-800 relative overflow-hidden group hover:border-violet-500/50 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Promedio de Calificaciones</span>
                <div class="w-10 h-10 rounded-xl bg-violet-500/10 text-violet-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 30a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1h-2a1 1 0 01-1-1v-2z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 012-2h2a2 2 0 012 2v6a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $avgGrade }}</span>
                <span class="text-xs text-slate-400 ml-2">escala 1.0 - 7.0</span>
            </div>
            <div class="mt-3 text-xs text-violet-400 font-semibold">
                <span>Rendimiento general satisfactorio</span>
            </div>
        </div>

        <!-- Card 4: Asignaturas en Curso -->
        <div class="glass-card p-5 rounded-2xl border border-slate-800 relative overflow-hidden group hover:border-amber-500/50 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Asignaturas / Materias</span>
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ $totalSubjects }}</span>
                <span class="text-xs text-slate-400 ml-2">materias configuradas</span>
            </div>
            <div class="mt-3 text-xs text-amber-400 font-semibold">
                <span>Con evaluaciones asignadas</span>
            </div>
        </div>
    </div>

    <!-- Main Section: Risk Alert Students & Recent Attendances -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Students in Risk Panel -->
        <div class="lg:col-span-2 glass-card p-6 rounded-2xl border border-slate-800 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Alumnos en Alerta Temprana
                    </h2>
                    <p class="text-xs text-slate-400">Estudiantes con asistencia &lt; 85% o promedio bajo 4.0</p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                    {{ count($riskStudents) }} Casos
                </span>
            </div>

            @if(count($riskStudents) > 0)
                <div class="divide-y divide-slate-800/60">
                    @foreach($riskStudents as $student)
                        <div class="py-3 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center font-bold text-white text-sm border border-slate-700">
                                    {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                                </div>
                                <div>
                                    <a href="{{ route('students.show', $student->id) }}" class="font-semibold text-sm text-white hover:text-indigo-400 transition-colors">
                                        {{ $student->full_name }}
                                    </a>
                                    <p class="text-xs text-slate-400">{{ $student->course->name ?? 'Sin Curso' }} &bull; {{ $student->rut_dni }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 text-right">
                                <div>
                                    <div class="text-xs font-bold {{ $student->attendance_percentage < 85 ? 'text-rose-400' : 'text-slate-300' }}">
                                        {{ $student->attendance_percentage }}% Asistencia
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        Promedio: {{ $student->average_grade > 0 ? $student->average_grade : 'N/A' }}
                                    </div>
                                </div>
                                <a href="{{ route('students.show', $student->id) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 border border-slate-700 transition-colors">
                                    Ver Ficha
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center text-slate-400 text-sm">
                    <svg class="w-10 h-10 text-emerald-400 mx-auto mb-2 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Excelente: No hay alumnos en alerta académica o de inasistencia en esta institución.
                </div>
            @endif
        </div>

        <!-- Recent Attendances Activity Feed -->
        <div class="glass-card p-6 rounded-2xl border border-slate-800 space-y-4">
            <div class="border-b border-slate-800/80 pb-4">
                <h2 class="text-lg font-bold text-white">Últimos Registros</h2>
                <p class="text-xs text-slate-400">Actividad reciente de asistencias</p>
            </div>

            <div class="space-y-3">
                @forelse($recentAttendances as $att)
                    <div class="p-3 rounded-xl bg-slate-800/40 border border-slate-700/30 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-white block">{{ $att->student->full_name ?? 'Alumno' }}</span>
                            <span class="text-slate-400">{{ $att->course->name ?? '' }} &bull; {{ $att->date }}</span>
                        </div>
                        @if($att->status === 'present')
                            <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold border border-emerald-500/30">Presente</span>
                        @elseif($att->status === 'absent')
                            <span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 font-bold border border-rose-500/30">Ausente</span>
                        @elseif($att->status === 'late')
                            <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30">Atraso</span>
                        @else
                            <span class="px-2 py-0.5 rounded bg-blue-500/20 text-blue-300 font-bold border border-blue-500/30">Justificado</span>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-400 py-4 text-center">Sin registros aún.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
