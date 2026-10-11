<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\Student;
use App\Services\AttendanceSheetImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSheetImportTest extends TestCase
{
    use RefreshDatabase;

    private string $sampleCsv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sampleCsv = 'PLANILLA DE ASISTENCIA – SISTEMAS OPERATIVOS Y REDES;;;;;;;;;;;;
Año Académico;2026;Período Lectivo;2do Cuatrimestre;;Clases desde;31/08/2026;;;;;;
Ubicación;Rectorado;Modalidad;Presencial;;Horario;Lun 15:00 a 17:00 - Teórico-Práctica;;;;;;
Actividad;SISTEMAS OPERATIVOS Y REDES (208);;;;;;;;;;;
;;;;;;;;;;;;
Nro;Legajo;Apellido y Nombres;Inasist (Just);10, 17,24,31 de ago;7-sept;14-sept;21-sept;28-sept;5-oct;PP;19-oct;26-oct
1;7253;ACOSTA, KARINA AILEN;0.00 (0.00);4;1;1;F;1;1;16;;
2;8007;ALARCON, PATRICIO YAMIL;0.00 (0.00);3;1;1;F;1;1;77,5;;
3;;AQUINO, OMAR IVAN;0.00 (0.00);4;1;1;F;1;1;40,5;;
12;;BOGADO, GUSTAVO ARIEL;;4;1;1;F;1;1;100;;
31;;GARCIA, NOELIA RAQUEL;;0;0;0;F;0;0;;;';
    }

    public function test_service_imports_students_attendances_and_grades_correctly(): void
    {
        $institution = Institution::create([
            'name' => 'Universidad Nacional - Rectorado',
            'code' => 'UNIV-01',
        ]);

        $service = app(AttendanceSheetImportService::class);
        $result = $service->import($this->sampleCsv, $institution->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['students_total']);
        $this->assertEquals(4, $result['grades_count']); // 4 tienen notas, 1 sin nota (NOELIA)

        // Verificar alumno con legajo
        $acosta = Student::where('last_name', 'ACOSTA')->first();
        $this->assertNotNull($acosta);
        $this->assertEquals('7253', $acosta->legajo);
        $this->assertEquals('KARINA AILEN', $acosta->first_name);

        // Verificar nota 16
        $acostaGrade = Grade::where('student_id', $acosta->id)->first();
        $this->assertNotNull($acostaGrade);
        $this->assertEquals(16.0, (float) $acostaGrade->score);

        // Verificar alumno con 100
        $bogado = Student::where('last_name', 'BOGADO')->first();
        $this->assertNotNull($bogado);
        $bogadoGrade = Grade::where('student_id', $bogado->id)->first();
        $this->assertEquals(100.0, (float) $bogadoGrade->score);

        // Verificar asistencias para ACOSTA: 4 (agosto) + 1 (7/9) + 1 (14/9) + 1 (21/9 Feriado) + 1 (28/9) + 1 (5/10) = 9
        $this->assertEquals(9, $acosta->attendances()->count());

        // Verificar que el feriado del 21/09 se marque justificado
        $holidayAtt = Attendance::where('student_id', $acosta->id)->where('date', '2026-09-21')->first();
        $this->assertNotNull($holidayAtt);
        $this->assertEquals('justified', $holidayAtt->status);
    }

    public function test_import_web_route_renders_and_processes(): void
    {
        $institution = Institution::create([
            'name' => 'Universidad Test',
            'code' => 'UTEST-01',
        ]);

        $response = $this->get(route('import.index'));
        $response->assertStatus(200);
        $response->assertSee('Importar Planilla de Cátedra');

        $postResponse = $this->post(route('import.process'), [
            'raw_content' => $this->sampleCsv,
            'institution_id' => $institution->id,
        ]);

        $postResponse->assertRedirect(route('import.index'));
        $postResponse->assertSessionHas('success');
        $postResponse->assertSessionHas('import_result');
    }
}
