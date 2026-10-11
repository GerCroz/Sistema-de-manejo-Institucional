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

            // 2. Resolver Curso
            $courseName = ! empty($metadata['periodo'])
                ? "Sistemas Operativos - {$metadata['periodo']} {$metadata['anio']}"
                : "Sistemas Operativos y Redes ({$metadata['anio']})";

            $course = $courseId
                ? Course::findOrFail($courseId)
                : Course::firstOrCreate(
                    [
                        'institution_id' => $institution->id,
                        'name' => $courseName,
                        'academic_year' => $metadata['anio'] ?? '2026',
                    ],
                    [
                        'section' => 'Única',
                    ]
                );

            // 3. Resolver Materia
            $subjectName = $metadata['actividad_nombre'] ?? 'Sistemas Operativos y Redes';
            $subjectCode = $metadata['actividad_codigo'] ?? '208';

            $subject = $subjectId
                ? Subject::findOrFail($subjectId)
                : Subject::firstOrCreate(
                    [
                        'institution_id' => $institution->id,
                        'course_id' => $course->id,
                        'name' => $subjectName,
                    ],
                    [
                        'code' => $subjectCode,
                        'teacher_name' => 'Cátedra SO y Redes',
                    ]
                );

            // 4. Crear o buscar la Evaluación para "Primer Parcial (PP)"
            $evaluation = Evaluation::firstOrCreate(
                [
                    'institution_id' => $institution->id,
                    'subject_id' => $subject->id,
                    'title' => 'Primer Parcial (PP)',
                ],
                [
                    'description' => 'Evaluación Parcial 1 importada de planilla de cátedra',
                    'weight_percentage' => 50,
                    'date' => '2026-10-05',
                ]
            );

            // 5. Procesar filas de alumnos, asistencias y notas
            $studentsCreated = 0;
            $studentsUpdated = 0;
            $attendancesCount = 0;
            $gradesCount = 0;
            $processedStudents = [];

            // Buscar la línea de encabezado de columnas (Nro, Legajo, Apellido y Nombres...)
            $headerRowIndex = null;
            $columns = [];

            foreach ($lines as $idx => $line) {
                $row = str_getcsv($line, ';');
                if (isset($row[0], $row[2]) && (stripos($row[0], 'Nro') !== false || stripos($row[2], 'Apellido') !== false)) {
                    $headerRowIndex = $idx;
                    $columns = array_map('trim', $row);
                    break;
                }
            }

            if ($headerRowIndex === null) {
                throw new \RuntimeException('No se pudo identificar la fila de encabezados (Nro; Legajo; Apellido y Nombres).');
            }

            // Identificar índices de columnas relevantes
            $colNro = $this->findColumnIndex($columns, ['Nro', 'N°', 'Nº']);
            $colLegajo = $this->findColumnIndex($columns, ['Legajo']);
            $colNombre = $this->findColumnIndex($columns, ['Apellido y Nombres', 'Apellido', 'Alumno']);
            $colInasist = $this->findColumnIndex($columns, ['Inasist']);
            $colAgo = $this->findColumnIndex($columns, ['ago', '10, 17,24,31']);
            $col07Sep = $this->findColumnIndex($columns, ['7-sept', '07-sept', '07/09', '7/09']);
            $col14Sep = $this->findColumnIndex($columns, ['14-sept', '14/09']);
            $col21Sep = $this->findColumnIndex($columns, ['21-sept', '21/09']);
            $col28Sep = $this->findColumnIndex($columns, ['28-sept', '28/09']);
            $col05Oct = $this->findColumnIndex($columns, ['5-oct', '05-oct', '05/10', '5/10']);
            $colPP = $this->findColumnIndex($columns, ['PP', 'Primer Parcial', 'Parcial 1']);

            // Recorrer las filas de alumnos
            for ($i = $headerRowIndex + 1; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                if (empty($line)) {
                    continue;
                }

                $row = str_getcsv($line, ';');
                // Ignorar filas de pie de página como 'Datos', 'Tema:', 'Firma docente:'
                if (isset($row[0]) && in_array(strtolower(trim($row[0])), ['datos', 'tema:', 'firma docente:', ''])) {
                    if (empty($row[0]) && empty($row[1]) && empty($row[2])) {
                        continue;
                    }
                }

                $nro = isset($row[$colNro]) ? trim($row[$colNro]) : '';
                $nombreCompleto = isset($row[$colNombre]) ? trim($row[$colNombre]) : '';
                $legajo = isset($row[$colLegajo]) ? trim($row[$colLegajo]) : null;
                $legajo = ($legajo === '' || $legajo === '0') ? null : $legajo;

                if (empty($nombreCompleto) || ! is_numeric($nro)) {
                    // No es una fila válida de estudiante
                    continue;
                }

                // Separar Apellido y Nombres (habitualmente separados por coma: 'ACOSTA, KARINA AILEN')
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

                // === ASISTENCIAS ===
                // A) Clases de Agosto (10, 17, 24, 31 de agosto)
                // En la planilla viene el acumulado asistido (de 0 a 4)
                if ($colAgo !== null && isset($row[$colAgo]) && is_numeric(trim($row[$colAgo]))) {
                    $diasAsistidosAgo = min(4, max(0, (int) trim($row[$colAgo])));
                    $fechasAgosto = ['2026-08-10', '2026-08-17', '2026-08-24', '2026-08-31'];

                    foreach ($fechasAgosto as $k => $fechaAgo) {
                        // Si el contador es mayor al índice, se computa presente
                        $statusAgo = ($k < $diasAsistidosAgo) ? 'present' : 'absent';
                        $this->recordAttendance($institution->id, $student->id, $course->id, $subject->id, $fechaAgo, $statusAgo, 'Asistencia Agosto');
                        $attendancesCount++;
                    }
                }

                // B) 07 de Septiembre
                if ($col07Sep !== null && isset($row[$col07Sep])) {
                    $val = trim($row[$col07Sep]);
                    if ($val === '1' || $val === '0') {
                        $this->recordAttendance($institution->id, $student->id, $course->id, $subject->id, '2026-09-07', $val === '1' ? 'present' : 'absent');
                        $attendancesCount++;
                    }
                }

                // C) 14 de Septiembre
                if ($col14Sep !== null && isset($row[$col14Sep])) {
                    $val = trim($row[$col14Sep]);
                    if ($val === '1' || $val === '0') {
                        $this->recordAttendance($institution->id, $student->id, $course->id, $subject->id, '2026-09-14', $val === '1' ? 'present' : 'absent');
                        $attendancesCount++;
                    }
                }

                // D) 21 de Septiembre (F = Feriado / Primavera)
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

                // E) 28 de Septiembre
                if ($col28Sep !== null && isset($row[$col28Sep])) {
                    $val = trim($row[$col28Sep]);
                    if ($val === '1' || $val === '0') {
                        $this->recordAttendance($institution->id, $student->id, $course->id, $subject->id, '2026-09-28', $val === '1' ? 'present' : 'absent');
                        $attendancesCount++;
                    }
                }

                // F) 05 de Octubre
                if ($col05Oct !== null && isset($row[$col05Oct])) {
                    $val = trim($row[$col05Oct]);
                    if ($val === '1' || $val === '0') {
                        $this->recordAttendance($institution->id, $student->id, $course->id, $subject->id, '2026-10-05', $val === '1' ? 'present' : 'absent');
                        $attendancesCount++;
                    }
                }

                // === NOTA DEL PRIMER PARCIAL (PP) ===
                if ($colPP !== null && isset($row[$colPP])) {
                    $valPP = trim($row[$colPP]);
                    // Reemplazar coma decimal por punto (ej. 77,5 -> 77.5)
                    $valPP = str_replace(',', '.', $valPP);

                    if (is_numeric($valPP)) {
                        $score = (float) $valPP;
                        Grade::updateOrCreate(
                            [
                                'evaluation_id' => $evaluation->id,
                                'student_id' => $student->id,
                            ],
                            [
                                'score' => $score,
                                'feedback' => 'Calificación Primer Parcial (PP)',
                            ]
                        );
                        $gradesCount++;
                    }
                }
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
                'dates_imported' => [
                    '2026-08-10',
                    '2026-08-17',
                    '2026-08-24',
                    '2026-08-31',
                    '2026-09-07',
                    '2026-09-14',
                    '2026-09-21 (Feriado)',
                    '2026-09-28',
                    '2026-10-05',
                ],
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
            'periodo' => '2do Cuatrimestre',
            'ubicacion' => 'Rectorado',
            'modalidad' => 'Presencial',
            'horario' => 'Lun 15:00 a 17:00',
            'actividad_nombre' => 'SISTEMAS OPERATIVOS Y REDES',
            'actividad_codigo' => '208',
        ];

        foreach (array_slice($lines, 0, 7) as $line) {
            $cols = str_getcsv($line, ';');

            foreach ($cols as $cIdx => $val) {
                $clean = trim($val);
                if (stripos($clean, 'PLANILLA') !== false) {
                    $meta['titulo'] = $clean;
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
        // Si detecta secuencia UTF-8 rota o caracteres comunes corruptos
        $replacements = [
            'Ao' => 'Año',
            'Perodo' => 'Período',
            'Ubicacin' => 'Ubicación',
            'Terico-Prctica' => 'Teórico-Práctica',
            'GARCA' => 'GARCÍA',
            'GAVILN' => 'GAVILÁN',
            'GASTN' => 'GASTÓN',
            '' => 'í',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $str);
    }
}
