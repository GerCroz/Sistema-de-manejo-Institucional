<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\Request;

class InstitutionController extends Controller
{
    public function index()
    {
        $institutions = Institution::withCount(['courses', 'students', 'subjects'])->get();
        $currentInstitutionId = session('active_institution_id', $institutions->first()?->id);

        return view('institutions.index', compact('institutions', 'currentInstitutionId'));
    }

    public function switch(Request $request, $id)
    {
        $institution = Institution::findOrFail($id);
        session(['active_institution_id' => $institution->id]);

        return redirect()->back()->with('success', "Has cambiado a la institución: {$institution->name}");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:institutions,code',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'logo_color' => 'nullable|string|max:15',
        ]);

        $validated['logo_color'] = $validated['logo_color'] ?: '#6366f1';
        $institution = Institution::create($validated);
        session(['active_institution_id' => $institution->id]);

        return redirect()->route('institutions.index')->with('success', "Institución {$institution->name} creada exitosamente");
    }

    public function update(Request $request, $id)
    {
        $institution = Institution::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:institutions,code,' . $institution->id,
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'logo_color' => 'nullable|string|max:15',
        ]);

        $institution->update($validated);

        return redirect()->route('institutions.index')->with('success', "Institución {$institution->name} actualizada exitosamente");
    }

    public function destroy($id)
    {
        $institution = Institution::findOrFail($id);

        if (Institution::count() <= 1) {
            return back()->withErrors(['error' => 'No puedes eliminar la única institución del sistema.']);
        }

        $name = $institution->name;
        $institution->delete();

        // Si era la activa, cambiar a la primera existente
        $first = Institution::first();
        session(['active_institution_id' => $first?->id]);

        return redirect()->route('institutions.index')->with('success', "Institución {$name} eliminada correctamente");
    }
}
