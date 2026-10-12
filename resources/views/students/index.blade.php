@extends('layouts.app')

@section('title', 'Directorio de Alumnos')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight">Directorio de Alumnos</h1>
            <p class="text-sm text-slate-400 mt-1">Listado de estudiantes matriculados en la institución activa.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('import.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/80 font-semibold text-sm transition-all flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Importar Planilla
            </a>
            <a href="{{ route('students.create') }}" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nuevo Alumno
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="glass-card p-4 rounded-2xl border border-slate-800">
        <form method="GET" action="{{ route('students.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Filtrar por Curso</label>
                <select name="course_id" onchange="this.form.submit()" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                    <option value="">Todos los Cursos</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                            {{ $course->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Buscar por Nombre o RUT/DNI</label>
                <div class="flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Ej. Mateo, 21.450..." class="flex-1 bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                    <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white font-semibold text-sm rounded-xl transition-colors">
                        Buscar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Students Table -->
    <div class="glass-card rounded-2xl border border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-xs font-bold uppercase text-slate-400 bg-slate-800/40">
                        <th class="p-4">Estudiante</th>
                        <th class="p-4">RUT / DNI</th>
                        <th class="p-4">Curso</th>
                        <th class="p-4 text-center">Asistencia %</th>
                        <th class="p-4 text-center">Promedio</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-sm">
                    @forelse($students as $student)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-400 flex items-center justify-center font-bold text-white text-xs">
                                        {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('students.show', $student->id) }}" class="font-bold text-white hover:text-indigo-400 transition-colors">
                                            {{ $student->full_name }}
                                        </a>
                                        <span class="block text-xs text-slate-400">{{ $student->email ?? 'Sin email' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-slate-300 font-mono text-xs font-semibold">
                                @if($student->legajo)
                                    <span class="px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 text-[11px] block w-fit mb-0.5">Leg. {{ $student->legajo }}</span>
                                @endif
                                <span class="text-slate-400 text-[11px]">{{ $student->rut_dni }}</span>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-slate-200 border border-slate-700 text-xs font-semibold">
                                    {{ $student->course->full_name ?? 'Sin asignar' }}
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                @php $attPct = $student->attendance_percentage; @endphp
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $attPct >= 85 ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30' }}">
                                    {{ $attPct }}%
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                @php $avgG = $student->average_grade; @endphp
                                <span class="font-extrabold text-sm {{ $avgG >= 4.0 ? 'text-indigo-300' : ($avgG > 0 ? 'text-rose-400' : 'text-slate-500') }}">
                                    {{ $avgG > 0 ? $avgG : 'N/A' }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('students.show', $student->id) }}" title="Ver Ficha" class="p-2 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/40 text-indigo-300 border border-indigo-500/30 text-xs font-bold transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>

                                    <a href="{{ route('students.edit', $student->id) }}" title="Editar Alumno" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 text-xs font-bold transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>

                                    @if(auth()->user()?->isAdmin())
                                        <button type="button" 
                                            @click="$dispatch('open-delete-modal', {
                                                title: '¿Eliminar al alumno \'{{ addslashes($student->full_name) }}\'?',
                                                message: 'Se borrará la ficha académica completa, incluyendo su historial de asistencias ({{ $student->attendances->count() }}) y todas sus calificaciones registradas.',
                                                action: '{{ route('students.destroy', $student->id) }}',
                                                confirmText: 'Sí, Eliminar Alumno'
                                            })"
                                            title="Eliminar Alumno" class="p-2 rounded-lg bg-slate-800 hover:bg-rose-900/40 text-slate-400 hover:text-rose-300 border border-slate-700 hover:border-rose-700/60 transition-colors cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">
                                No se encontraron alumnos con los criterios seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($students->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $students->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
