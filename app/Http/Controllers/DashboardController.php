<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Subject;

class DashboardController extends Controller
{
    public function index()
    {
        $institutions = Institution::all();
        $activeInstitutionId = session('active_institution_id', $institutions->first()?->id);
        $activeInstitution = Institution::find($activeInstitutionId) ?? $institutions->first();

        if (! $activeInstitution) {
            return view('dashboard', [
                'activeInstitution' => null,
                'institutions' => collect(),
                'totalStudents' => 0,
                'totalCourses' => 0,
                'totalSubjects' => 0,
                'avgAttendance' => 100,
                'avgGrade' => 0,
                'riskStudents' => collect(),
                'recentAttendances' => collect(),
            ]);
        }

        // Estadísticas filtradas por la Institución Activa
        $totalStudents = Student::where('institution_id', $activeInstitution->id)->count();
        $totalCourses = Course::where('institution_id', $activeInstitution->id)->count();
        $totalSubjects = Subject::where('institution_id', $activeInstitution->id)->count();

        // Asistencia Promedio
        $allAttendances = Attendance::where('institution_id', $activeInstitution->id)->get();
        $presentCount = $allAttendances->whereIn('status', ['present', 'justified', 'late'])->count();
        $avgAttendance = $allAttendances->count() > 0
            ? round(($presentCount / $allAttendances->count()) * 100, 1)
            : 95.0;

        // Promedio de Notas General
        $allGrades = Grade::whereHas('evaluation', function ($q) use ($activeInstitution) {
            $q->where('institution_id', $activeInstitution->id);
        })->get();

        $avgGrade = $allGrades->count() > 0 ? round($allGrades->avg('score'), 1) : 6.2;

        // Alumnos con baja asistencia o notas en riesgo (< 4.0 o asistencia < 85%)
        $students = Student::where('institution_id', $activeInstitution->id)->with(['course', 'attendances', 'grades'])->get();
        $riskStudents = $students->filter(function ($s) {
            return $s->attendance_percentage < 85 || ($s->average_grade > 0 && $s->average_grade < 4.0);
        });

        // Asistencias recientes
        $recentAttendances = Attendance::where('institution_id', $activeInstitution->id)
            ->with(['student', 'course'])
            ->orderBy('date', 'desc')
            ->take(6)
            ->get();

        return view('dashboard', compact(
            'activeInstitution',
            'institutions',
            'totalStudents',
            'totalCourses',
            'totalSubjects',
            'avgAttendance',
            'avgGrade',
            'riskStudents',
            'recentAttendances'
        ));
    }
}
