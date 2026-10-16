@extends('layouts.app')

@section('title', 'Importación de Planillas Académicas')

@section('content')
<div class="space-y-8 max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Módulo Automatizado de Carga
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Importar Planilla de Cátedra</h1>
            <p class="text-slate-400 text-sm mt-1">Carga masiva de nómina de alumnos, asistencias por fecha y calificaciones de evaluaciones parciales (PP).</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('students.index') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium border border-slate-700/60 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Ver Alumnos
            </a>
            <a href="{{ route('grades.index') }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Ver Calificaciones
            </a>
        </div>
    </div>

    <!-- Alert / Result Section -->
    @if(session('import_result'))
        @php $res = session('import_result'); @endphp
        <div class="p-6 rounded-2xl bg-emerald-950/40 border border-emerald-500/30 backdrop-blur-xl animate-fade-in shadow-2xl shadow-emerald-950/50">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-emerald-300">¡Importación Exitosa de la Planilla!</h3>
                    <p class="text-emerald-200/80 text-sm mt-0.5">Se procesaron todos los registros de alumnos, asistencias y notas sin conflictos.</p>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5">
                        <div class="p-4 rounded-xl bg-slate-900/60 border border-emerald-500/20">
                            <span class="text-xs text-slate-400 block font-medium">Alumnos Procesados</span>
                            <span class="text-2xl font-black text-white mt-1 block">{{ $res['students_total'] }}</span>
                            <span class="text-[11px] text-emerald-400">{{ $res['students_created'] }} creados / {{ $res['students_updated'] }} actualizados</span>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-900/60 border border-emerald-500/20">
                            <span class="text-xs text-slate-400 block font-medium">Asistencias Creadas</span>
                            <span class="text-2xl font-black text-white mt-1 block">{{ $res['attendances_count'] }}</span>
                            <span class="text-[11px] text-indigo-400">
                                {{ count($res['dates_imported']) > 0 ? count($res['dates_imported']) . ' fechas procesadas' : 'Sin asistencias (Planilla de Notas)' }}
                            </span>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-900/60 border border-emerald-500/20">
                            <span class="text-xs text-slate-400 block font-medium">Notas de Parcial (P1 / PP)</span>
                            <span class="text-2xl font-black text-white mt-1 block">{{ $res['grades_count'] }}</span>
                            <span class="text-[11px] text-amber-400">{{ $res['evaluation_title'] }}</span>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-900/60 border border-emerald-500/20">
                            <span class="text-xs text-slate-400 block font-medium">Carrera / Curso y Materia</span>
                            <span class="text-xs font-bold text-white mt-2 block truncate">{{ $res['subject_name'] }}</span>
                            <span class="text-[11px] text-slate-400 truncate block">{{ $res['course_name'] }}</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-emerald-500/20 flex flex-wrap items-center justify-between gap-3 text-xs">
                        @if(count($res['dates_imported']) > 0)
                            <div class="flex items-center gap-2 text-slate-300">
                                <span class="font-semibold text-emerald-400">Fechas registradas:</span>
                                @foreach($res['dates_imported'] as $dt)
                                    <span class="px-2 py-0.5 rounded bg-slate-800/80 border border-slate-700/50 text-slate-300 font-mono">{{ $dt }}</span>
                                @endforeach
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-slate-300">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>Planilla de notas procesada: se registraron las calificaciones de <strong class="text-emerald-300">{{ $res['evaluation_title'] }}</strong> para la nómina de alumnos.</span>
                            </div>
                        @endif
                        <div class="flex gap-2">
                            <a href="{{ route('students.index', ['course_id' => $res['course_id']]) }}" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold transition-all">
                                Explorar Alumnos &rarr;
                            </a>
                            <a href="{{ route('grades.index', ['subject_id' => $res['subject_id']]) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-emerald-300 border border-emerald-500/30 font-semibold transition-all">
                                Ver Notas &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-950/40 border border-rose-500/30 text-rose-300 text-sm">
            <div class="font-bold flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Ocurrió un error al procesar el archivo:
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-xs text-rose-200/80">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">
            <form action="{{ route('import.process') }}" method="POST" enctype="multipart/form-data" x-data="importModule()" @submit="handleFormSubmit($event)" class="glass-card p-6 md:p-8 rounded-2xl border border-slate-800 space-y-6">
                @csrf

                <!-- Selección de Destino (Institución, Carrera/Curso y Materia) -->
                <div class="glass-card p-5 rounded-2xl border border-slate-800 bg-slate-900/60 space-y-4">
                    <div class="flex items-center gap-2 text-xs font-bold text-indigo-400 uppercase tracking-wider">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Destino Académico de la Importación
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- 1. Institución -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Institución</label>
                            <select name="institution_id" class="w-full bg-slate-800/90 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach($institutions as $inst)
                                    <option value="{{ $inst->id }}" {{ $inst->id == $activeInstId ? 'selected' : '' }}>
                                        {{ $inst->name }} ({{ $inst->code }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Sede o Rectorado destino.</p>
                        </div>

                        <!-- 2. Carrera / Curso -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Carrera / Curso</label>
                                <button type="button" @click="createNewCourse = !createNewCourse; if(createNewCourse){ selectedCourseId = ''; }" class="text-[11px] text-indigo-400 hover:text-indigo-300 font-medium">
                                    <span x-text="createNewCourse ? '← Seleccionar existente' : '+ Crear nueva'"></span>
                                </button>
                            </div>

                            <!-- Selector de Curso existente -->
                            <div x-show="!createNewCourse">
                                <select name="course_id" x-model="selectedCourseId" @change="onCourseChange()" class="w-full bg-slate-800/90 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">-- Detección Automática desde la Planilla --</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->name }} ({{ $course->academic_year }})</option>
                                    @endforeach
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1">Ej. Comisión 21, Tec. Univ. Programación.</p>
                            </div>

                            <!-- Input para nueva carrera/curso -->
                            <div x-show="createNewCourse" x-cloak class="space-y-1">
                                <input type="text" name="new_course_name" x-model="newCourseName" placeholder="Ej: Tec. Univ. en Programación - Com. 21" class="w-full bg-slate-900 border border-indigo-500/60 rounded-xl px-3.5 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <p class="text-[11px] text-indigo-400">Se registrará como nueva Carrera/Curso al importar.</p>
                            </div>
                        </div>

                        <!-- 3. Materia / Asignatura -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Materia / Asignatura</label>
                                <button type="button" @click="createNewSubject = !createNewSubject; if(createNewSubject){ selectedSubjectId = ''; }" class="text-[11px] text-indigo-400 hover:text-indigo-300 font-medium">
                                    <span x-text="createNewSubject ? '← Seleccionar existente' : '+ Crear nueva'"></span>
                                </button>
                            </div>

                            <!-- Selector de Materia existente -->
                            <div x-show="!createNewSubject">
                                <select name="subject_id" x-model="selectedSubjectId" class="w-full bg-slate-800/90 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">-- Detección Automática desde la Planilla --</option>
                                    <template x-for="subj in filteredSubjects" :key="subj.id">
                                        <option :value="subj.id" x-text="subj.code ? subj.name + ' (' + subj.code + ')' : subj.name"></option>
                                    </template>
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1">Filtra según la carrera o curso seleccionado.</p>
                            </div>

                            <!-- Input para nueva materia -->
                            <div x-show="createNewSubject" x-cloak class="space-y-1.5">
                                <div class="flex gap-2">
                                    <input type="text" name="new_subject_name" x-model="newSubjectName" placeholder="Nombre (ej. Programación IV)" class="flex-1 bg-slate-900 border border-indigo-500/60 rounded-xl px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <input type="text" name="new_subject_code" x-model="newSubjectCode" placeholder="Cód." class="w-20 bg-slate-900 border border-indigo-500/60 rounded-xl px-2 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <p class="text-[11px] text-indigo-400">Se registrará como nueva Asignatura al importar.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs: Archivo vs Texto Pegado -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 p-1 bg-slate-800/60 rounded-xl border border-slate-700/60 w-fit">
                        <button type="button" @click="tab = 'file'" :class="tab === 'file' ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white'" class="px-4 py-2 rounded-lg text-xs font-medium transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            Subir Archivo (.csv / .txt)
                        </button>
                        <button type="button" @click="tab = 'paste'" :class="tab === 'paste' ? 'bg-indigo-600 text-white font-semibold shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white'" class="px-4 py-2 rounded-lg text-xs font-medium transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Pegar Texto de Planilla
                        </button>
                    </div>

                    <!-- Mensaje de Validación de Cliente -->
                    <div x-show="clientError" x-cloak class="p-3.5 rounded-xl bg-rose-950/70 border border-rose-500/40 text-rose-200 text-xs flex items-center justify-between gap-3 animate-fade-in shadow-lg shadow-rose-950/30">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-rose-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span x-text="clientError"></span>
                        </div>
                        <button type="button" @click="clientError = ''" class="text-rose-400 hover:text-white p-1 rounded">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Pestaña Archivo (Drag & Drop + Selector) -->
                    <div x-show="tab === 'file'" class="space-y-3">
                        <!-- Input nativo oculto -->
                        <input 
                            type="file" 
                            name="file" 
                            x-ref="fileInput" 
                            accept=".csv,.txt,.tsv" 
                            class="hidden" 
                            @change="handleFileInput($event)"
                        >

                        <!-- Contenedor Interactivo Dropzone -->
                        <div 
                            @click="$refs.fileInput.click()"
                            @dragover.prevent="isDragging = true"
                            @dragenter.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop($event)"
                            :class="{
                                'border-indigo-400 bg-indigo-500/15 ring-4 ring-indigo-500/25 scale-[1.01]': isDragging,
                                'border-emerald-500/40 bg-emerald-950/20': selectedFile && !isDragging,
                                'border-slate-700/80 hover:border-indigo-500/60 bg-slate-800/20 hover:bg-slate-800/40': !isDragging && !selectedFile
                            }"
                            class="relative border-2 border-dashed transition-all duration-200 rounded-2xl p-8 text-center cursor-pointer group"
                        >
                            <!-- Estado: SIN archivo seleccionado -->
                            <div x-show="!selectedFile" class="space-y-4">
                                <div class="w-16 h-16 mx-auto rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 group-hover:scale-110 group-hover:text-indigo-300 transition-all duration-200">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                </div>

                                <div>
                                    <p class="text-base font-bold text-white tracking-wide">
                                        <span x-show="!isDragging">Arrastra tu archivo aquí o haz clic para explorar</span>
                                        <span x-show="isDragging" class="text-indigo-400 font-extrabold animate-pulse">¡Suelta el archivo para cargarlo!</span>
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1.5 max-w-md mx-auto">
                                        Archivos exportados de Excel o Guaraní/Siu delimitados por punto y coma (;) en formato <strong>.CSV</strong> o <strong>.TXT</strong>.
                                    </p>
                                </div>

                                <div class="pt-1">
                                    <button 
                                        type="button" 
                                        @click.stop="$refs.fileInput.click()" 
                                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition-all transform active:scale-95"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                        Seleccionar Archivo del Equipo
                                    </button>
                                </div>
                            </div>

                            <!-- Estado: CON archivo seleccionado -->
                            <div x-show="selectedFile" x-cloak class="p-4 rounded-xl bg-slate-900/90 border border-emerald-500/30 text-left flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <div class="w-12 h-12 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 flex-shrink-0">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="truncate">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-bold text-white truncate" x-text="selectedFile ? selectedFile.name : ''"></h4>
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                                Listo
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-1">
                                            Tamaño: <span class="font-mono text-slate-300 font-semibold" x-text="selectedFile ? formatFileSize(selectedFile.size) : ''"></span> &bull; 
                                            Tipo: <span class="uppercase font-semibold text-slate-300" x-text="selectedFile ? selectedFile.name.split('.').pop() : ''"></span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0" @click.stop>
                                    <button 
                                        type="button" 
                                        @click="$refs.fileInput.click()" 
                                        class="px-3.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold border border-slate-700 transition-all flex items-center gap-1.5"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Cambiar
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="removeFile()" 
                                        class="px-3.5 py-1.5 rounded-lg bg-rose-950/60 hover:bg-rose-900/80 text-rose-300 hover:text-rose-100 text-xs font-semibold border border-rose-500/30 transition-all flex items-center gap-1.5"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Quitar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestaña Pegar Texto -->
                    <div x-show="tab === 'paste'" class="space-y-3" style="display: none;">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="block text-xs font-semibold text-slate-300">Contenido delimitado por punto y coma (;)</label>
                            <div class="flex items-center gap-2 text-xs">
                                <button type="button" onclick="loadSampleProg4()" class="text-emerald-400 hover:text-emerald-300 font-semibold underline">
                                    Cargar ejemplo Programación IV (P1)
                                </button>
                                <span class="text-slate-600">&bull;</span>
                                <button type="button" onclick="loadSampleData()" class="text-indigo-400 hover:text-indigo-300 underline font-medium">
                                    Cargar Sistemas Operativos
                                </button>
                            </div>
                        </div>
                        <textarea id="rawContentTextarea" name="raw_content" rows="10" placeholder=";Alumnos cursando: Programación IV (Tec.Univ.Prog.);;;;&#10;;Comision: 21 - 2026;;;&#10;Legajo;Alumno;P1;P2;P3;R1;R2;R3;Final;&#10;30581;Aguirre, Sira Gretel (*);7;-;-;-;-;-;0;&#10;..." class="w-full bg-slate-900/90 border border-slate-700 rounded-xl p-3 text-xs font-mono text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-between pt-4 border-t border-slate-800">
                    <span class="text-xs text-slate-400">Se validarán los registros de alumnos, asistencias y calificaciones.</span>
                    <button 
                        type="submit" 
                        :disabled="isSubmitting" 
                        class="px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold text-sm shadow-xl shadow-indigo-600/30 transition-all flex items-center gap-2 transform active:scale-95"
                    >
                        <svg x-show="!isSubmitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <svg x-show="isSubmitting" x-cloak class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span x-text="isSubmitting ? 'Procesando Planilla...' : 'Procesar e Importar Planilla'"></span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Info / Sidebar Guide -->
        <div class="space-y-6">
            <div class="glass-card p-6 rounded-2xl border border-slate-800 space-y-4">
                <h3 class="font-bold text-white text-base flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Estructuras Soportadas
                </h3>
                <ul class="space-y-3 text-xs text-slate-300">
                    <li class="flex items-start gap-2">
                        <span class="w-5 h-5 rounded-md bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold flex-shrink-0">1</span>
                        <span><strong>Planillas de Calificaciones (ej. Programación IV):</strong> Formato con <em>Legajo</em>, <em>Alumno</em> y columnas de notas (<em>P1, P2...</em>). Registra la nómina y calificaciones de parciales.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="w-5 h-5 rounded-md bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold flex-shrink-0">2</span>
                        <span><strong>Planillas con Asistencias (ej. Sistemas Operativos):</strong> Formato con columnas de fechas semanales (agosto, septiembre, feriados) y nota <em>PP</em>.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="w-5 h-5 rounded-md bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold flex-shrink-0">3</span>
                        <span><strong>Asignación Flexible:</strong> Puedes seleccionar o crear la Carrera/Curso y Materia manualmente o dejar la detección automática.</span>
                    </li>
                </ul>
            </div>

            <!-- Artisan Command Helper -->
            <div class="glass-card p-6 rounded-2xl border border-slate-800 space-y-3">
                <h4 class="text-xs font-bold text-slate-200 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Comando de Consola Artisan
                </h4>
                <p class="text-xs text-slate-400">También puedes ejecutar la importación directamente en el servidor mediante la terminal:</p>
                <div class="p-3 bg-slate-900 rounded-xl border border-slate-800 text-[11px] font-mono text-emerald-400 select-all">
                    php artisan school:import-sheet
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function importModule() {
    return {
        tab: 'file',
        isDragging: false,
        selectedFile: null,
        clientError: '',
        isSubmitting: false,
        courses: {{ Js::from($courses->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'section' => $c->section, 'academic_year' => $c->academic_year])) }},
        subjects: {{ Js::from($subjects->map(fn($s) => ['id' => $s->id, 'course_id' => $s->course_id, 'name' => $s->name, 'code' => $s->code])) }},
        selectedCourseId: '{{ old('course_id', '') }}',
        selectedSubjectId: '{{ old('subject_id', '') }}',
        createNewCourse: false,
        newCourseName: '',
        createNewSubject: false,
        newSubjectName: '',
        newSubjectCode: '',

        get filteredSubjects() {
            if (!this.selectedCourseId) {
                return this.subjects;
            }
            return this.subjects.filter(s => String(s.course_id) === String(this.selectedCourseId));
        },

        onCourseChange() {
            if (this.selectedCourseId && this.selectedSubjectId) {
                const match = this.subjects.some(s => String(s.id) === String(this.selectedSubjectId) && String(s.course_id) === String(this.selectedCourseId));
                if (!match) {
                    this.selectedSubjectId = '';
                }
            }
        },

        init() {
            window.addEventListener('switch-to-paste', () => {
                this.tab = 'paste';
                this.clientError = '';
            });
        },

        handleFileInput(e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                this.processSelectedFile(files[0]);
            }
        },

        handleDrop(e) {
            this.isDragging = false;
            const files = e.dataTransfer.files;
            if (!files || files.length === 0) return;

            const droppedFile = files[0];
            const lowerName = droppedFile.name.toLowerCase();
            const valid = lowerName.endsWith('.csv') || lowerName.endsWith('.txt') || lowerName.endsWith('.tsv');

            if (!valid) {
                this.clientError = 'El archivo "' + droppedFile.name + '" no tiene una extensión admitida. Solo se aceptan archivos .CSV o .TXT.';
                return;
            }

            try {
                const dt = new DataTransfer();
                dt.items.add(droppedFile);
                if (this.$refs.fileInput) {
                    this.$refs.fileInput.files = dt.files;
                }
            } catch (err) {
                console.warn('DataTransfer not fully supported:', err);
            }

            this.processSelectedFile(droppedFile);
        },

        processSelectedFile(file) {
            this.clientError = '';
            this.selectedFile = file;
        },

        removeFile() {
            this.selectedFile = null;
            this.clientError = '';
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        },

        formatFileSize(bytes) {
            if (!bytes || bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        handleFormSubmit(e) {
            this.clientError = '';

            if (this.tab === 'file') {
                const hasInputFile = this.$refs.fileInput && this.$refs.fileInput.files && this.$refs.fileInput.files.length > 0;
                if (!this.selectedFile && !hasInputFile) {
                    e.preventDefault();
                    this.clientError = 'Por favor, selecciona o arrastra un archivo CSV o TXT antes de importar.';
                    return false;
                }
            } else if (this.tab === 'paste') {
                const text = document.getElementById('rawContentTextarea')?.value.trim();
                if (!text) {
                    e.preventDefault();
                    this.clientError = 'Por favor, ingresa o pega el contenido de la planilla antes de importar.';
                    return false;
                }
            }

            this.isSubmitting = true;
            return true;
        }
    };
}

