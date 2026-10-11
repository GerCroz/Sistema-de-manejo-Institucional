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
        $activeInstId = session('active_institution_id', Institution::first()?->id);

        $query = Student::where('institution_id', $activeInstId)->with('course');

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
        $courses = Course::where('institution_id', $activeInstId)->get();

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
            'rut_dni' => 'required|string|max:20|unique:students,rut_dni',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:150',
            'birth_date' => 'nullable|date',
            'guardian_name' => 'nullable|string|max:150',
            'guardian_phone' => 'nullable|string|max:50',
        ]);

        $validated['institution_id'] = $activeInstId;
        Student::create($validated);

        return redirect()->route('students.index')->with('success', 'Alumno registrado exitosamente');
    }

    public function show($id)
    {
        $student = Student::with(['course', 'attendances', 'grades.evaluation.subject'])->findOrFail($id);

        // Agrupar calificaciones por materia
        $gradesBySubject = $student->grades->groupBy(function ($grade) {
            return $grade->evaluation->subject->name ?? 'Asignatura General';
        });

        return view('students.show', compact('student', 'gradesBySubject'));
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return redirect()->route('students.index')->with('success', 'Alumno eliminado del sistema');
    }
}
