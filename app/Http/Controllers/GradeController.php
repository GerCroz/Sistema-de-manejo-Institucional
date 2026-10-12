<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\Subject;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $activeInstId = session('active_institution_id', Institution::first()?->id);

        if ($user && ! $user->isAdmin()) {
            // Un docente solo ve y administra las materias que le fueron asignadas
            $subjects = Subject::where('institution_id', $activeInstId)
                ->where('teacher_id', $user->id)
                ->with('course')
                ->get();
        } else {
            $subjects = Subject::where('institution_id', $activeInstId)->with('course')->get();
        }

        $selectedSubjectId = $request->input('subject_id', $subjects->first()?->id);

        if ($subjects->isNotEmpty() && ! $subjects->pluck('id')->contains($selectedSubjectId)) {
            $selectedSubjectId = $subjects->first()->id;
        }

        $selectedSubject = $selectedSubjectId
            ? Subject::with(['evaluations.grades', 'course.students'])->find($selectedSubjectId)
            : null;

        return view('grades.index', compact('subjects', 'selectedSubjectId', 'selectedSubject'));
    }

    public function storeEvaluation(Request $request)
    {
        $user = $request->user();
        $activeInstId = session('active_institution_id', Institution::first()?->id);

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:200',
            'weight_percentage' => 'required|integer|min:1|max:100',
            'date' => 'required|date',
        ]);

        if ($user && ! $user->isAdmin()) {
            $subject = Subject::findOrFail($validated['subject_id']);
            if ($subject->teacher_id !== $user->id) {
                abort(403, 'No estás asignado a esta asignatura para crear evaluaciones.');
            }
        }

        $validated['institution_id'] = $activeInstId;
        Evaluation::create($validated);

        return redirect()->back()->with('success', 'Evaluación agregada con éxito');
    }

    public function storeGrades(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'evaluation_id' => 'required|exists:evaluations,id',
            'scores' => 'required|array',
        ]);

        $evaluationId = $request->evaluation_id;
        $evaluation = Evaluation::with('subject')->findOrFail($evaluationId);

        if ($user && ! $user->isAdmin()) {
            if ($evaluation->subject->teacher_id !== $user->id) {
                abort(403, 'No tienes permisos para calificar esta evaluación.');
            }
        }

        foreach ($request->scores as $studentId => $score) {
            if ($score !== null && $score !== '') {
                Grade::updateOrCreate(
                    [
                        'evaluation_id' => $evaluationId,
                        'student_id' => $studentId,
                    ],
                    [
                        'score' => (float) $score,
                        'feedback' => $request->feedback[$studentId] ?? null,
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Calificaciones registradas correctamente');
    }

    /**
     * Elimina una evaluación y sus notas asociadas.
     */
    public function destroyEvaluation(Request $request, $id)
    {
        $evaluation = Evaluation::with('subject')->findOrFail($id);
        $user = $request->user();

        if ($user && ! $user->isAdmin()) {
            if ($evaluation->subject->teacher_id !== $user->id) {
                abort(403, 'No tienes permisos para eliminar esta evaluación.');
            }
        }

        $title = $evaluation->title;
        $evaluation->grades()->delete();
        $evaluation->delete();

        return redirect()->back()->with('success', "Evaluación \"{$title}\" y sus calificaciones asociadas han sido eliminadas.");
    }
}
