<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Institution;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

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
        $teachers = User::where('role', 'teacher')->orWhereNull('role')->orderBy('name')->get();

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
        ]);

        $validated['institution_id'] = $activeInstId;
        $validated['course_id'] = $course->id;

        if (! empty($validated['teacher_id'])) {
            $assignedTeacher = User::find($validated['teacher_id']);
            if ($assignedTeacher) {
                $validated['teacher_name'] = $assignedTeacher->name;
            }
        } else {
            $validated['teacher_name'] = $validated['teacher_name'] ?: 'Docente Titular';
        }

        $subject = Subject::create($validated);

        return redirect()->route('courses.index')->with('success', "Asignatura {$subject->name} agregada al curso {$course->name}.");
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
