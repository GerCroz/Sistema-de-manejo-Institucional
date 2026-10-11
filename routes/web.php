<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistema de Gestión Escolar Multi-Institución (Laravel 13)
|--------------------------------------------------------------------------
*/

// Redirección inicial a Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// 1. Gestión Multi-Institución (Tenants)
Route::prefix('institutions')->name('institutions.')->group(function () {
    Route::get('/', [InstitutionController::class, 'index'])->name('index');
    Route::post('/switch/{id}', [InstitutionController::class, 'switch'])->name('switch');
    Route::post('/', [InstitutionController::class, 'store'])->name('store');
    Route::put('/{id}', [InstitutionController::class, 'update'])->name('update');
    Route::delete('/{id}', [InstitutionController::class, 'destroy'])->name('destroy');
});

// 2. Gestión de Maestros / Docentes (Usuarios del sistema)
Route::resource('teachers', TeacherController::class)->except(['create', 'show', 'edit']);

// 3. Gestión Académica: Cursos y Materias
Route::resource('courses', CourseController::class)->except(['create', 'show', 'edit']);
Route::post('courses/{course}/subjects', [CourseController::class, 'storeSubject'])->name('courses.subjects.store');
Route::delete('subjects/{id}', [CourseController::class, 'destroySubject'])->name('subjects.destroy');

// 4. Directorio y Ficha de Alumnos
Route::resource('students', StudentController::class);

// 5. Control de Asistencias por Fecha y Curso
Route::prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('index');
    Route::post('/', [AttendanceController::class, 'store'])->name('store');
});

// 6. Matriz de Calificaciones y Evaluaciones
Route::prefix('grades')->name('grades.')->group(function () {
    Route::get('/', [GradeController::class, 'index'])->name('index');
    Route::post('/evaluations', [GradeController::class, 'storeEvaluation'])->name('storeEvaluation');
    Route::post('/scores', [GradeController::class, 'storeGrades'])->name('storeGrades');
});

// 7. Importación de Planillas Académicas (Asistencias y Notas)
Route::prefix('import')->name('import.')->group(function () {
    Route::get('/', [ImportController::class, 'index'])->name('index');
    Route::post('/', [ImportController::class, 'process'])->name('process');
});
