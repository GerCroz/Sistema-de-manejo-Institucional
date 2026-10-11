@extends('layouts.app')

@section('title', 'Directorio de Maestros y Docentes')

@section('content')
<div x-data="{
    createModal: false,
    editModal: false,
    editing: { id: '', name: '', email: '', title: '', role: 'teacher', institution_ids: [] },
    openEdit(teacher, currentInstIds, currentRole) {
        this.editing = {
            id: teacher.id,
            name: teacher.name,
            email: teacher.email,
            title: teacher.title || '',
            role: currentRole || 'teacher',
            institution_ids: currentInstIds || []
        };
        this.editModal = true;
    }
}" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Gestión de Personal Docente • Multi-Tenant
            </div>
            <h1 class="text-3xl font-black text-white tracking-tight">Cuerpo Docente y Maestros</h1>
            <p class="text-sm text-slate-400 mt-1">Administra los profesores, sus especialidades y las instituciones donde imparten clases.</p>
        </div>

        <div>
            <button @click="createModal = true" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-semibold text-sm shadow-lg shadow-emerald-600/30 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nuevo Maestro / Docente
            </button>
        </div>
    </div>

    <!-- Feedback messages -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-950/40 border border-emerald-500/30 text-emerald-300 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-950/40 border border-rose-500/30 text-rose-300 text-sm">
            <ul class="list-disc list-inside space-y-1 text-xs">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Teachers Table -->
    <div class="glass-card rounded-2xl border border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-xs font-bold uppercase text-slate-400 bg-slate-800/40">
                        <th class="p-4">Docente</th>
                        <th class="p-4">Título / Especialidad</th>
                        <th class="p-4">Instituciones Asignadas</th>
                        <th class="p-4">Rol en Tenant</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-sm">
                    @forelse($teachers as $teacher)
                        @php
                            $instIds = $teacher->institutions->pluck('id')->toArray();
                            $firstRole = $teacher->institutions->first()?->pivot?->role ?? 'teacher';
                        @endphp
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center font-bold text-white text-sm shadow-md">
                                        {{ substr($teacher->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-white leading-tight">{{ $teacher->name }}</h3>
                                        <span class="text-xs text-slate-400 font-mono">{{ $teacher->email }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="p-4">
                                <span class="text-slate-300 text-xs font-medium">
                                    {{ $teacher->title ?: 'Docente Titular' }}
                                </span>
                            </td>

                            <td class="p-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($teacher->institutions as $inst)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-800/80 border border-slate-700/60 text-xs font-semibold text-slate-200">
                                            <span class="w-2 h-2 rounded-full" style="background-color: {{ $inst->logo_color }}"></span>
                                            {{ $inst->code }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-500 italic">Sin asignación</span>
                                    @endforelse
                                </div>
                            </td>

                            <td class="p-4">
                                @if($firstRole === 'admin')
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40">
                                        Administrador
                                    </span>
                                @elseif($firstRole === 'coordinator')
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40">
                                        Coordinador
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                                        Docente
                                    </span>
                                @endif
                            </td>

                            <td class="p-4 text-right space-x-2">
                                <button type="button" @click="openEdit({{ json_encode($teacher) }}, {{ json_encode($instIds) }}, '{{ $firstRole }}')" class="p-2 text-slate-400 hover:text-indigo-300 hover:bg-slate-800 rounded-lg transition-all" title="Editar Docente">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>

                                <form action="{{ route('teachers.destroy', $teacher->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Seguro que deseas eliminar este docente?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition-all" title="Eliminar Docente">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-500">
                                No hay docentes registrados todavía. ¡Comienza haciendo clic en "Nuevo Maestro / Docente"!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL CREAR MAESTRO / DOCENTE -->
    <div x-show="createModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="createModal = false" class="glass-card max-w-lg w-full rounded-2xl border border-slate-700 p-6 md:p-8 space-y-5 animate-fade-in">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-lg font-extrabold text-white flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-emerald-600/30 text-emerald-400 flex items-center justify-center text-sm">👨‍🏫</span>
                    Registrar Nuevo Maestro / Docente
                </h3>
                <button type="button" @click="createModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <form action="{{ route('teachers.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Nombre Completo *</label>
                    <input type="text" name="name" required placeholder="Ej. Prof. Andrea Villalba" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Email Institucional *</label>
                        <input type="email" name="email" required placeholder="andrea.villalba@escuela.edu" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Título o Especialidad</label>
                        <input type="text" name="title" placeholder="Ej. Lic. en Sistemas" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Contraseña de Acceso (Opcional)</label>
                    <input type="password" name="password" placeholder="Por defecto: password123" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <!-- Asignación de Instituciones -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Asignar a Instituciones (Multi-Tenancy) *</label>
                    <div class="space-y-2 max-h-36 overflow-y-auto p-3 rounded-xl bg-slate-900 border border-slate-800">
                        @foreach($institutions as $inst)
                            <label class="flex items-center gap-3 text-xs text-slate-300 cursor-pointer hover:text-white">
                                <input type="checkbox" name="institution_ids[]" value="{{ $inst->id }}" {{ $inst->id == $activeInstId ? 'checked' : '' }} class="rounded border-slate-700 text-emerald-600 focus:ring-emerald-500">
                                <span class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $inst->logo_color }}"></span>
                                    <span>{{ $inst->name }} ({{ $inst->code }})</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Rol en la Institución *</label>
                    <select name="role" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="teacher" selected>Docente / Profesor</option>
                        <option value="coordinator">Coordinador Académico</option>
                        <option value="admin">Administrador de Tenant</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="button" @click="createModal = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-lg shadow-emerald-600/30">Registrar Docente</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR MAESTRO / DOCENTE -->
    <div x-show="editModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editModal = false" class="glass-card max-w-lg w-full rounded-2xl border border-slate-700 p-6 md:p-8 space-y-5 animate-fade-in">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-lg font-extrabold text-white flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-emerald-600/30 text-emerald-400 flex items-center justify-center text-sm">✏️</span>
                    Editar Docente
                </h3>
                <button type="button" @click="editModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <form :action="'/teachers/' + editing.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Nombre Completo *</label>
                    <input type="text" name="name" x-model="editing.name" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Email Institucional *</label>
                        <input type="email" name="email" x-model="editing.email" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Título o Especialidad</label>
                        <input type="text" name="title" x-model="editing.title" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Nueva Contraseña (Opcional)</label>
                    <input type="password" name="password" placeholder="Dejar en blanco para no modificar" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <!-- Asignación de Instituciones -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Asignar a Instituciones *</label>
                    <div class="space-y-2 max-h-36 overflow-y-auto p-3 rounded-xl bg-slate-900 border border-slate-800">
                        @foreach($institutions as $inst)
                            <label class="flex items-center gap-3 text-xs text-slate-300 cursor-pointer hover:text-white">
                                <input type="checkbox" name="institution_ids[]" value="{{ $inst->id }}" :checked="editing.institution_ids.includes({{ $inst->id }})" class="rounded border-slate-700 text-emerald-600 focus:ring-emerald-500">
                                <span class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $inst->logo_color }}"></span>
                                    <span>{{ $inst->name }} ({{ $inst->code }})</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Rol Asignado *</label>
                    <select name="role" x-model="editing.role" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="teacher">Docente / Profesor</option>
                        <option value="coordinator">Coordinador Académico</option>
                        <option value="admin">Administrador de Tenant</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-lg shadow-emerald-600/30">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
