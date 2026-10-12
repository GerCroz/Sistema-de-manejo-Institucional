<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $activeInstId = session('active_institution_id', Institution::first()?->id);

        $query = Student::where('institution_id', $activeInstId)->with('course');

        if ($user && ! $user->isAdmin()) {
            // El docente solo ve alumnos inscritos en los cursos donde él enseña alguna materia
            $teacherCourseIds = Course::where('institution_id', $activeInstId)
                ->whereHas('subjects', fn ($q) => $q->where('teacher_id', $user->id))
                ->pluck('id');

            $query->whereIn('course_id', $teacherCourseIds);
            $courses = Course::where('institution_id', $activeInstId)->whereIn('id', $teacherCourseIds)->get();
        } else {
            $courses = Course::where('institution_id', $activeInstId)->get();
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('rut_dni', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate(15);

        return view('students.index', compact('students', 'courses', 'activeInstId'));
    }

    public function create()
    {
        $activeInstId = session('active_institution_id', Institution::first()?->id);
        $courses = Course::where('institution_id', $activeInstId)->get();

        return view('students.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $activeInstId = session('active_institution_id', Institution::first()?->id);

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'legajo' => 'nullable|string|max:50',
            'rut_dni' => 'required|string|max:50|unique:students,rut_dni',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:150',
            'birth_date' => 'nullable|date',
            'guardian_name' => 'nullable|string|max:150',
            'guardian_phone' => 'nullable|string|max:50',
            'status' => 'nullable|in:active,inactive,graduated',
        ]);

        $validated['institution_id'] = $activeInstId;
        $validated['status'] = $validated['status'] ?? 'active';
        Student::create($validated);

        return redirect()->route('students.index')->with('success', 'Alumno registrado exitosamente');
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $student = Student::with(['course', 'attendances', 'grades.evaluation.subject'])->findOrFail($id);

        if ($user && ! $user->isAdmin()) {
            $teacherCourseIds = Course::where('institution_id', $student->institution_id)
                ->whereHas('subjects', fn ($q) => $q->where('teacher_id', $user->id))
                ->pluck('id');

            if (! $teacherCourseIds->contains($student->course_id)) {
                abort(403, 'No tienes autorización para ver la ficha de este alumno.');
            }
        }

        // Agrupar calificaciones por materia
        $gradesBySubject = $student->grades->groupBy(function ($grade) {
            return $grade->evaluation->subject->name ?? 'Asignatura General';
        });

        return view('students.show', compact('student', 'gradesBySubject'));
    }

    public function edit($id)
    {
        $activeInstId = session('active_institution_id', Institution::first()?->id);
        $student = Student::findOrFail($id);
        $courses = Course::where('institution_id', $activeInstId)->get();

        return view('students.edit', compact('student', 'courses'));
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'legajo' => 'nullable|string|max:50',
            'rut_dni' => 'required|string|max:50|unique:students,rut_dni,'.$student->id,
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:150',
            'birth_date' => 'nullable|date',
            'guardian_name' => 'nullable|string|max:150',
            'guardian_phone' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive,graduated',
        ]);

        $student->update($validated);

        return redirect()->route('students.index')->with('success', "Alumno {$student->full_name} actualizado exitosamente.");
    }

    public function destroy(Request $request, $id)
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Solo los administradores del sistema tienen permisos para eliminar alumnos.');
        }

        $student = Student::findOrFail($id);
        $name = $student->full_name;

        // Limpiar asistencias y calificaciones asociadas
        $student->attendances()->delete();
        $student->grades()->delete();
        $student->delete();

        return redirect()->route('students.index')->with('success', "Alumno {$name} eliminado del sistema correctamente.");
    }
}
