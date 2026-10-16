<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    /**
     * Muestra el directorio y administración de maestros/docentes.
     */
    public function index()
    {
        $activeInstId = session('active_institution_id', Institution::first()?->id);
        $institutions = Institution::all();

        // Cargar todos los docentes/usuarios con sus instituciones asociadas
        $teachers = User::with('institutions')->orderBy('name')->get();

        return view('teachers.index', compact('teachers', 'institutions', 'activeInstId'));
    }

    /**
     * Registra rápidamente un docente desde modales de asignaturas y lo vincula a la institución activa.
     */
    public function quickStore(Request $request)
    {
        $activeInstId = session('active_institution_id', Institution::first()?->id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'title' => 'nullable|string|max:255',
            'institution_id' => 'nullable|exists:institutions,id',
        ], [
            'name.required' => 'El nombre del docente es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Este correo ya se encuentra registrado en el sistema.',
        ]);

        $institutionId = $validated['institution_id'] ?? $activeInstId;

        $teacher = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'title' => ($validated['title'] ?? null) ?: 'Docente Titular',
            'role' => 'teacher',
            'password' => Hash::make('password123'),
        ]);

        if ($institutionId) {
            $teacher->institutions()->syncWithoutDetaching([
                $institutionId => ['role' => 'teacher'],
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'teacher' => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'title' => $teacher->title,
                    'email' => $teacher->email,
                ],
                'message' => "Docente {$teacher->name} creado exitosamente.",
            ]);
        }

        return back()->with('success', "Docente {$teacher->name} registrado exitosamente.");
    }

    /**
     * Registra un nuevo maestro/docente y lo vincula a instituciones.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'title' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
            'institution_ids' => 'required|array|min:1',
            'institution_ids.*' => 'exists:institutions,id',
            'role' => 'required|in:teacher,admin,coordinator',
        ]);

        $teacher = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'title' => ($validated['title'] ?? null) ?: 'Docente Titular',
            'role' => $validated['role'],
            'password' => Hash::make(($validated['password'] ?? null) ?: 'password123'),
        ]);

        // Asociar a las instituciones seleccionadas con el rol especificado
        $pivotData = [];
        foreach ($validated['institution_ids'] as $instId) {
            $pivotData[$instId] = ['role' => $validated['role']];
        }
        $teacher->institutions()->sync($pivotData);

        return redirect()->route('teachers.index')->with('success', "Docente {$teacher->name} registrado exitosamente.");
    }

    /**
     * Actualiza la información y asignaciones del docente.
     */
    public function update(Request $request, $id)
    {
        $teacher = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$teacher->id,
            'title' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
            'institution_ids' => 'required|array|min:1',
            'institution_ids.*' => 'exists:institutions,id',
            'role' => 'required|in:teacher,admin,coordinator',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'title' => ($validated['title'] ?? null) ?: $teacher->title,
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $teacher->update($data);

        // Sincronizar instituciones y roles
        $pivotData = [];
        foreach ($validated['institution_ids'] as $instId) {
            $pivotData[$instId] = ['role' => $validated['role']];
        }
        $teacher->institutions()->sync($pivotData);

        return redirect()->route('teachers.index')->with('success', "Información de {$teacher->name} actualizada correctamente.");
    }

    /**
     * Elimina el docente del sistema.
     */
    public function destroy(Request $request, $id)
    {
        $teacher = User::findOrFail($id);

        if ($teacher->id === $request->user()?->id) {
            return back()->withErrors(['error' => 'No puedes eliminar tu propia cuenta de administrador en sesión activa.']);
        }

        $name = $teacher->name;

        // Desvincular de materias asignadas para no dejar referencias rotas
        $teacher->subjects()->update([
            'teacher_id' => null,
            'teacher_name' => 'Docente Titular (Por asignar)',
        ]);

        $teacher->institutions()->detach();
        $teacher->delete();

        return redirect()->route('teachers.index')->with('success', "Docente {$name} eliminado del sistema correctamente.");
    }
}
