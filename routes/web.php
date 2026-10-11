<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\ImportController;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistema de Gestión Escolar Multi-Institución (Laravel 13)
|--------------------------------------------------------------------------
*/

// Redirección inicial a Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Gestión Multi-Institución y Cambio de Contexto Activo
Route::prefix('institutions')->name('institutions.')->group(function () {
    Route::get('/', [InstitutionController::class, 'index'])->name('index');
    Route::post('/switch/{id}', [InstitutionController::class, 'switch'])->name('switch');
    Route::post('/', [InstitutionController::class, 'store'])->name('store');
});

// Directorio y Ficha de Alumnos
Route::resource('students', StudentController::class);

// Control de Asistencias por Fecha y Curso
Route::prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('index');
    Route::post('/', [AttendanceController::class, 'store'])->name('store');
});

// Matriz de Calificaciones y Evaluaciones
Route::prefix('grades')->name('grades.')->group(function () {
    Route::get('/', [GradeController::class, 'index'])->name('index');
    Route::post('/evaluations', [GradeController::class, 'storeEvaluation'])->name('storeEvaluation');
    Route::post('/scores', [GradeController::class, 'storeGrades'])->name('storeGrades');
});

// Importación de Planillas Académicas (Asistencias y Notas)
Route::prefix('import')->name('import.')->group(function () {
    Route::get('/', [ImportController::class, 'index'])->name('index');
    Route::post('/', [ImportController::class, 'process'])->name('process');
});

