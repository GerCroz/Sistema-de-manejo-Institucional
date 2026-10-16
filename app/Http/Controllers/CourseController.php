<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Institution;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CourseController extends Controller
{
    /**
     * Muestra la estructura de cursos y materias de la institución activa.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $activeInstId = session('active_institution_id', Institution::first()?->id);
        $institution = Institution::findOrFail($activeInstId);

        if ($user && ! $user->isAdmin()) {
            // Un docente solo ve los cursos que contienen sus asignaturas y solo sus asignaturas asignadas
            $courses = Course::where('institution_id', $activeInstId)
                ->whereHas('subjects', fn ($q) => $q->where('teacher_id', $user->id))
                ->with([
                    'subjects' => fn ($q) => $q->where('teacher_id', $user->id),
                    'students',
                ])
                ->withCount(['students', 'subjects'])
                ->orderBy('name')
                ->get();
        } else {
            // El administrador ve todos los cursos y materias de la institución
            $courses = Course::where('institution_id', $activeInstId)
                ->with(['subjects', 'students'])
                ->withCount(['students', 'subjects'])
                ->orderBy('name')
                ->get();
        }

        // Lista de docentes para asignar a materias
        $teachers = User::whereIn('role', ['teacher', 'coordinator', 'admin'])
            ->orWhereNull('role')
            ->orderBy('name')
            ->get();

        return view('courses.index', compact('courses', 'institution', 'teachers', 'activeInstId'));
    }

    /**
     * Registra un nuevo curso en la institución activa.
     */
    public function store(Request $request)
    {
        $activeInstId = session('active_institution_id', Institution::first()?->id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'section' => 'nullable|string|max:50',
            'academic_year' => 'required|string|max:10',
        ]);

        $validated['institution_id'] = $activeInstId;
        $course = Course::create($validated);

        return redirect()->route('courses.index')->with('success', "Curso {$course->full_name} creado exitosamente.");
    }

    /**
     * Actualiza la información de un curso.
     */
    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'section' => 'nullable|string|max:50',
            'academic_year' => 'required|string|max:10',
        ]);

        $course->update($validated);

        return redirect()->route('courses.index')->with('success', "Curso {$course->full_name} actualizado correctamente.");
    }

    /**
     * Elimina un curso y sus materias/alumnos asociados.
     */
    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $name = $course->full_name;
        $course->delete();

        return redirect()->route('courses.index')->with('success', "Curso {$name} eliminado del sistema.");
    }

    /**
     * Registra una nueva asignatura/materia dentro de un curso.
     */
    public function storeSubject(Request $request, $courseId)
    {
        $activeInstId = session('active_institution_id', Institution::first()?->id);
        $course = Course::findOrFail($courseId);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50',
            'teacher_name' => 'nullable|string|max:150',
            'teacher_id' => 'nullable|exists:users,id',
            'new_teacher_name' => 'nullable|string|max:255',
            'new_teacher_email' => 'nullable|email|max:255',
            'new_teacher_title' => 'nullable|string|max:255',
        ], [
            'name.required' => 'El nombre de la asignatura es obligatorio.',
            'new_teacher_email.email' => 'Ingresa un correo electrónico válido para el nuevo docente.',
        ]);

        $institutionId = $course->institution_id ?: $activeInstId;
        $validated['institution_id'] = $institutionId;
        $validated['course_id'] = $course->id;

        // Si se envió un nuevo docente para crear directamente en el formulario
        if (! empty($validated['new_teacher_name']) && ! empty($validated['new_teacher_email'])) {
            $request->validate([
                'new_teacher_email' => 'unique:users,email',
            ], [
                'new_teacher_email.unique' => 'El correo del nuevo docente ya se encuentra registrado.',
            ]);

            $newTeacher = User::create([
                'name' => $validated['new_teacher_name'],
                'email' => $validated['new_teacher_email'],
                'title' => ($validated['new_teacher_title'] ?? null) ?: 'Docente Titular',
                'role' => 'teacher',
                'password' => Hash::make('password123'),
            ]);

            if ($institutionId) {
                $newTeacher->institutions()->syncWithoutDetaching([
                    $institutionId => ['role' => 'teacher'],
                ]);
            }

            $validated['teacher_id'] = $newTeacher->id;
            $validated['teacher_name'] = $newTeacher->name;
        } elseif (! empty($validated['teacher_id'])) {
            $assignedTeacher = User::find($validated['teacher_id']);
            if ($assignedTeacher) {
                $validated['teacher_name'] = $assignedTeacher->name;
                if ($institutionId) {
                    $assignedTeacher->institutions()->syncWithoutDetaching([
                        $institutionId => ['role' => 'teacher'],
                    ]);
                }
            }
        } else {
            $validated['teacher_id'] = null;
            $validated['teacher_name'] = ! empty($validated['teacher_name']) ? $validated['teacher_name'] : 'Docente Titular';
        }

        $subject = Subject::create([
            'institution_id' => $validated['institution_id'],
            'course_id' => $validated['course_id'],
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'teacher_id' => $validated['teacher_id'],
            'teacher_name' => $validated['teacher_name'],
        ]);

        return redirect()->route('courses.index')->with('success', "Asignatura {$subject->name} agregada al curso {$course->name}.");
    }

    /**
     * Actualiza la información de una asignatura/materia y su docente asignado.
     */
    public function updateSubject(Request $request, $id)
    {
        $activeInstId = session('active_institution_id', Institution::first()?->id);
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50',
            'teacher_id' => 'nullable|exists:users,id',
            'teacher_name' => 'nullable|string|max:150',
            'new_teacher_name' => 'nullable|string|max:255',
            'new_teacher_email' => 'nullable|email|max:255',
            'new_teacher_title' => 'nullable|string|max:255',
        ], [
            'name.required' => 'El nombre de la asignatura es obligatorio.',
            'new_teacher_email.email' => 'Ingresa un correo electrónico válido para el nuevo docente.',
        ]);

        $institutionId = $subject->institution_id ?: $activeInstId;

        // Si se envió un nuevo docente para crear directamente en el formulario
        if (! empty($validated['new_teacher_name']) && ! empty($validated['new_teacher_email'])) {
            $request->validate([
                'new_teacher_email' => 'unique:users,email',
            ], [
                'new_teacher_email.unique' => 'El correo del nuevo docente ya se encuentra registrado en el sistema.',
            ]);

            $newTeacher = User::create([
                'name' => $validated['new_teacher_name'],
                'email' => $validated['new_teacher_email'],
                'title' => ($validated['new_teacher_title'] ?? null) ?: 'Docente Titular',
                'role' => 'teacher',
                'password' => Hash::make('password123'),
            ]);

            if ($institutionId) {
                $newTeacher->institutions()->syncWithoutDetaching([
                    $institutionId => ['role' => 'teacher'],
                ]);
            }

            $validated['teacher_id'] = $newTeacher->id;
            $validated['teacher_name'] = $newTeacher->name;
        } elseif (! empty($validated['teacher_id'])) {
            $assignedTeacher = User::find($validated['teacher_id']);
            if ($assignedTeacher) {
                $validated['teacher_name'] = $assignedTeacher->name;
                if ($institutionId) {
                    $assignedTeacher->institutions()->syncWithoutDetaching([
                        $institutionId => ['role' => 'teacher'],
                    ]);
                }
            }
        } else {
            $validated['teacher_id'] = null;
            $validated['teacher_name'] = ! empty($validated['teacher_name']) ? $validated['teacher_name'] : 'Docente Titular';
        }

        $subject->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'teacher_id' => $validated['teacher_id'],
            'teacher_name' => $validated['teacher_name'],
        ]);

        return redirect()->route('courses.index')->with('success', "Asignatura {$subject->name} actualizada exitosamente.");
    }

    /**
     * Elimina una asignatura/materia.
     */
    public function destroySubject($id)
    {
        $subject = Subject::findOrFail($id);
        $name = $subject->name;
        $subject->delete();

        return redirect()->route('courses.index')->with('success', "Asignatura {$name} eliminada.");
    }
}
