@extends('layouts.app')

@section('title', 'Gestión de Instituciones (Multi-Tenant)')

@section('content')
<div x-data="{
    createModal: false,
    editModal: false,
    editing: { id: '', name: '', code: '', address: '', phone: '', email: '', logo_color: '#6366f1' },
    openEdit(inst) {
        this.editing = { ...inst };
        this.editModal = true;
    }
}" class="space-y-6">
    <!-- Top Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v12a2 2 0 01-2 2m-6 0h6"></path></svg>
                Nivel Global • Multi-Tenancy
            </div>
            <h1 class="text-3xl font-black text-white tracking-tight">Instituciones Educativas</h1>
            <p class="text-sm text-slate-400 mt-1">Administra los colegios, institutos y facultades registrados en la plataforma.</p>
        </div>

        <div>
            <button @click="createModal = true" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nueva Institución
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

    <!-- Institutions Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($institutions as $inst)
            <div class="glass-card rounded-2xl border border-slate-800 p-6 flex flex-col justify-between relative overflow-hidden group hover:border-indigo-500/50 transition-all {{ $currentInstitutionId === $inst->id ? 'ring-2 ring-indigo-500/40' : '' }}">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="w-4 h-4 rounded-full shadow-lg" style="background-color: {{ $inst->logo_color }}"></span>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider font-mono">{{ $inst->code }}</span>
                        </div>
                        @if($currentInstitutionId === $inst->id)
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                Contexto Activo
                            </span>
                        @endif
                    </div>

                    <div>
                        <h2 class="text-xl font-extrabold text-white group-hover:text-indigo-400 transition-colors leading-tight">{{ $inst->name }}</h2>
                        <p class="text-xs text-slate-400 mt-1 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                            <span class="truncate">{{ $inst->address ?? 'Dirección no especificada' }}</span>
                        </p>
                        @if($inst->email || $inst->phone)
                            <div class="mt-2 text-[11px] text-slate-400 flex flex-wrap gap-3">
                                @if($inst->email) <span>✉ {{ $inst->email }}</span> @endif
                                @if($inst->phone) <span>☎ {{ $inst->phone }}</span> @endif
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-3 gap-2 py-3 border-y border-slate-800/80 text-center">
                        <div class="p-2.5 rounded-xl bg-slate-800/50">
                            <span class="block text-xl font-black text-white">{{ $inst->courses_count }}</span>
                            <span class="text-[10px] uppercase font-bold text-slate-400">Cursos</span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-800/50">
                            <span class="block text-xl font-black text-white">{{ $inst->students_count }}</span>
                            <span class="text-[10px] uppercase font-bold text-slate-400">Alumnos</span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-800/50">
                            <span class="block text-xl font-black text-white">{{ $inst->subjects_count }}</span>
                            <span class="text-[10px] uppercase font-bold text-slate-400">Materias</span>
                        </div>
                    </div>
                </div>

                <div class="mt-5 space-y-2">
                    <form action="{{ route('institutions.switch', $inst->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all {{ $currentInstitutionId === $inst->id ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/40 cursor-default' : 'bg-slate-800 hover:bg-slate-700 text-white border border-slate-700' }}">
                            {{ $currentInstitutionId === $inst->id ? '✓ Institución Seleccionada' : 'Seleccionar Esta Institución' }}
                        </button>
                    </form>

                    <div class="flex items-center justify-end gap-2 pt-1 text-xs">
                        <button type="button" @click="openEdit({{ json_encode($inst) }})" class="px-2.5 py-1 text-slate-400 hover:text-indigo-300 hover:bg-slate-800/60 rounded-lg transition-all flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Editar
                        </button>

                        @if($institutions->count() > 1)
                            <form action="{{ route('institutions.destroy', $inst->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta institución? Se eliminarán también sus cursos y alumnos asociados.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1 text-slate-400 hover:text-rose-400 hover:bg-slate-800/60 rounded-lg transition-all flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Eliminar
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- MODAL CREAR INSTITUCIÓN -->
    <div x-show="createModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="createModal = false" class="glass-card max-w-lg w-full rounded-2xl border border-slate-700 p-6 md:p-8 space-y-5 animate-fade-in">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-lg font-extrabold text-white flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-indigo-600/30 text-indigo-400 flex items-center justify-center text-sm">🏫</span>
                    Registrar Nueva Institución
                </h3>
                <button type="button" @click="createModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <form action="{{ route('institutions.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Nombre Oficial *</label>
                    <input type="text" name="name" required placeholder="Ej. Facultad de Ciencias Exactas" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Código Único *</label>
                        <input type="text" name="code" required placeholder="Ej. FCE-UNF" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white uppercase font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Color Distintivo</label>
                        <input type="color" name="logo_color" value="#6366f1" class="w-full h-10 bg-slate-900 border border-slate-700 rounded-xl p-1 cursor-pointer">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Dirección / Campus</label>
                    <input type="text" name="address" placeholder="Ej. Av. Gutnisky 3200" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Teléfono</label>
                        <input type="text" name="phone" placeholder="+54 370 4452000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Email de Contacto</label>
                        <input type="email" name="email" placeholder="contacto@institucion.edu" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="button" @click="createModal = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/30">Guardar Institución</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR INSTITUCIÓN -->
    <div x-show="editModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="editModal = false" class="glass-card max-w-lg w-full rounded-2xl border border-slate-700 p-6 md:p-8 space-y-5 animate-fade-in">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-lg font-extrabold text-white flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-indigo-600/30 text-indigo-400 flex items-center justify-center text-sm">✏️</span>
                    Editar Institución
                </h3>
                <button type="button" @click="editModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <form :action="'/institutions/' + editing.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Nombre Oficial *</label>
                    <input type="text" name="name" x-model="editing.name" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Código Único *</label>
                        <input type="text" name="code" x-model="editing.code" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white uppercase font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Color Distintivo</label>
                        <input type="color" name="logo_color" x-model="editing.logo_color" class="w-full h-10 bg-slate-900 border border-slate-700 rounded-xl p-1 cursor-pointer">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Dirección / Campus</label>
                    <input type="text" name="address" x-model="editing.address" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Teléfono</label>
                        <input type="text" name="phone" x-model="editing.phone" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Email de Contacto</label>
                        <input type="email" name="email" x-model="editing.email" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/30">Actualizar Institución</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
