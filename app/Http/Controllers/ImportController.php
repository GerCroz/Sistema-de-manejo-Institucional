<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Institution;
use App\Models\Subject;
use App\Services\AttendanceSheetImportService;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    /**
     * Muestra la interfaz de importación de planillas.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isTeacher = $user && ! $user->isAdmin();

        $institutions = $isTeacher ? $user->institutions : Institution::all();
        $activeInstId = session('active_institution_id', $institutions->first()?->id);

        if ($isTeacher) {
            $courses = Course::where('institution_id', $activeInstId)
                ->whereHas('subjects', fn ($q) => $q->where('teacher_id', $user->id))
                ->with(['subjects' => fn ($q) => $q->where('teacher_id', $user->id)])
                ->orderBy('name')
                ->get();
            $subjects = Subject::where('institution_id', $activeInstId)
                ->where('teacher_id', $user->id)
                ->with('course')
                ->orderBy('name')
                ->get();
        } else {
            $courses = Course::where('institution_id', $activeInstId)->with('subjects')->orderBy('name')->get();
            $subjects = Subject::where('institution_id', $activeInstId)->with('course')->orderBy('name')->get();
        }

        return view('import.index', compact('institutions', 'courses', 'subjects', 'activeInstId'));
    }

    /**
     * Procesa la carga de archivo o texto pegado.
     */
    public function process(Request $request, AttendanceSheetImportService $service)
    {
        $user = $request->user();
        $isTeacher = $user && ! $user->isAdmin();

        $request->validate([
            'file' => 'nullable|file|extensions:csv,txt,tsv|max:10240',
            'raw_content' => 'nullable|string',
            'institution_id' => 'nullable|exists:institutions,id',
            'course_id' => 'nullable|exists:courses,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'new_course_name' => 'nullable|string|max:150',
            'new_subject_name' => 'nullable|string|max:150',
            'new_subject_code' => 'nullable|string|max:50',
        ], [
            'file.extensions' => 'El archivo debe ser un archivo con extensión .csv o .txt válido.',
            'file.max' => 'El tamaño del archivo no puede superar los 10 MB.',
        ]);

        $content = '';

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $content = file_get_contents($request->file('file')->getRealPath());
        } elseif ($request->filled('raw_content')) {
            $content = $request->input('raw_content');
        } else {
            return back()->withErrors(['content' => 'Debes adjuntar un archivo CSV/TXT o pegar el contenido de la planilla.']);
        }

        try {
            $institutionId = $request->institution_id ?: session('active_institution_id', Institution::first()?->id);
            $courseId = $request->filled('course_id') ? (int) $request->course_id : null;
            $subjectId = $request->filled('subject_id') ? (int) $request->subject_id : null;

            // Creación dinámica de carrera / curso si el usuario especificó uno nuevo
            if (! $courseId && $request->filled('new_course_name')) {
                $newCourse = Course::firstOrCreate(
                    [
                        'institution_id' => $institutionId,
                        'name' => trim($request->new_course_name),
                    ],
                    [
                        'section' => 'Única',
                        'academic_year' => '2026',
                    ]
                );
                $courseId = $newCourse->id;
            }

            // Creación dinámica de materia / asignatura si el usuario especificó una nueva
            if (! $subjectId && $request->filled('new_subject_name')) {
                $newSubject = Subject::firstOrCreate(
                    [
                        'institution_id' => $institutionId,
                        'course_id' => $courseId ?? Course::where('institution_id', $institutionId)->first()?->id ?? 1,
                        'name' => trim($request->new_subject_name),
                    ],
                    [
                        'code' => $request->filled('new_subject_code') ? trim($request->new_subject_code) : null,
                        'teacher_name' => $isTeacher ? $user->name : ('Cátedra '.trim($request->new_subject_name)),
                        'teacher_id' => $isTeacher ? $user->id : null,
                    ]
                );
                $subjectId = $newSubject->id;
            }

            // Validación de permisos docentes para importar en cursos/materias
            if ($isTeacher && $subjectId) {
                $subject = Subject::find($subjectId);
                if ($subject && $subject->teacher_id !== $user->id) {
                    return back()->withErrors(['error' => 'No tienes permisos para importar registros en una asignatura que no te pertenece.']);
                }
            }

            $result = $service->import($content, $institutionId, $courseId, $subjectId);

            // Si se importó y creó la asignatura con un docente, vincular al docente activo si es docente
            if ($isTeacher && $result['subject'] && ! $result['subject']->teacher_id) {
                $result['subject']->update(['teacher_id' => $user->id, 'teacher_name' => $user->name]);
            }

            return redirect()->route('import.index')->with([
                'success' => 'Planilla importada exitosamente.',
                'import_result' => [
                    'institution_name' => $result['institution']->name,
                    'course_name' => $result['course']->name,
                    'subject_name' => "{$result['subject']->name} ({$result['subject']->code})",
                    'evaluation_title' => $result['evaluation']->title,
                    'students_total' => $result['students_total'],
                    'students_created' => $result['students_created'],
                    'students_updated' => $result['students_updated'],
                    'attendances_count' => $result['attendances_count'],
                    'grades_count' => $result['grades_count'],
                    'dates_imported' => $result['dates_imported'],
                    'course_id' => $result['course']->id,
                    'subject_id' => $result['subject']->id,
                ],
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Error al procesar la planilla: '.$e->getMessage()])->withInput();
        }
    }
}
