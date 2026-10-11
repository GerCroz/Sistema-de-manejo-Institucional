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
        $activeInstId = session('active_institution_id', Institution::first()?->id);
        $courses = Course::where('institution_id', $activeInstId)->get();
        $selectedCourseId = $request->input('course_id', $courses->first()?->id);
        $date = $request->input('date', date('Y-m-d'));

        $selectedCourse = Course::find($selectedCourseId);
        $students = $selectedCourseId
            ? Student::where('course_id', $selectedCourseId)->get()
            : collect();

        // Cargar registros existentes para ese día y curso
        $existingAttendances = Attendance::where('course_id', $selectedCourseId)
            ->where('date', $date)
            ->get()
            ->keyBy('student_id');

        return view('attendance.index', compact('courses', 'selectedCourseId', 'selectedCourse', 'date', 'students', 'existingAttendances'));
    }

    public function store(Request $request)
    {
        $activeInstId = session('active_institution_id', Institution::first()?->id);

        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'date' => 'required|date',
            'attendances' => 'required|array',
        ]);

        $courseId = $request->course_id;
        $date = $request->date;

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
}