function loadSampleData() {
    const sample = `PLANILLA DE ASISTENCIA – SISTEMAS OPERATIVOS Y REDES;;;;;;;;;;;;
Año Académico;2026;Período Lectivo;2do Cuatrimestre;;Clases desde;31/08/2026;;;;;;
Ubicación;Rectorado;Modalidad;Presencial;;Horario;Lun 15:00 a 17:00 - Teórico-Práctica;;;;;;
Actividad;SISTEMAS OPERATIVOS Y REDES (208);;;;;;;;;;;
;;;;;;;;;;;;
Nro;Legajo;Apellido y Nombres;Inasist (Just);10, 17,24,31 de ago;7-sept;14-sept;21-sept;28-sept;5-oct;PP;19-oct;26-oct
1;7253;ACOSTA, KARINA AILEN;0.00 (0.00);4;1;1;F;1;1;16;;
2;8007;ALARCON, PATRICIO YAMIL;0.00 (0.00);3;1;1;F;1;1;77,5;;
3;;AQUINO, OMAR IVAN;0.00 (0.00);4;1;1;F;1;1;40,5;;
4;;ARCE, IRINA SOLEDAD;;3;1;1;F;1;1;62,5;;
5;;ARCE, ROBERTO CARLOS;0.00 (0.00);2;0;1;F;0;1;55;;
6;;AREVALO, ANTONIO JUAN;0.00 (0.00);4;1;1;F;0;1;58;;
7;;BAISCH, MAXIMILIANO;0.00 (0.00);4;1;1;F;1;1;70,5;;
8;;BARRIOS, MIQUEAS;;4;1;1;F;1;1;85,5;;
9;;BENITEZ GOMEZ, LAUTARO;0.00 (0.00);0;0;0;F;1;0;;;
10;8010;BENITEZ, IVAN ARIEL;0.00 (0.00);3;1;1;F;1;1;70;;
11;;BLANCO, GERARDO;;4;1;1;F;0;1;92,5;;
12;;BOGADO, GUSTAVO ARIEL;;4;1;1;F;1;1;100;;
13;;BOGADO, RITA BEATRIZ;;4;1;1;F;1;1;80;;
14;;CABRERA, GUSTAVO;;4;1;1;F;1;1;100;;
15;;CABRERA, MARCOS;0.00 (0.00);4;1;1;F;1;1;80;;
16;;CAMACHO, EXEQUIEL;;3;1;1;F;0;1;95;;
17;;CAMPUZANO, BRENDA;0.00 (0.00);4;1;0;F;1;1;85;;
18;7179;CARDOZO, EZEQUIEL;0.00 (0.00);2;1;0;F;1;1;37;;
19;7180;CARDOZO, JUAN IGNACIO;0.00 (0.00);3;0;1;F;1;1;78;;
20;;CASALES, GUSTAVO;;4;1;1;F;1;1;58;;
21;;CASONES, LUCAS GASTÓN;;3;0;0;F;1;0;;;
22;;CERRIZUELA, CONSTANZA;;4;1;1;F;1;1;53;;
23;;CHAVEZ, EDUARDO RENE;;4;0;1;F;1;1;68;;
24;;CHAVEZ, NAHUEL SAMUEL;;4;1;1;F;1;1;85;;
25;;DORREGO, AGUSTINA;0.00 (0.00);2;0;1;F;1;1;100;;
26;;FERNANDEZ, MARIA;0.00 (0.00);3;1;1;F;1;1;78;;
27;;GALEANO, ENZO RENE;;4;1;1;F;1;1;35,5;;
28;;GALEANO, FABIO EZEQUIEL;;4;1;1;F;1;1;50,5;;
29;;GAMARRA, ROMINA;0.00 (0.00);4;1;1;F;1;1;100;;
30;;GARCÍA, JOEL AXEL;0.00 (0.00);4;1;1;F;1;1;75;;
31;;GARCIA, NOELIA RAQUEL;;0;0;0;F;0;0;;;
32;;GARCIA, SEBASTIAN;;4;1;0;F;0;1;90;;
33;;GAVILÁN, FELIPE OSCAR;;4;1;1;F;1;1;69,5;;
34;;GOMEZ, EDGAR EZEQUIEL;0.00 (0.00);4;1;1;F;1;1;62,5;;
35;;GOMEZ, ENZO AGUSTIN;0.00 (0.00);2;1;1;F;1;1;58;;
36;;GOMEZ, GRISELDA;0.00 (0.00);4;1;1;F;1;1;85;;
37;1300;GON, FACUNDO SEBASTIAN;;3;1;1;F;1;1;80,5;;
38;;INSFRAN, BELEN MONICA;;4;1;0;F;0;1;95;;
39;;JARA, MARCELO LUIS;0.00 (0.00);4;1;1;F;1;1;72,5;;
40;;JURCZUK, IVAN;0.00 (0.00);3;1;1;F;1;1;57;;
41;;KUJARCHI, GUIDO EXEQUIEL;0.00 (0.00);4;1;0;F;1;1;90;;
42;;LARROZA, MATIAS;;0;0;0;F;0;0;;;
43;;LEGUIZAMON, HUGO;;0;0;0;F;0;0;;;
44;;LEIVA, ALEJANDRO IVAN;0.00 (0.00);4;1;1;F;1;1;41;;
45;;LEZCANO, MAXIMILIANO;0.00 (0.00);4;1;1;F;0;0;;;
46;;LOPEZ, CARLOS FEDERICO;;4;0;0;F;1;1;80,5;;
47;1333;LOPEZ, PATRICIA RAMONA;0.00 (0.00);0;0;0;F;0;0;;;
48;1350;MARTINEZ, JOSE IGNACIO;;3;1;1;F;1;1;90;;
49;;MONZON, BRIAN NAHUEL;;4;1;1;F;1;1;100;;
50;;OLIVERA, SOFIA;;4;1;0;F;0;1;60,5;;
51;;ORTOLA GONZALEZ, EDUARDO;0.00 (0.00);3;1;1;F;1;1;75,5;;
52;;OVIEDO, FABRICIO GABRIEL;;4;1;0;F;0;0;;;
53;8085;OZUNA, OCTAVIO ADRIAN;;4;1;1;F;0;1;43;;
54;;PAEZ, LEANDRO SAMUEL;0.00 (0.00);4;1;1;F;1;1;50,5;;
55;;PALACIO, RAMIRO;;4;1;1;F;1;1;100;;
56;;PERNOCHI, DEMIAN;0.00 (0.00);4;1;1;F;1;1;85;;
57;;PINTOS, MARIPAZ;0.00 (0.00);4;0;0;F;1;1;77,5;;
58;;PRANTTE, LUCAS MAURICIO;0.00 (0.00);4;1;1;F;1;1;65,5;;
59;;RAMIREZ, SABA EMILIANO;;3;0;0;F;0;0;;;
60;;RODAS, EDUARDO CESAR;0.00 (0.00);3;0;0;F;0;0;;;
61;1432;ROMERO, JIMENA MAGALI;0.00 (0.00);4;1;1;F;0;1;70,5;;
62;;ROMERO, LUCAS EZEQUIEL;0.00 (0.00);4;1;1;F;1;1;100;;
63;;SILVA, LEONARDO DE;;4;1;1;F;1;1;97,5;;
64;;SILVERO, SERGIO RODOLFO;0.00 (0.00);2;0;0;F;0;0;;;
65;;SOSA, CESAR JAVIER;;3;1;1;F;1;1;47;;
66;1466;TORRES, MILAGROS;0.00 (0.00);4;1;1;F;1;1;65,5;;
67;;VARGAS, PABLO ANDRES;;4;1;1;F;0;1;85;;
68;;VERA, FERNANDO MARTIN;0.00 (0.00);4;1;1;F;1;1;80;;
69;;VILLAGRA, NADIA MARLENE;;3;1;0;F;1;1;63;;
70;;VILLANUEVA, ADRIAN;;4;1;1;F;1;1;97,5;;
;;;;;;;;;;;;
Datos;;;;;07/09;14/09;21/09;28/09;05/10;;;
Tema:;;;;;;;;;;;;
Firma docente:;;;;;;;;;;;;`;

    const textarea = document.getElementById('rawContentTextarea');
    if (textarea) {
        textarea.value = sample;
    }
    window.dispatchEvent(new CustomEvent('switch-to-paste'));
}

