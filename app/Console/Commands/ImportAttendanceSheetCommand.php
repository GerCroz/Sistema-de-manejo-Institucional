<?php

namespace App\Console\Commands;

use App\Services\AttendanceSheetImportService;
use Illuminate\Console\Command;

class ImportAttendanceSheetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'school:import-sheet
                            {file? : Ruta al archivo CSV/TXT a importar (por defecto storage/app/imports/planilla_so_redes.csv)}
                            {--institution= : ID de la institución}
                            {--course= : ID del curso}
                            {--subject= : ID de la materia}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa planilla académica con alumnos, asistencias y calificaciones de parciales.';

    /**
     * Execute the console command.
     */
    public function handle(AttendanceSheetImportService $service): int
    {
        $filePath = $this->argument('file') ?? base_path('storage/app/imports/planilla_so_redes.csv');

        if (! file_exists($filePath)) {
            $this->error("El archivo no existe en la ruta: {$filePath}");

            return self::FAILURE;
        }

        $this->info("Leyendo archivo: {$filePath}");
        $content = file_get_contents($filePath);

        try {
            $institutionId = $this->option('institution') ? (int) $this->option('institution') : null;
            $courseId = $this->option('course') ? (int) $this->option('course') : null;
            $subjectId = $this->option('subject') ? (int) $this->option('subject') : null;

            $result = $service->import($content, $institutionId, $courseId, $subjectId);

            $this->newLine();
            $this->info('=== IMPORTACIÓN COMPLETADA CON ÉXITO ===');
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Institución', $result['institution']->name],
                    ['Curso', $result['course']->name],
                    ['Materia / Cátedra', "{$result['subject']->name} ({$result['subject']->code})"],
                    ['Evaluación Generada', $result['evaluation']->title],
                    ['Total Alumnos Procesados', $result['students_total']],
                    ['Alumnos Nuevos Creados', $result['students_created']],
                    ['Alumnos Actualizados', $result['students_updated']],
                    ['Registros de Asistencia Creados', $result['attendances_count']],
                    ['Calificaciones Registradas (PP)', $result['grades_count']],
                ]
            );

            $this->newLine();
            $this->line('<fg=green>Fechas con asistencia importada:</>');
            foreach ($result['dates_imported'] as $date) {
                $this->line("  ✓ {$date}");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error durante la importación: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
