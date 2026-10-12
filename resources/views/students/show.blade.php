@extends('layouts.app')

@section('title', 'Expediente del Alumno: ' . $student->full_name)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('students.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver al Directorio
        </a>

        @if(auth()->user()?->isAdmin())
            <div class="flex items-center gap-2">
                <a href="{{ route('students.edit', $student->id) }}" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-bold text-slate-200 transition-colors flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Editar Alumno
                </a>
                <button type="button" 
                    @click="$dispatch('open-delete-modal', {
                        title: '¿Eliminar al alumno \'{{ addslashes($student->full_name) }}\'?',
                        message: 'Se borrará la ficha académica completa, incluyendo su historial de asistencias ({{ $student->attendances->count() }}) y todas sus calificaciones registradas.',
                        action: '{{ route('students.destroy', $student->id) }}',
                        confirmText: 'Sí, Eliminar Alumno'
                    })"
                    class="px-3.5 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-bold transition-colors flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Eliminar Alumno
                </button>
            </div>
        @endif
    </div>

    <!-- Header Dossier Card -->
    <div class="glass-card p-6 rounded-2xl border border-slate-800 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center font-black text-2xl text-white shadow-xl shadow-indigo-500/20">
                {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">{{ $student->full_name }}</h1>
                <div class="flex flex-wrap items-center gap-3 mt-1 text-xs text-slate-400">
                    <span class="font-mono bg-slate-800 px-2 py-0.5 rounded border border-slate-700 font-bold text-white">{{ $student->rut_dni }}</span>
                    <span>Curso: <strong class="text-indigo-400 font-bold">{{ $student->course->full_name ?? 'Sin asignar' }}</strong></span>
                    <span>Apoderado: <strong class="text-slate-300 font-semibold">{{ $student->guardian_name ?? 'No registrado' }}</strong></span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 border-t md:border-t-0 md:border-l border-slate-800 pt-4 md:pt-0 md:pl-6">
            <div class="text-center px-3">
                <span class="block text-2xl font-black text-emerald-400">{{ $student->attendance_percentage }}%</span>
                <span class="text-[10px] uppercase font-bold text-slate-400">Asistencia Total</span>
            </div>
            <div class="text-center px-3 border-l border-slate-800">
                <span class="block text-2xl font-black text-indigo-300">{{ $student->average_grade > 0 ? $student->average_grade : 'N/A' }}</span>
                <span class="text-[10px] uppercase font-bold text-slate-400">Promedio General</span>
            </div>
        </div>
    </div>

    <!-- Content Grid: Grades Breakdown & Attendances -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Grades by Subject -->
        <div class="glass-card p-6 rounded-2xl border border-slate-800 space-y-4">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Historial de Calificaciones
            </h2>

            @forelse($gradesBySubject as $subjectName => $grades)
                <div class="p-4 rounded-xl bg-slate-800/40 border border-slate-700/40 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-700/50 pb-2">
                        <span class="font-bold text-sm text-white">{{ $subjectName }}</span>
                        <span class="text-xs font-bold text-indigo-300">Promedio: {{ round($grades->avg('score'), 1) }}</span>
                    </div>
                    <div class="space-y-2">
                        @foreach($grades as $g)
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-300">{{ $g->evaluation->title ?? 'Evaluación' }} ({{ $g->evaluation->weight_percentage }}%)</span>
                                <span class="font-black px-2 py-0.5 rounded {{ $g->score >= 4.0 ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30' }}">
                                    {{ number_format($g->score, 1) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-slate-400 text-xs py-4 text-center">No hay calificaciones registradas aún para este alumno.</p>
            @endforelse
        </div>

        <!-- Attendance Breakdown -->
        <div class="glass-card p-6 rounded-2xl border border-slate-800 space-y-4">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Registro de Asistencia
            </h2>

            <div class="space-y-2">
                @forelse($student->attendances->take(10) as $att)
                    <div class="p-3 rounded-xl bg-slate-800/40 border border-slate-700/30 flex items-center justify-between text-xs">
                        <span class="text-slate-300 font-medium">{{ $att->date }}</span>
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
                    <p class="text-slate-400 text-xs py-4 text-center">No hay registros de asistencia aún.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
