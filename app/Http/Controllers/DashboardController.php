<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Si es docente, solo tiene acceso a sus instituciones vinculadas
        $institutions = ($user && ! $user->isAdmin())
            ? $user->institutions
            : Institution::all();

        $activeInstitutionId = session('active_institution_id', $institutions->first()?->id);
        $activeInstitution = $institutions->firstWhere('id', $activeInstitutionId) ?? $institutions->first();

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
                'isTeacher' => $user?->isTeacher() ?? false,
            ]);
        }

        $isTeacher = $user && ! $user->isAdmin();

        if ($isTeacher) {
            // Alcance exclusivo del docente: solo sus asignaturas y cursos asignados
            $teacherSubjectIds = Subject::where('institution_id', $activeInstitution->id)
                ->where('teacher_id', $user->id)
                ->pluck('id');

            $teacherCourseIds = Course::where('institution_id', $activeInstitution->id)
                ->whereHas('subjects', fn ($q) => $q->where('teacher_id', $user->id))
                ->pluck('id');

            $totalStudents = Student::where('institution_id', $activeInstitution->id)
                ->whereIn('course_id', $teacherCourseIds)
                ->count();

            $totalCourses = $teacherCourseIds->count();
            $totalSubjects = $teacherSubjectIds->count();

            $allAttendances = Attendance::where('institution_id', $activeInstitution->id)
                ->whereIn('course_id', $teacherCourseIds)
                ->get();

            $presentCount = $allAttendances->whereIn('status', ['present', 'justified', 'late'])->count();
            $avgAttendance = $allAttendances->count() > 0
                ? round(($presentCount / $allAttendances->count()) * 100, 1)
                : 95.0;

            $allGrades = Grade::whereHas('evaluation', function ($q) use ($teacherSubjectIds) {
                $q->whereIn('subject_id', $teacherSubjectIds);
            })->get();

            $avgGrade = $allGrades->count() > 0 ? round($allGrades->avg('score'), 1) : 6.0;

            $students = Student::where('institution_id', $activeInstitution->id)
                ->whereIn('course_id', $teacherCourseIds)
                ->with(['course', 'attendances', 'grades'])
                ->get();

            $riskStudents = $students->filter(function ($s) {
                return $s->attendance_percentage < 85 || ($s->average_grade > 0 && $s->average_grade < 4.0);
            });

            $recentAttendances = Attendance::where('institution_id', $activeInstitution->id)
                ->whereIn('course_id', $teacherCourseIds)
                ->with(['student', 'course'])
                ->orderBy('date', 'desc')
                ->take(6)
                ->get();
        } else {
            // Alcance global para administradores
            $totalStudents = Student::where('institution_id', $activeInstitution->id)->count();
            $totalCourses = Course::where('institution_id', $activeInstitution->id)->count();
            $totalSubjects = Subject::where('institution_id', $activeInstitution->id)->count();

            $allAttendances = Attendance::where('institution_id', $activeInstitution->id)->get();
            $presentCount = $allAttendances->whereIn('status', ['present', 'justified', 'late'])->count();
            $avgAttendance = $allAttendances->count() > 0
                ? round(($presentCount / $allAttendances->count()) * 100, 1)
                : 95.0;

            $allGrades = Grade::whereHas('evaluation', function ($q) use ($activeInstitution) {
                $q->where('institution_id', $activeInstitution->id);
            })->get();

            $avgGrade = $allGrades->count() > 0 ? round($allGrades->avg('score'), 1) : 6.2;

            $students = Student::where('institution_id', $activeInstitution->id)
                ->with(['course', 'attendances', 'grades'])
                ->get();

            $riskStudents = $students->filter(function ($s) {
                return $s->attendance_percentage < 85 || ($s->average_grade > 0 && $s->average_grade < 4.0);
            });

            $recentAttendances = Attendance::where('institution_id', $activeInstitution->id)
                ->with(['student', 'course'])
                ->orderBy('date', 'desc')
                ->take(6)
                ->get();
        }

        return view('dashboard', compact(
            'activeInstitution',
            'institutions',
            'totalStudents',
            'totalCourses',
            'totalSubjects',
            'avgAttendance',
            'avgGrade',
            'riskStudents',
            'recentAttendances',
            'isTeacher'
        ));
    }
}
