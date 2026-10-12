<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $activeInstId = session('active_institution_id', Institution::first()?->id);

        if ($user && ! $user->isAdmin()) {
            // El docente solo tiene acceso a registrar asistencia en los cursos de sus asignaturas
            $courses = Course::where('institution_id', $activeInstId)
                ->whereHas('subjects', fn ($q) => $q->where('teacher_id', $user->id))
                ->get();
        } else {
            $courses = Course::where('institution_id', $activeInstId)->get();
        }

        $selectedCourseId = $request->input('course_id', $courses->first()?->id);
        $date = $request->input('date', date('Y-m-d'));

        // Asegurar que si el curso no pertenece al docente, use el primero disponible
        if ($courses->isNotEmpty() && ! $courses->pluck('id')->contains($selectedCourseId)) {
            $selectedCourseId = $courses->first()->id;
        }

        $selectedCourse = Course::find($selectedCourseId);
        $students = $selectedCourseId
            ? Student::where('course_id', $selectedCourseId)->get()
            : collect();

        // Cargar registros existentes para ese día y curso
        $existingAttendances = $selectedCourseId
            ? Attendance::where('course_id', $selectedCourseId)
                ->where('date', $date)
                ->get()
                ->keyBy('student_id')
            : collect();

        return view('attendance.index', compact('courses', 'selectedCourseId', 'selectedCourse', 'date', 'students', 'existingAttendances'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $activeInstId = session('active_institution_id', Institution::first()?->id);

        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'date' => 'required|date',
            'attendances' => 'required|array',
        ]);

        $courseId = $request->course_id;
        $date = $request->date;

        if ($user && ! $user->isAdmin()) {
            $allowedCourseIds = Course::where('institution_id', $activeInstId)
                ->whereHas('subjects', fn ($q) => $q->where('teacher_id', $user->id))
                ->pluck('id');

            if (! $allowedCourseIds->contains($courseId)) {
                abort(403, 'No estás asignado como docente para tomar asistencia en este curso.');
            }
        }

        foreach ($request->attendances as $studentId => $data) {
            Attendance::updateOrCreate(
                [
                    'institution_id' => $activeInstId,
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'date' => $date,
                ],
                [
                    'status' => $data['status'] ?? 'present',
                    'notes' => $data['notes'] ?? null,
                ]
            );
        }

        return redirect()->back()->with('success', "Asistencia guardada correctamente para el {$date}");
    }

    /**
     * Elimina el registro de asistencias de un curso en una fecha determinada.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'date' => 'required|date',
        ]);

        $user = $request->user();
        $activeInstId = session('active_institution_id', Institution::first()?->id);

        if ($user && ! $user->isAdmin()) {
            $allowedCourseIds = Course::where('institution_id', $activeInstId)
                ->whereHas('subjects', fn ($q) => $q->where('teacher_id', $user->id))
                ->pluck('id');

            if (! $allowedCourseIds->contains($request->course_id)) {
                abort(403, 'No estás autorizado para eliminar asistencias en este curso.');
            }
        }

        $count = Attendance::where('institution_id', $activeInstId)
            ->where('course_id', $request->course_id)
            ->where('date', $request->date)
            ->delete();

        return redirect()->back()->with('success', "Se eliminaron {$count} registros de asistencia para el día {$request->date}.");
    }
}
