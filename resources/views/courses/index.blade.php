@extends('layouts.app')

@section('title', 'Cursos y Materias - ' . $institution->name)

@section('content')
<div x-data="{
    createCourseModal: false,
    editCourseModal: false,
    addSubjectModal: false,
    editSubjectModal: false,
    editingCourse: { id: '', name: '', section: '', academic_year: '2026' },
    activeCourseForSubject: { id: '', name: '' },
    editingSubject: { id: '', name: '', code: '', teacher_id: '', course_name: '' },
    teachersList: {{ Js::from($teachers->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'title' => $t->title ?: 'Docente'])) }},
    showNewTeacherInAdd: false,
    showNewTeacherInEdit: false,
    newTeacher: { name: '', email: '', title: '' },
    quickTeacherLoading: false,
    quickTeacherError: '',

    openEditCourse(id, name, section, academicYear) {
        this.editingCourse = {
            id: id,
            name: name,
            section: section || '',
            academic_year: academicYear || '2026'
        };
        this.editCourseModal = true;
    },
    openAddSubject(id, name) {
        this.activeCourseForSubject = {
            id: id,
            name: name
        };
        this.showNewTeacherInAdd = false;
        this.quickTeacherError = '';
        this.newTeacher = { name: '', email: '', title: '' };
        this.addSubjectModal = true;
    },
    openEditSubject(subject) {
        this.editingSubject = {
            id: subject.id,
            name: subject.name,
            code: subject.code || '',
            teacher_id: subject.teacher_id ? String(subject.teacher_id) : '',
            course_name: subject.course_name || ''
        };
        this.showNewTeacherInEdit = false;
        this.quickTeacherError = '';
        this.newTeacher = { name: '', email: '', title: '' };
        this.editSubjectModal = true;
    },
    async quickCreateTeacher(context) {
        this.quickTeacherError = '';
        if (!this.newTeacher.name.trim() || !this.newTeacher.email.trim()) {
            this.quickTeacherError = 'Por favor ingresa nombre y correo electrónico del docente.';
            return;
        }
        this.quickTeacherLoading = true;
        try {
            const res = await fetch('{{ route('teachers.quick') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    name: this.newTeacher.name,
                    email: this.newTeacher.email,
                    title: this.newTeacher.title,
                    institution_id: {{ $institution->id }}
                })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.teachersList.push({
                    id: data.teacher.id,
                    name: data.teacher.name,
                    title: data.teacher.title
                });
                this.teachersList.sort((a, b) => a.name.localeCompare(b.name));

                if (context === 'add') {
                    this.$nextTick(() => {
                        const select = document.getElementById('addSubjectTeacherSelect');
                        if (select) select.value = data.teacher.id;
                    });
                    this.showNewTeacherInAdd = false;
                } else if (context === 'edit') {
                    this.editingSubject.teacher_id = String(data.teacher.id);
                    this.showNewTeacherInEdit = false;
                }
                this.newTeacher = { name: '', email: '', title: '' };
            } else {
                this.quickTeacherError = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Error al registrar el docente.');
            }
        } catch (err) {
            this.quickTeacherError = 'Error de red o conexión al registrar el docente.';
        } finally {
            this.quickTeacherLoading = false;
        }
    }
}" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                Gestión Curricular • {{ $institution->name }}
            </div>
            <h1 class="text-3xl font-black text-white tracking-tight">Cursos y Asignaturas</h1>
            <p class="text-sm text-slate-400 mt-1">Administra los grupos académicos, años lectivos y la distribución de materias y profesores asignados.</p>
        </div>

        @if(auth()->user()?->isAdmin())
            <div>
                <button @click="createCourseModal = true" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Nuevo Curso
                </button>
            </div>
        @endif
    </div>

    <!-- Error / Alert handling -->
    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-950/40 border border-rose-500/30 text-rose-300 text-sm">
            <ul class="list-disc list-inside space-y-1 text-xs">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Course Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @forelse($courses as $course)
            <div class="glass-card rounded-2xl border border-slate-800 p-6 flex flex-col justify-between hover:border-slate-700/80 transition-all">
                <div>
                    <!-- Course Header -->
                    <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-800/80">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="w-3 h-3 rounded-full bg-indigo-500 shadow-sm shadow-indigo-500/50"></span>
                                <h2 class="text-xl font-black text-white tracking-tight">{{ $course->name }}</h2>
                                @if($course->section)
                                    <span class="px-2.5 py-0.5 rounded-md bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 text-xs font-bold">
                                        División {{ $course->section }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 mt-2 text-xs text-slate-400">
                                <span>Ciclo Lectivo: <strong class="text-slate-200">{{ $course->academic_year }}</strong></span>
                                <span>•</span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    <strong class="text-slate-200">{{ $course->students_count }}</strong> alumnos
                                </span>
                                <span>•</span>
                                <span><strong class="text-slate-200">{{ $course->subjects_count }}</strong> materias</span>
                            </div>
                        </div>

                        <!-- Course Actions -->
                        @if(auth()->user()?->isAdmin())
                            <div class="flex items-center gap-1.5">
                                <button @click="openEditCourse({{ $course->id }}, '{{ addslashes($course->name) }}', '{{ addslashes($course->section ?? '') }}', '{{ addslashes($course->academic_year) }}')" title="Editar Curso" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 hover:text-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>

                                <button type="button" 
                                    @click="$dispatch('open-delete-modal', {
                                        title: '¿Eliminar curso \'{{ addslashes($course->full_name) }}\'?',
                                        message: 'Se eliminará el curso completo, incluyendo sus {{ $course->subjects_count }} asignaturas y las inscripciones académicas vinculadas.',
                                        action: '{{ route('courses.destroy', $course->id) }}',
                                        confirmText: 'Sí, Eliminar Curso'
                                    })"
                                    title="Eliminar Curso" class="p-2 rounded-lg bg-slate-800 hover:bg-rose-900/40 border border-slate-700 hover:border-rose-700/60 text-slate-400 hover:text-rose-300 transition-colors cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Subjects List inside this course -->
                    <div class="mt-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Asignaturas Impartidas</span>
                            @if(auth()->user()?->isAdmin())
                                <button @click="openAddSubject({{ $course->id }}, '{{ addslashes($course->full_name) }}')" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Agregar Materia
                                </button>
                            @endif
                        </div>

                        <div class="space-y-2">
                            @forelse($course->subjects as $subject)
                                <div class="p-3 rounded-xl bg-slate-800/40 border border-slate-700/40 flex items-center justify-between hover:bg-slate-800/70 transition-colors">
                                    <div class="min-w-0 pr-3">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-bold text-white truncate">{{ $subject->name }}</h4>
                                            @if($subject->code)
                                                <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-700 text-slate-300 font-semibold">{{ $subject->code }}</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-1 truncate">
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            <span>{{ $subject->teacher_name ?: 'Sin docente asignado' }}</span>
                                        </p>
                                    </div>

                                    @if(auth()->user()?->isAdmin())
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            <button type="button" 
                                                @click="openEditSubject({
                                                    id: {{ $subject->id }},
                                                    name: '{{ addslashes($subject->name) }}',
                                                    code: '{{ addslashes($subject->code ?? '') }}',
                                                    teacher_id: '{{ $subject->teacher_id ?? '' }}',
                                                    course_name: '{{ addslashes($course->full_name) }}'
                                                })"
                                                title="Editar Materia y Asignar Docente" 
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-400 hover:bg-slate-700/60 transition-colors cursor-pointer"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>

                                            <button type="button" 
                                                @click="$dispatch('open-delete-modal', {
                                                    title: '¿Eliminar asignatura \'{{ addslashes($subject->name) }}\'?',
                                                    message: 'Se borrarán de forma definitiva todas las evaluaciones y calificaciones asociadas a esta materia.',
                                                    action: '{{ route('subjects.destroy', $subject->id) }}',
                                                    confirmText: 'Sí, Eliminar Asignatura'
                                                })"
                                                title="Quitar Asignatura" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-400 hover:bg-rose-950/30 transition-colors cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="p-4 rounded-xl border border-dashed border-slate-700/60 text-center">
                                    <p class="text-xs text-slate-400">No hay materias registradas aún en este curso.</p>
                                    @if(auth()->user()?->isAdmin())
                                        <button @click="openAddSubject({{ $course->id }}, '{{ addslashes($course->full_name) }}')" class="mt-2 text-xs font-semibold text-indigo-400 hover:underline">
                                            + Registrar la primera materia
                                        </button>
                                    @endif
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Footer with shortcuts -->
                <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                    <span class="text-slate-400">ID Curso: #{{ $course->id }}</span>
                    <a href="{{ route('students.index', ['course_id' => $course->id]) }}" class="text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1">
                        Ver Alumnos
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="lg:col-span-2 glass-card rounded-2xl border border-slate-800 p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <h3 class="text-lg font-bold text-white">No hay cursos registrados en esta institución</h3>
                <p class="text-sm text-slate-400 max-w-md mx-auto mt-1 mb-6">Comienza creando tu primer curso (por ejemplo, 5to Año, 1er Grado, etc.) para luego organizar materias y cargar estudiantes.</p>
                <button @click="createCourseModal = true" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 transition-all inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Crear Primer Curso
                </button>
            </div>
        @endforelse
    </div>

    <!-- Modal: Crear Curso -->
    <div x-show="createCourseModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" style="display: none;">
        <div @click.outside="createCourseModal = false" class="w-full max-w-md glass-card rounded-2xl border border-slate-700 shadow-2xl p-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <h3 class="text-lg font-bold text-white">Crear Nuevo Curso</h3>
                <button @click="createCourseModal = false" class="text-slate-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form action="{{ route('courses.store') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Nombre del Curso *</label>
                    <input type="text" name="name" required placeholder="Ej: 5to Año Secundaria, 1er Grado" class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-400 mb-1">División / Sección</label>
                        <input type="text" name="section" placeholder="Ej: A, B, Única" class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Ciclo Lectivo *</label>
                        <input type="text" name="academic_year" value="2026" required class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" @click="createCourseModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white text-sm font-semibold">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30">
                        Guardar Curso
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Editar Curso -->
    <div x-show="editCourseModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" style="display: none;">
        <div @click.outside="editCourseModal = false" class="w-full max-w-md glass-card rounded-2xl border border-slate-700 shadow-2xl p-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <h3 class="text-lg font-bold text-white">Editar Curso</h3>
                <button @click="editCourseModal = false" class="text-slate-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form :action="'{{ url('courses') }}/' + editingCourse.id" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Nombre del Curso *</label>
                    <input type="text" name="name" x-model="editingCourse.name" required class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-400 mb-1">División / Sección</label>
                        <input type="text" name="section" x-model="editingCourse.section" class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Ciclo Lectivo *</label>
                        <input type="text" name="academic_year" x-model="editingCourse.academic_year" required class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" @click="editCourseModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white text-sm font-semibold">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30">
                        Actualizar Curso
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Agregar Materia / Asignatura -->
    <div x-show="addSubjectModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" style="display: none;">
        <div @click.outside="addSubjectModal = false" class="w-full max-w-lg glass-card rounded-2xl border border-slate-700 shadow-2xl p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <div>
                    <h3 class="text-lg font-bold text-white">Agregar Asignatura</h3>
                    <p class="text-xs text-indigo-400 mt-0.5" x-text="'Para: ' + activeCourseForSubject.name"></p>
                </div>
                <button @click="addSubjectModal = false" class="text-slate-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form :action="'{{ url('courses') }}/' + activeCourseForSubject.id + '/subjects'" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Nombre de la Asignatura *</label>
                    <input type="text" name="name" required placeholder="Ej: Sistemas Operativos, Matemática, Redes" class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Código de Materia (Opcional)</label>
                    <input type="text" name="code" placeholder="Ej: SO-2026, MAT-01" class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500">
                </div>

                <!-- Sección Docente -->
                <div class="pt-2 border-t border-slate-800/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold uppercase text-slate-400">Docente Asignado</label>
                        <button type="button" @click="showNewTeacherInAdd = !showNewTeacherInAdd; quickTeacherError = ''" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1 transition-colors">
                            <span x-show="!showNewTeacherInAdd">+ Registrar Nuevo Docente</span>
                            <span x-show="showNewTeacherInAdd" class="text-slate-400">&larr; Seleccionar de la lista</span>
                        </button>
                    </div>

                    <!-- Dropdown de selección -->
                    <div x-show="!showNewTeacherInAdd">
                        <select name="teacher_id" id="addSubjectTeacherSelect" class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:outline-none focus:border-indigo-500">
                            <option value="">-- Sin docente asignado / Por asignar --</option>
                            <template x-for="tch in teachersList" :key="tch.id">
                                <option :value="tch.id" x-text="tch.name + ' (' + (tch.title || 'Docente') + ')'"></option>
                            </template>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">El docente asignado podrá registrar asistencias y calificar en esta materia.</p>
                    </div>

                    <!-- Formulario desplegable para registrar nuevo docente en caso de no existir -->
                    <div x-show="showNewTeacherInAdd" class="p-4 rounded-xl bg-indigo-950/30 border border-indigo-500/30 space-y-3 animate-fade-in">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-indigo-300 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                Nuevo Docente para esta Asignatura
                            </h4>
                        </div>

                        <div x-show="quickTeacherError" class="p-2.5 rounded-lg bg-rose-950/50 border border-rose-500/40 text-rose-300 text-xs" x-text="quickTeacherError"></div>

                        <div class="space-y-2.5">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-300 mb-0.5">Nombre Completo *</label>
                                <input type="text" name="new_teacher_name" x-model="newTeacher.name" placeholder="Ej: Lic. Martín Rodríguez" class="w-full px-3 py-2 rounded-lg bg-slate-900/90 border border-slate-700 text-white text-xs placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-300 mb-0.5">Correo Electrónico *</label>
                                <input type="email" name="new_teacher_email" x-model="newTeacher.email" placeholder="mrodriguez@escuela.test" class="w-full px-3 py-2 rounded-lg bg-slate-900/90 border border-slate-700 text-white text-xs placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-300 mb-0.5">Título / Especialidad (Opcional)</label>
                                <input type="text" name="new_teacher_title" x-model="newTeacher.title" placeholder="Ej: Especialista en Redes, Prof. Titular" class="w-full px-3 py-2 rounded-lg bg-slate-900/90 border border-slate-700 text-white text-xs placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                            </div>
                        </div>

                        <div class="pt-1 flex items-center justify-between">
                            <span class="text-[10px] text-slate-400">Contraseña temporal: <code>password123</code></span>
                            <button type="button" @click="quickCreateTeacher('add')" :disabled="quickTeacherLoading" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow transition-all flex items-center gap-1.5 disabled:opacity-50">
                                <svg x-show="quickTeacherLoading" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Crear y Vincular Ahora</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-800">
                    <button type="button" @click="addSubjectModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white text-sm font-semibold">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30">
                        Guardar Asignatura
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Editar Materia / Asignatura -->
    <div x-show="editSubjectModal" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" style="display: none;">
        <div @click.outside="editSubjectModal = false" class="w-full max-w-lg glass-card rounded-2xl border border-slate-700 shadow-2xl p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <div>
                    <h3 class="text-lg font-bold text-white">Editar Asignatura</h3>
                    <p class="text-xs text-indigo-400 mt-0.5" x-text="'Curso: ' + editingSubject.course_name"></p>
                </div>
                <button @click="editSubjectModal = false" class="text-slate-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form :action="'{{ url('subjects') }}/' + editingSubject.id" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Nombre de la Asignatura *</label>
                    <input type="text" name="name" x-model="editingSubject.name" required placeholder="Ej: Sistemas Operativos, Matemática" class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Código de Materia (Opcional)</label>
                    <input type="text" name="code" x-model="editingSubject.code" placeholder="Ej: SO-2026, MAT-01" class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500">
                </div>

                <!-- Sección Docente -->
                <div class="pt-2 border-t border-slate-800/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold uppercase text-slate-400">Docente Asignado</label>
                        <button type="button" @click="showNewTeacherInEdit = !showNewTeacherInEdit; quickTeacherError = ''" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1 transition-colors">
                            <span x-show="!showNewTeacherInEdit">+ Registrar Nuevo Docente</span>
                            <span x-show="showNewTeacherInEdit" class="text-slate-400">&larr; Seleccionar de la lista</span>
                        </button>
                    </div>

                    <!-- Dropdown de selección -->
                    <div x-show="!showNewTeacherInEdit">
                        <select name="teacher_id" x-model="editingSubject.teacher_id" class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:outline-none focus:border-indigo-500">
                            <option value="">-- Sin docente asignado / Por asignar --</option>
                            <template x-for="tch in teachersList" :key="tch.id">
                                <option :value="String(tch.id)" :selected="editingSubject.teacher_id == String(tch.id)" x-text="tch.name + ' (' + (tch.title || 'Docente') + ')'"></option>
                            </template>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">El docente asignado podrá registrar asistencias y calificar en esta materia.</p>
                    </div>

                    <!-- Formulario desplegable para registrar nuevo docente en caso de no existir -->
                    <div x-show="showNewTeacherInEdit" class="p-4 rounded-xl bg-indigo-950/30 border border-indigo-500/30 space-y-3 animate-fade-in">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-indigo-300 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                Nuevo Docente para esta Asignatura
                            </h4>
                        </div>

                        <div x-show="quickTeacherError" class="p-2.5 rounded-lg bg-rose-950/50 border border-rose-500/40 text-rose-300 text-xs" x-text="quickTeacherError"></div>

                        <div class="space-y-2.5">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-300 mb-0.5">Nombre Completo *</label>
                                <input type="text" name="new_teacher_name" x-model="newTeacher.name" placeholder="Ej: Lic. Martín Rodríguez" class="w-full px-3 py-2 rounded-lg bg-slate-900/90 border border-slate-700 text-white text-xs placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-300 mb-0.5">Correo Electrónico *</label>
                                <input type="email" name="new_teacher_email" x-model="newTeacher.email" placeholder="mrodriguez@escuela.test" class="w-full px-3 py-2 rounded-lg bg-slate-900/90 border border-slate-700 text-white text-xs placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-300 mb-0.5">Título / Especialidad (Opcional)</label>
                                <input type="text" name="new_teacher_title" x-model="newTeacher.title" placeholder="Ej: Especialista en Redes, Prof. Titular" class="w-full px-3 py-2 rounded-lg bg-slate-900/90 border border-slate-700 text-white text-xs placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                            </div>
                        </div>

                        <div class="pt-1 flex items-center justify-between">
                            <span class="text-[10px] text-slate-400">Contraseña temporal: <code>password123</code></span>
                            <button type="button" @click="quickCreateTeacher('edit')" :disabled="quickTeacherLoading" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow transition-all flex items-center gap-1.5 disabled:opacity-50">
                                <svg x-show="quickTeacherLoading" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Crear y Vincular Ahora</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-800">
                    <button type="button" @click="editSubjectModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white text-sm font-semibold">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30">
                        Actualizar Asignatura
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
