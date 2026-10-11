@extends('layouts.app')

@section('title', 'Registrar Nuevo Alumno')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('students.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver al Directorio
        </a>
    </div>

    <div class="glass-card p-6 rounded-2xl border border-slate-800 space-y-6">
        <div class="border-b border-slate-800 pb-4">
            <h1 class="text-xl font-black text-white">Registro de Nuevo Alumno</h1>
            <p class="text-xs text-slate-400 mt-0.5">Ingresa los datos del estudiante para la institución activa.</p>
        </div>

        <form action="{{ route('students.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Nombres *</label>
                    <input type="text" name="first_name" required placeholder="Ej. Sofía Ignacia" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Apellidos *</label>
                    <input type="text" name="last_name" required placeholder="Ej. Valenzuela Rojas" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">RUT / DNI *</label>
                    <input type="text" name="rut_dni" required placeholder="Ej. 21.450.981-2" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Curso Asignado *</label>
                    <select name="course_id" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                        <option value="">Selecciona un Curso</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Email Estudiante</label>
                    <input type="email" name="email" placeholder="estudiante@escuela.edu" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Fecha Nacimiento</label>
                    <input type="date" name="birth_date" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-800">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Nombre Apoderado / Tutor</label>
                    <input type="text" name="guardian_name" placeholder="Nombre completo apoderado" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Teléfono Apoderado</label>
                    <input type="text" name="guardian_phone" placeholder="+56 9 1234 5678" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <a href="{{ route('students.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-sm border border-slate-700 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 transition-all">
                    Guardar Alumno
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
