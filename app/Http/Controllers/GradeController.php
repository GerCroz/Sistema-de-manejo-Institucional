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
        $activeInstId = session('active_institution_id', Institution::first()?->id);
        $subjects = Subject::where('institution_id', $activeInstId)->with('course')->get();
        $selectedSubjectId = $request->input('subject_id', $subjects->first()?->id);

        $selectedSubject = Subject::with(['evaluations.grades', 'course.students'])->find($selectedSubjectId);

        return view('grades.index', compact('subjects', 'selectedSubjectId', 'selectedSubject'));
    }

    public function storeEvaluation(Request $request)
    {
        $activeInstId = session('active_institution_id', Institution::first()?->id);

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:200',
            'weight_percentage' => 'required|integer|min:1|max:100',
            'date' => 'required|date',
        ]);

        $validated['institution_id'] = $activeInstId;
        Evaluation::create($validated);

        return redirect()->back()->with('success', 'Evaluación agregada con éxito');
    }

    public function storeGrades(Request $request)
    {
        $request->validate([
            'evaluation_id' => 'required|exists:evaluations,id',
            'scores' => 'required|array',
        ]);

        $evaluationId = $request->evaluation_id;

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
}
