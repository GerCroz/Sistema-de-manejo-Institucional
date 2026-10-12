<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistema de Gestión Escolar Multi-Institución (Laravel 13)
|--------------------------------------------------------------------------
*/

// --- Autenticación y 2FA ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:10,1');

    // Desafío 2FA
    Route::get('/2fa/verify', [TwoFactorController::class, 'showChallenge'])->name('2fa.challenge');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verifyChallenge'])->name('2fa.verify')->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    // Cerrar sesión
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Seguridad y configuración 2FA del usuario
    Route::prefix('security')->name('security.')->group(function () {
        Route::get('/', [TwoFactorController::class, 'showSecurity'])->name('index');
        Route::post('/2fa/confirm', [TwoFactorController::class, 'confirm'])->name('2fa.confirm');
        Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
        Route::post('/2fa/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('2fa.recovery_codes');
    });

    // Dashboard principal (filtrado por rol)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Cambio de institución activa (context switcher)
    Route::post('/institutions/switch/{id}', [InstitutionController::class, 'switch'])->name('institutions.switch');

    // --- Módulos Exclusivos para Administradores (role:admin) ---
    Route::middleware('role:admin')->group(function () {
        // Gestión de Instituciones (CRUD)
        Route::prefix('institutions')->name('institutions.')->group(function () {
            Route::get('/', [InstitutionController::class, 'index'])->name('index');
            Route::post('/', [InstitutionController::class, 'store'])->name('store');
            Route::put('/{id}', [InstitutionController::class, 'update'])->name('update');
            Route::delete('/{id}', [InstitutionController::class, 'destroy'])->name('destroy');
        });

        // Directorio y Administración de Docentes
        Route::resource('teachers', TeacherController::class)->except(['create', 'show', 'edit']);

        // Creación y eliminación estructural de cursos y materias
        Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
        Route::put('courses/{id}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('courses/{id}', [CourseController::class, 'destroy'])->name('courses.destroy');
        Route::post('courses/{course}/subjects', [CourseController::class, 'storeSubject'])->name('courses.subjects.store');
        Route::delete('subjects/{id}', [CourseController::class, 'destroySubject'])->name('subjects.destroy');
    });

    // --- Módulos Compartidos (con alcance filtrado según rol de Admin o Docente) ---

    // 1. Cursos y Materias (vista)
    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');

    // 2. Directorio y Ficha de Alumnos
    Route::resource('students', StudentController::class);

    // 3. Control de Asistencias por Fecha y Curso
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
        Route::delete('/', [AttendanceController::class, 'destroy'])->name('destroy');
    });

    // 4. Matriz de Calificaciones y Evaluaciones
    Route::prefix('grades')->name('grades.')->group(function () {
        Route::get('/', [GradeController::class, 'index'])->name('index');
        Route::post('/evaluations', [GradeController::class, 'storeEvaluation'])->name('storeEvaluation');
        Route::delete('/evaluations/{id}', [GradeController::class, 'destroyEvaluation'])->name('destroyEvaluation');
        Route::post('/scores', [GradeController::class, 'storeGrades'])->name('storeGrades');
    });

    // 5. Importación de Planillas Académicas
    Route::prefix('import')->name('import.')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::post('/', [ImportController::class, 'process'])->name('process');
    });
});
