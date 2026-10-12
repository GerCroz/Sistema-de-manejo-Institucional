@extends('layouts.app')

@section('title', 'Notas y Evaluaciones')

@section('content')
<div class="space-y-6" x-data="{ showModal: false }">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white tracking-tight">Gestión de Calificaciones</h1>
            <p class="text-sm text-slate-400 mt-1">Crea evaluaciones ponderadas e ingresa notas por asignatura y curso.</p>
        </div>
        <div>
            <button @click="showModal = true" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nueva Evaluación
            </button>
        </div>
    </div>

    <!-- Subject Filter Header -->
    <div class="glass-card p-5 rounded-2xl border border-slate-800">
        <form method="GET" action="{{ route('grades.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Seleccionar Asignatura / Materia</label>
                    <select name="subject_id" onchange="this.form.submit()" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-indigo-500 font-semibold">
                        @foreach($subjects as $sub)
                            <option value="{{ $sub->id }}" {{ $selectedSubjectId == $sub->id ? 'selected' : '' }}>
                                {{ $sub->name }} — {{ $sub->course->full_name ?? '' }} (Docente: {{ $sub->teacher_name }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- Evaluations & Grades Matrix Table -->
    @if($selectedSubject)
        <div class="glass-card rounded-2xl border border-slate-800 overflow-hidden space-y-4">
            <div class="p-5 border-b border-slate-800/80 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-white">{{ $selectedSubject->name }} — {{ $selectedSubject->course->full_name ?? '' }}</h2>
                    <span class="text-xs text-slate-400">{{ count($selectedSubject->evaluations) }} Evaluaciones Ponderadas creadas</span>
                </div>
            </div>

            <!-- List of Evaluations -->
            <div class="px-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($selectedSubject->evaluations as $eval)
                    <div class="p-3.5 rounded-xl bg-slate-800/50 border border-slate-700/50 flex flex-col justify-between group hover:border-slate-600 transition-all">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="font-bold text-xs text-white leading-tight">{{ $eval->title }}</span>
                                <span class="px-2 py-0.5 rounded text-[11px] font-extrabold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 shrink-0">
                                    {{ $eval->weight_percentage }}%
                                </span>
                            </div>
                            <span class="text-[11px] text-slate-400 block mt-1.5">Fecha: {{ $eval->date }}</span>
                        </div>

                        <div class="mt-3 pt-2.5 border-t border-slate-700/50 flex items-center justify-between text-xs">
                            <span class="text-[11px] text-slate-400 font-mono">{{ $eval->grades->count() }} notas</span>
                            <button type="button" 
                                @click="$dispatch('open-delete-modal', {
                                    title: '¿Eliminar evaluación \'{{ addslashes($eval->title) }}\'?',
                                    message: 'Se borrarán de forma definitiva todas las notas registradas de los alumnos en esta evaluación ({{ $eval->grades->count() }} notas asociadas).',
                                    action: '{{ route('grades.destroyEvaluation', $eval->id) }}',
                                    confirmText: 'Sí, Eliminar Evaluación'
                                })"
                                class="p-1 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-950/30 transition-colors cursor-pointer" title="Eliminar Evaluación">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Grade Matrix Entry Form -->
            @if(count($selectedSubject->evaluations) > 0)
                <div class="pt-4 border-t border-slate-800">
                    <div class="px-5 pb-2">
                        <h3 class="text-sm font-bold text-white">Ingreso Masivo de Notas (Escala 1.0 - 7.0)</h3>
                    </div>

                    @foreach($selectedSubject->evaluations as $eval)
                        <form action="{{ route('grades.storeGrades') }}" method="POST" class="p-5 border-t border-slate-800/60 bg-slate-800/20 space-y-3">
                            @csrf
                            <input type="hidden" name="evaluation_id" value="{{ $eval->id }}">

                            <div class="flex items-center justify-between">
                                <span class="font-bold text-sm text-indigo-300">Ingresando para: {{ $eval->title }} ({{ $eval->weight_percentage }}%)</span>
                                <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition-all">
                                    Guardar Notas
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($selectedSubject->course->students as $student)
                                    @php
                                        $existingScore = $eval->grades->firstWhere('student_id', $student->id)?->score;
                                    @endphp
                                    <div class="p-2.5 rounded-xl bg-slate-800/60 border border-slate-700/50 flex items-center justify-between gap-3">
                                        <div class="overflow-hidden">
                                            <span class="block text-xs font-bold text-white truncate">{{ $student->full_name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono">{{ $student->rut_dni }}</span>
                                        </div>
                                        <input type="number" step="0.1" min="1.0" max="7.0" name="scores[{{ $student->id }}]" value="{{ $existingScore }}" placeholder="1.0-7.0" class="w-16 bg-slate-900 border border-slate-700 rounded-lg px-2 py-1 text-center text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                                    </div>
                                @endforeach
                            </div>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <!-- Create Evaluation Modal -->
    <div x-show="showModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4" x-transition>
        <div class="glass-card w-full max-w-lg p-6 rounded-2xl border border-slate-700 space-y-4" @click.outside="showModal = false">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-lg font-bold text-white">Nueva Evaluación Ponderada</h3>
                <button @click="showModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <form action="{{ route('grades.storeEvaluation') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $selectedSubjectId }}">

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Título de la Evaluación</label>
                    <input type="text" name="title" required placeholder="Ej. Examen Parcial 2" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Ponderación (%)</label>
                        <input type="number" name="weight_percentage" required min="1" max="100" value="25" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Fecha</label>
                        <input type="date" name="date" required value="{{ date('Y-m-d') }}" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="pt-3 flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold">Crear Evaluación</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