function loadSampleProg4() {
    const sample = `;Alumnos cursando: Programación IV (Tec.Univ.Prog.);;;;;;;;;
;Comision: 21 - 2026;;;;;;;;;
;;;;;;;;;;
;;;;;;;;;;
Legajo;Alumno;P1;P2;P3;R1;R2;R3;Final;;
30581;Aguirre, Sira Gretel (*);7;-;-;-;-;-;0;;
30628;Antonelli, Lucas Gabriel (*);8;-;-;-;-;-;0;;
30582;Arce, Angel Gabriel (*);8;-;-;-;-;-;0;;
30004;Barcos, Lia Marián;-;-;-;-;-;-;0;;
30584;Benitez, Apolo Salomon (*);5;-;-;-;-;-;0;;
30587;Benitez, Dolores Antonia (*);5;-;-;-;-;-;0;;
30585;Benitez, José Alexander (*);7;-;-;-;-;-;0;;
30586;Benitez, Santiago Miguel (*);6;-;-;-;-;-;0;;
30037;Blanco Wuest, Naldo Fabian (*);9;-;-;-;-;-;0;;
30588;Bordon, Guillermo Anibal (*);6;-;-;-;-;-;0;;
30589;Britez, Jonathan Javier (*);6;-;-;-;-;-;0;;
30590;Budiño, Valeria (*);8;-;-;-;-;-;0;;
30629;Cardozo, Benjamin Emanuel (*);8;-;-;-;-;-;0;;
30592;Cardozo, Diego Fabian (*);8;-;-;-;-;-;0;;
30591;Cardozo, Mauricio Nair (*);8;-;-;-;-;-;0;;
29144;Casanueva, Samuel Dario (*);8;-;-;-;-;-;0;;
30593;Castillo, Rodrigo Fabian (*);8;-;-;-;-;-;0;;
30005;Centurion, Tomas Emanuel (*);6;-;-;-;-;-;0;;
30594;Cislaghi, Gabriel Emilio (*);6;-;-;-;-;-;0;;
31215;Corrales, Ramiro Exequiel (*);9;-;-;-;-;-;0;;
30595;Cuellar, Alex Marcelo (*);6;-;-;-;-;-;0;;
30630;Fernandez, Abraham Danilo (*);8;-;-;-;-;-;0;;
29085;Gabriel, Silvio (*);8;-;-;-;-;-;0;;
30599;Garcia, Santiago Adriel (*);7;-;-;-;-;-;0;;
30631;Gines, Fabricio Facundo (*);8;-;-;-;-;-;0;;
30600;Gonzalez, Enrique Maximiliano (*);9;-;-;-;-;-;0;;
29108;Heretichi, Adriana Raquel (*);8;-;-;-;-;-;0;;
30601;Lopez, Gabriela Elvira (*);6;-;-;-;-;-;0;;
30603;Medina, Nadia Carolina (*);7;-;-;-;-;-;0;;
30604;Mendoza, Fabian Nicolas (*);7;-;-;-;-;-;0;;
30608;Molina, Patricia Elizabeth (*);6;-;-;-;-;-;0;;
30610;Nieva, Marcelo Fernando (*);8;-;-;-;-;-;0;;
30611;Nuñez, Matias Daniel (*);7;-;-;-;-;-;0;;
30612;Olmedo, Ivan Alejandro de Jesús (*);5;-;-;-;-;-;0;;
30633;Ortiz, Marcos Andres Ramon (*);6;-;-;-;-;-;0;;
30614;Oviedo, Micaela Tomasa (*);7;-;-;-;-;-;0;;
30615;Pineda, Antonio Gabriel (*);9;-;-;-;-;-;0;;
30009;Portillo, Anahí Elizabeth (*);6;-;-;-;-;-;0;;
30616;Quiroga, Alejandro Fabian (*);9;-;-;-;-;-;0;;
30010;Rahn, Ana Karen (*);7;-;-;-;-;-;0;;
30617;Riveros, María Laura (*);9;-;-;-;-;-;0;;
30618;Rojas Jojot, Jorge Luis (*);8;-;-;-;-;-;0;;
30013;Rojas, Yasmin Beatriz (*);8;-;-;-;-;-;0;;
30619;Sanchez, Sergio Martín Laureano (*);8;-;-;-;-;-;0;;
30620;Saucedo, Alexia Mariel (*);8;-;-;-;-;-;0;;
30621;Servian, Sixto Manuel (*);8;-;-;-;-;-;0;;
29091;Silvera, Cristian Anibal (*);7;-;-;-;-;-;0;;
30622;Soria del Valle, Sergio Nicolas (*);7;-;-;-;-;-;0;;
30626;Zaracho, Fernando Daniel;-;-;-;-;-;-;0;;`;

    const textarea = document.getElementById('rawContentTextarea');
    if (textarea) {
        textarea.value = sample;
    }
    window.dispatchEvent(new CustomEvent('switch-to-paste'));
}
</script>
@endsection
