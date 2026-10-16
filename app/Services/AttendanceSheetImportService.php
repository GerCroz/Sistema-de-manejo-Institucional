<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class AttendanceSheetImportService
{
    /**
     * Procesa e importa el archivo/texto de la planilla.
     *
     * @param  string  $content  Contenido crudo en formato CSV/texto delimitado por punto y coma.
     * @param  int|null  $institutionId  ID de la institución (opcional, usa la primera o la indicada)
     * @param  int|null  $courseId  ID del curso (opcional)
     * @param  int|null  $subjectId  ID de la materia (opcional)
     * @return array Resumen del proceso de importación
     */
    public function import(string $content, ?int $institutionId = null, ?int $courseId = null, ?int $subjectId = null): array
    {
        // Limpieza y decodificación de codificación
        $content = $this->normalizeEncoding($content);

        // Dividir por líneas
        $lines = preg_split("/\r\n|\n|\r/", trim($content));
        if (empty($lines)) {
            throw new \InvalidArgumentException('El archivo está vacío o no contiene líneas legibles.');
        }

        // Metadatos extraídos de la cabecera
        $metadata = $this->parseMetadata($lines);

        return DB::transaction(function () use ($lines, $metadata, $institutionId, $courseId, $subjectId) {
            // 1. Resolver Institución
            $institution = $institutionId
                ? Institution::findOrFail($institutionId)
                : Institution::first();

            if (! $institution) {
                $institution = Institution::create([
                    'name' => 'Universidad / Rectorado',
                    'code' => 'RECT-01',
                    'logo_color' => '#6366f1',
                    'is_active' => true,
                ]);
            }

            // 2. Buscar la línea de encabezado de columnas (Legajo, Alumno, Nro, P1, PP...)
            $headerRowIndex = null;
            $columns = [];

            foreach ($lines as $idx => $line) {
                $row = str_getcsv($line, ';');
                $cleanRow = array_map('trim', $row);

                $hasLegajoOrNro = false;
                $hasStudentName = false;

                foreach ($cleanRow as $cell) {
                    if (stripos($cell, 'Legajo') !== false || stripos($cell, 'Nro') !== false || stripos($cell, 'N°') !== false || stripos($cell, 'Nº') !== false) {
                        $hasLegajoOrNro = true;
                    }
                    if (stripos($cell, 'Alumno') !== false || stripos($cell, 'Apellido') !== false || stripos($cell, 'Nombres') !== false) {
                        $hasStudentName = true;
                    }
                }

                if ($hasLegajoOrNro && $hasStudentName) {
                    $headerRowIndex = $idx;
                    $columns = $cleanRow;
                    break;
                }
            }

            if ($headerRowIndex === null) {
                throw new \RuntimeException('No se pudo identificar la fila de encabezados (Legajo/Nro; Alumno/Apellido y Nombres).');
            }

            // Identificar índices de columnas relevantes
            $colNro = $this->findColumnIndex($columns, ['Nro', 'N°', 'Nº', 'Orden']);
            $colLegajo = $this->findColumnIndex($columns, ['Legajo', 'Leg.']);
            $colNombre = $this->findColumnIndex($columns, ['Apellido y Nombres', 'Alumno', 'Estudiante', 'Apellido']);
            $colInasist = $this->findColumnIndex($columns, ['Inasist']);
            $colAgo = $this->findColumnIndex($columns, ['ago', '10, 17,24,31']);
            $col07Sep = $this->findColumnIndex($columns, ['7-sept', '07-sept', '07/09', '7/09']);
            $col14Sep = $this->findColumnIndex($columns, ['14-sept', '14/09']);
            $col21Sep = $this->findColumnIndex($columns, ['21-sept', '21/09']);
            $col28Sep = $this->findColumnIndex($columns, ['28-sept', '28/09']);
            $col05Oct = $this->findColumnIndex($columns, ['5-oct', '05-oct', '05/10', '5/10']);
            $colP1 = $this->findColumnIndex($columns, ['P1', 'PP', 'Primer Parcial', 'Parcial 1', 'Parcial I']);

            // 3. Resolver Curso
            if (! empty($metadata['comision'])) {
                $defaultCourseName = "Comisión {$metadata['comision']} ({$metadata['anio']})";
                $defaultSection = "Comisión {$metadata['comision']}";
            } elseif (! empty($metadata['periodo'])) {
                $defaultCourseName = "{$metadata['actividad_nombre']} - {$metadata['periodo']} {$metadata['anio']}";
                $defaultSection = 'Única';
            } else {
                $defaultCourseName = "{$metadata['actividad_nombre']} ({$metadata['anio']})";
                $defaultSection = 'Única';
            }

            if ($courseId) {
                $course = Course::findOrFail($courseId);
            } elseif ($subjectId) {
                $foundSubject = Subject::findOrFail($subjectId);
                $course = $foundSubject->course ?: Course::firstOrCreate(
                    [
                        'institution_id' => $institution->id,
                        'name' => $defaultCourseName,
                        'academic_year' => $metadata['anio'] ?? '2026',
                    ],
                    [
                        'section' => $defaultSection,
                    ]
                );
            } else {
                $course = Course::firstOrCreate(
                    [
                        'institution_id' => $institution->id,
                        'name' => $defaultCourseName,
                        'academic_year' => $metadata['anio'] ?? '2026',
                    ],
                    [
                        'section' => $defaultSection,
                    ]
                );
            }

            // 4. Resolver Materia
            $subjectName = $metadata['actividad_nombre'] ?? 'Programación IV';
            $subjectCode = $metadata['actividad_codigo'] ?? null;

            if ($subjectId) {
                $subject = Subject::findOrFail($subjectId);
                if ($course && $subject->course_id !== $course->id) {
                    $subject->update(['course_id' => $course->id]);
                }
            } else {
                $subject = Subject::firstOrCreate(
                    [
                        'institution_id' => $institution->id,
                        'course_id' => $course->id,
                        'name' => $subjectName,
                    ],
                    [
                        'code' => $subjectCode,
                        'teacher_name' => "Cátedra {$subjectName}",
                    ]
                );
            }

            // 5. Crear o buscar la Evaluación para el Primer Parcial (P1 o PP)
            $evaluationTitle = ($colP1 !== null && isset($columns[$colP1]) && stripos($columns[$colP1], 'P1') !== false)
                ? 'Primer Parcial (P1)'
                : 'Primer Parcial (PP)';

            $evaluation = Evaluation::firstOrCreate(
                [
                    'institution_id' => $institution->id,
                    'subject_id' => $subject->id,
                    'title' => $evaluationTitle,
                ],
                [
                    'description' => "Evaluación {$evaluationTitle} importada de planilla",
                    'weight_percentage' => 50,
                    'date' => '2026-10-05',
                ]
            );

            // 6. Procesar filas de alumnos, asistencias y notas
            $studentsCreated = 0;
            $studentsUpdated = 0;
            $attendancesCount = 0;
            $gradesCount = 0;
            $processedStudents = [];

            // Recorrer las filas de alumnos
            for ($i = $headerRowIndex + 1; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                if (empty($line)) {
                    continue;
                }

                $row = str_getcsv($line, ';');

                // Si todas las celdas de la fila están vacías, ignorar
                if (empty(array_filter($row, fn ($c) => trim($c) !== ''))) {
                    continue;
                }

                // Ignorar filas de pie de página como 'Datos', 'Tema:', 'Firma docente:'
                $firstCell = strtolower(trim($row[0] ?? ''));
                if (in_array($firstCell, ['datos', 'tema:', 'firma docente:'])) {
                    continue;
                }

                $nombreCompleto = ($colNombre !== null && isset($row[$colNombre])) ? trim($row[$colNombre]) : '';
                $legajo = ($colLegajo !== null && isset($row[$colLegajo])) ? trim($row[$colLegajo]) : null;
                $legajo = ($legajo === '' || $legajo === '0' || $legajo === '-') ? null : $legajo;
                $nro = ($colNro !== null && isset($row[$colNro])) ? trim($row[$colNro]) : '';

                if (empty($nombreCompleto) || (empty($legajo) && ! is_numeric($nro))) {
                    // No es una fila válida de estudiante
                    continue;
                }

                if (! is_numeric($nro)) {
                    $nro = $legajo ?: (string) ($i - $headerRowIndex);
                }

                // Limpiar notas o marcas agregadas como " (*)"
                $nombreCompleto = trim(preg_replace('/\s*\(\*\)\s*$/', '', $nombreCompleto));

                // Separar Apellido y Nombres (habitualmente separados por coma: 'Aguirre, Sira Gretel')
                [$lastName, $firstName] = $this->splitStudentName($nombreCompleto);

                // Generar o asignar RUT / DNI único
                $rutDni = $legajo ? "LEG-{$legajo}" : 'ALUM-2026-'.str_pad($nro, 4, '0', STR_PAD_LEFT);

                // Buscar o crear alumno
                $student = null;
                if ($legajo) {
                    $student = Student::where('institution_id', $institution->id)
                        ->where('course_id', $course->id)
                        ->where('legajo', $legajo)
                        ->first();
                }

                if (! $student) {
                    $student = Student::where('institution_id', $institution->id)
                        ->where('course_id', $course->id)
                        ->where('last_name', $lastName)
                        ->where('first_name', $firstName)
                        ->first();
                }

                if ($student) {
                    $student->update([
                        'legajo' => $legajo ?? $student->legajo,
                        'rut_dni' => $student->rut_dni ?? $rutDni,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                    ]);
                    $studentsUpdated++;
                } else {
                    $student = Student::create([
                        'institution_id' => $institution->id,
                        'course_id' => $course->id,
                        'legajo' => $legajo,
                        'rut_dni' => $rutDni,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'status' => 'active',
                    ]);
                    $studentsCreated++;
                }

                $processedStudents[] = [
                    'id' => $student->id,
                    'nro' => $nro,
                    'legajo' => $legajo,
                    'name' => $student->full_name,
                ];

                // === ASISTENCIAS (si existen en la planilla) ===
                if ($colAgo !== null && isset($row[$colAgo]) && is_numeric(trim($row[$colAgo]))) {
                    $diasAsistidosAgo = min(4, max(0, (int) trim($row[$colAgo])));
                    $fechasAgosto = ['2026-08-10', '2026-08-17', '2026-08-24', '2026-08-31'];

                    foreach ($fechasAgosto as $k => $fechaAgo) {
                        $statusAgo = ($k < $diasAsistidosAgo) ? 'present' : 'absent';
                        $this->recordAttendance($institution->id, $student->id, $course->id, $subject->id, $fechaAgo, $statusAgo, 'Asistencia Agosto');
                        $attendancesCount++;
                    }
                }

                if ($col07Sep !== null && isset($row[$col07Sep])) {
                    $val = trim($row[$col07Sep]);
                    if ($val === '1' || $val === '0') {
                        $this->recordAttendance($institution->id, $student->id, $course->id, $subject->id, '2026-09-07', $val === '1' ? 'present' : 'absent');
                        $attendancesCount++;
                    }
                }

                if ($col14Sep !== null && isset($row[$col14Sep])) {
                    $val = trim($row[$col14Sep]);
                    if ($val === '1' || $val === '0') {
                        $this->recordAttendance($institution->id, $student->id, $course->id, $subject->id, '2026-09-14', $val === '1' ? 'present' : 'absent');
                        $attendancesCount++;
                    }
                }

                if ($col21Sep !== null && isset($row[$col21Sep])) {
                    $val = strtoupper(trim($row[$col21Sep]));
                    if ($val === 'F') {
                        $this->recordAttendance($institution->id, $student->id, $course->id, $subject->id, '2026-09-21', 'justified', 'Feriado (Día del Estudiante)');
                        $attendancesCount++;
                    } elseif ($val === '1' || $val === '0') {
                        $this->recordAttendance($institution->id, $student->id, $course->id, $subject->id, '2026-09-21', $val === '1' ? 'present' : 'absent');
                        $attendancesCount++;
                    }
                }

                if ($col28Sep !== null && isset($row[$col28Sep])) {
                    $val = trim($row[$col28Sep]);
                    if ($val === '1' || $val === '0') {
                        $this->recordAttendance($institution->id, $student->id, $course->id, $subject->id, '2026-09-28', $val === '1' ? 'present' : 'absent');
                        $attendancesCount++;
                    }
                }

                if ($col05Oct !== null && isset($row[$col05Oct])) {
                    $val = trim($row[$col05Oct]);
                    if ($val === '1' || $val === '0') {
                        $this->recordAttendance($institution->id, $student->id, $course->id, $subject->id, '2026-10-05', $val === '1' ? 'present' : 'absent');
                        $attendancesCount++;
                    }
                }

                // === NOTA DEL PRIMER PARCIAL (P1 / PP) ===
                if ($colP1 !== null && isset($row[$colP1])) {
                    $valP1 = trim($row[$colP1]);
                    // Reemplazar coma decimal por punto (ej. 77,5 -> 77.5)
                    $valP1 = str_replace(',', '.', $valP1);

                    if (is_numeric($valP1)) {
                        $score = (float) $valP1;
                        Grade::updateOrCreate(
                            [
                                'evaluation_id' => $evaluation->id,
                                'student_id' => $student->id,
                            ],
                            [
                                'score' => $score,
                                'feedback' => "Calificación {$evaluationTitle}",
                            ]
                        );
                        $gradesCount++;
                    }
                }
            }

            // Fechas de asistencias procesadas (solo si la planilla tenía columnas de asistencia)
            $importedDates = [];
            if ($colAgo !== null && $attendancesCount > 0) {
                $importedDates = array_merge($importedDates, ['2026-08-10', '2026-08-17', '2026-08-24', '2026-08-31']);
            }
            if ($col07Sep !== null && $attendancesCount > 0) {
                $importedDates[] = '2026-09-07';
            }
            if ($col14Sep !== null && $attendancesCount > 0) {
                $importedDates[] = '2026-09-14';
            }
            if ($col21Sep !== null && $attendancesCount > 0) {
                $importedDates[] = '2026-09-21 (Feriado)';
            }
            if ($col28Sep !== null && $attendancesCount > 0) {
                $importedDates[] = '2026-09-28';
            }
            if ($col05Oct !== null && $attendancesCount > 0) {
                $importedDates[] = '2026-10-05';
            }

            return [
                'success' => true,
                'institution' => $institution,
                'course' => $course,
                'subject' => $subject,
                'evaluation' => $evaluation,
                'students_created' => $studentsCreated,
                'students_updated' => $studentsUpdated,
                'students_total' => count($processedStudents),
                'attendances_count' => $attendancesCount,
                'grades_count' => $gradesCount,
                'dates_imported' => $importedDates,
            ];
        });
    }

    /**
     * Registra o actualiza una asistencia individual.
     */
    protected function recordAttendance(int $institutionId, int $studentId, int $courseId, int $subjectId, string $date, string $status, ?string $notes = null): void
    {
        Attendance::updateOrCreate(
            [
                'institution_id' => $institutionId,
                'student_id' => $studentId,
                'course_id' => $courseId,
                'subject_id' => $subjectId,
                'date' => $date,
            ],
            [
                'status' => $status,
                'notes' => $notes,
            ]
        );
    }

    /**
     * Separa un string 'APELLIDO, NOMBRES' en array [apellido, nombres].
     */
    protected function splitStudentName(string $fullName): array
    {
        if (str_contains($fullName, ',')) {
            $parts = explode(',', $fullName, 2);
            $lastName = trim($parts[0]);
            $firstName = trim($parts[1]);
        } else {
            $parts = preg_split('/\s+/', $fullName);
            if (count($parts) > 1) {
                $lastName = array_shift($parts);
                $firstName = implode(' ', $parts);
            } else {
                $lastName = $fullName;
                $firstName = '-';
            }
        }

        return [$lastName, $firstName];
    }

    /**
     * Encuentra el índice de una columna dada una lista de posibles nombres.
     */
    protected function findColumnIndex(array $columns, array $needles): ?int
    {
        foreach ($columns as $idx => $col) {
            foreach ($needles as $needle) {
                if (stripos($col, $needle) !== false) {
                    return $idx;
                }
            }
        }

        return null;
    }

    /**
     * Extrae información general de las cabeceras de la planilla.
     */
    protected function parseMetadata(array $lines): array
    {
        $meta = [
            'titulo' => '',
            'anio' => '2026',
            'periodo' => '',
            'comision' => '',
            'ubicacion' => 'Rectorado',
            'modalidad' => 'Presencial',
            'horario' => '',
            'actividad_nombre' => 'Programación IV',
            'actividad_codigo' => null,
        ];

        foreach (array_slice($lines, 0, 10) as $line) {
            $cols = str_getcsv($line, ';');

            foreach ($cols as $cIdx => $val) {
                $clean = trim($val);
                if (empty($clean)) {
                    continue;
                }

                if (stripos($clean, 'PLANILLA') !== false) {
                    $meta['titulo'] = $clean;
                }

                // Detectar "Alumnos cursando: Programación IV (Tec.Univ.Prog.)"
                if (stripos($clean, 'Alumnos cursando:') !== false) {
                    $meta['titulo'] = $clean;
                    $subjectPart = trim(str_ireplace('Alumnos cursando:', '', $clean));
                    if (preg_match('/^(.*?)\s*\((.*?)\)$/', $subjectPart, $m)) {
                        $meta['actividad_nombre'] = trim($m[1]);
                        $meta['actividad_codigo'] = trim($m[2]);
                    } else {
                        $meta['actividad_nombre'] = $subjectPart;
                    }
                }

                // Detectar "Comision: 21 - 2026"
                if (stripos($clean, 'Comision:') !== false || stripos($clean, 'Comisión:') !== false) {
                    $comPart = trim(preg_replace('/^Comisi[oó]n:\s*/i', '', $clean));
                    if (preg_match('/^(\w+)\s*-\s*(\d{4})$/', $comPart, $m)) {
                        $meta['comision'] = trim($m[1]);
                        $meta['anio'] = trim($m[2]);
                    } elseif (preg_match('/^(\w+)/', $comPart, $m)) {
                        $meta['comision'] = trim($m[1]);
                    }
                }

                if (stripos($clean, 'Acad') !== false && isset($cols[$cIdx + 1])) {
                    $meta['anio'] = trim($cols[$cIdx + 1]) ?: $meta['anio'];
                }
                if (stripos($clean, 'Lectivo') !== false && isset($cols[$cIdx + 1])) {
                    $meta['periodo'] = trim($cols[$cIdx + 1]) ?: $meta['periodo'];
                }
                if (stripos($clean, 'Ubicaci') !== false && isset($cols[$cIdx + 1])) {
                    $meta['ubicacion'] = trim($cols[$cIdx + 1]) ?: $meta['ubicacion'];
                }
                if (stripos($clean, 'Actividad') !== false && isset($cols[$cIdx + 1])) {
                    $act = trim($cols[$cIdx + 1]);
                    if (preg_match('/^(.*?)\s*\((\d+)\)$/', $act, $matches)) {
                        $meta['actividad_nombre'] = trim($matches[1]);
                        $meta['actividad_codigo'] = trim($matches[2]);
                    } elseif (! empty($act)) {
                        $meta['actividad_nombre'] = $act;
                    }
                }
            }
        }

        return $meta;
    }

    /**
     * Normaliza caracteres y codificación defectuosa (Latin1/UTF-8).
     */
    protected function normalizeEncoding(string $str): string
    {
        // Detectar si no es UTF-8 (ej. Windows-1252 o ISO-8859-1 de Excel)
        if (! mb_check_encoding($str, 'UTF-8')) {
            $str = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
        }

        // Si detecta secuencia UTF-8 rota o caracteres comunes corruptos con el caracter de reemplazo
        $replacements = [
            "A\u{FFFD}o" => 'Año',
            "Per\u{FFFD}odo" => 'Período',
            "Ubicaci\u{FFFD}n" => 'Ubicación',
            "Te\u{FFFD}rico-Pr\u{FFFD}ctica" => 'Teórico-Práctica',
            "GARC\u{FFFD}A" => 'GARCÍA',
            "GAVIL\u{FFFD}N" => 'GAVILÁN',
            "GAST\u{FFFD}N" => 'GASTÓN',
            "Programaci\u{FFFD}n" => 'Programación',
            "Mari\u{FFFD}n" => 'Marián',
            "Jos\u{FFFD}" => 'José',
            "Budi\u{FFFD}o" => 'Budiño',
            "Nu\u{FFFD}ez" => 'Nuñez',
            "Jes\u{FFFD}s" => 'Jesús',
            "Anah\u{FFFD}" => 'Anahí',
            "Mar\u{FFFD}a" => 'María',
            "Mart\u{FFFD}n" => 'Martín',
            "Comisi\u{FFFD}n" => 'Comisión',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $str);
    }
}
