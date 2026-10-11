@extends('layouts.app')

@section('title', 'Control de Asistencias')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight">Registro de Asistencias Diarias</h1>
            <p class="text-sm text-slate-400 mt-1">Pasa lista rápido por curso y fecha. Los cambios se guardan al instante.</p>
        </div>
    </div>

    <!-- Course & Date Filter Header -->
    <div class="glass-card p-5 rounded-2xl border border-slate-800">
        <form method="GET" action="{{ route('attendance.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Seleccionar Curso</label>
                <select name="course_id" onchange="this.form.submit()" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500 font-semibold">
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ $selectedCourseId == $course->id ? 'selected' : '' }}>
                            {{ $course->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Fecha de la Clase</label>
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500 font-semibold">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white font-semibold text-sm rounded-xl transition-colors">
                    Actualizar Lista
                </button>
            </div>
        </form>
    </div>

    <!-- Fast Roll Call Table Form -->
    @if($selectedCourse)
        <div class="glass-card rounded-2xl border border-slate-800 overflow-hidden">
            <div class="p-5 border-b border-slate-800/80 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-white">Nómina: {{ $selectedCourse->full_name }}</h2>
                    <span class="text-xs text-slate-400">Fecha: {{ $date }} &bull; {{ count($students) }} Alumnos</span>
                </div>
            </div>

            <form action="{{ route('attendance.store') }}" method="POST">
                @csrf
                <input type="hidden" name="course_id" value="{{ $selectedCourseId }}">
                <input type="hidden" name="date" value="{{ $date }}">

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-xs font-bold uppercase text-slate-400 bg-slate-800/40">
                                <th class="p-4">Alumno</th>
                                <th class="p-4">RUT/DNI</th>
                                <th class="p-4 text-center">Estado de Asistencia</th>
                                <th class="p-4">Observaciones / Nota</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-sm">
                            @forelse($students as $student)
                                @php
                                    $currentStatus = $existingAttendances[$student->id]->status ?? 'present';
                                    $currentNotes = $existingAttendances[$student->id]->notes ?? '';
                                @endphp
                                <tr class="hover:bg-slate-800/30 transition-colors">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center font-bold text-white text-xs border border-slate-700">
                                                {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                                            </div>
                                            <span class="font-bold text-white">{{ $student->full_name }}</span>
                                        </div>
                                    </td>
                                    <td class="p-4 text-xs font-mono text-slate-400">{{ $student->rut_dni }}</td>
                                    <td class="p-4">
                                        <div class="flex items-center justify-center gap-1.5" x-data="{ status: '{{ $currentStatus }}' }">
                                            <input type="hidden" name="attendances[{{ $student->id }}][status]" :value="status">
                                            
                                            <!-- Presente -->
                                            <button type="button" @click="status = 'present'" :class="status === 'present' ? 'bg-emerald-500 text-slate-900 border-emerald-400 font-extrabold shadow-lg shadow-emerald-500/20' : 'bg-slate-800 text-slate-400 border-slate-700 hover:text-white'" class="px-3 py-1.5 rounded-lg border text-xs transition-all">
                                                Presente
                                            </button>

                                            <!-- Ausente -->
                                            <button type="button" @click="status = 'absent'" :class="status === 'absent' ? 'bg-rose-500 text-white border-rose-400 font-extrabold shadow-lg shadow-rose-500/20' : 'bg-slate-800 text-slate-400 border-slate-700 hover:text-white'" class="px-3 py-1.5 rounded-lg border text-xs transition-all">
                                                Ausente
                                            </button>

                                            <!-- Atraso -->
                                            <button type="button" @click="status = 'late'" :class="status === 'late' ? 'bg-amber-500 text-slate-900 border-amber-400 font-extrabold shadow-lg shadow-amber-500/20' : 'bg-slate-800 text-slate-400 border-slate-700 hover:text-white'" class="px-3 py-1.5 rounded-lg border text-xs transition-all">
                                                Atraso
                                            </button>

                                            <!-- Justificado -->
                                            <button type="button" @click="status = 'justified'" :class="status === 'justified' ? 'bg-blue-500 text-white border-blue-400 font-extrabold shadow-lg shadow-blue-500/20' : 'bg-slate-800 text-slate-400 border-slate-700 hover:text-white'" class="px-3 py-1.5 rounded-lg border text-xs transition-all">
                                                Justificado
                                            </button>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <input type="text" name="attendances[{{ $student->id }}][notes]" value="{{ $currentNotes }}" placeholder="Observación (opcional)..." class="w-full bg-slate-800/80 border border-slate-700/80 rounded-lg px-3 py-1.5 text-xs text-white focus:outline-none focus:border-indigo-500">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-8 text-center text-slate-400">
                                        No hay alumnos registrados en este curso.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(count($students) > 0)
                    <div class="p-5 border-t border-slate-800 flex justify-end">
                        <button type="submit" class="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm shadow-xl shadow-emerald-600/30 transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Guardar Asistencias de la Clase
                        </button>
                    </div>
                @endif
            </form>
        </div>
    @endif
</div>
@endsection
