<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\AttendanceSheetImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AttendanceSheetImportTest extends TestCase
{
    use RefreshDatabase;

    private string $sampleCsv;

    private string $prog4Csv;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::create([
            'name' => 'Admin Import Tester',
            'email' => 'admin.import@escuela.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

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

        $this->prog4Csv = ';Alumnos cursando: Programación IV (Tec.Univ.Prog.);;;;;;;;;
;Comision: 21 - 2026;;;;;;;;;
;;;;;;;;;;
;;;;;;;;;;
Legajo;Alumno;P1;P2;P3;R1;R2;R3;Final;;
30581;Aguirre, Sira Gretel (*);7;-;-;-;-;-;0;;
30628;Antonelli, Lucas Gabriel (*);8;-;-;-;-;-;0;;
30582;Arce, Angel Gabriel (*);8;-;-;-;-;-;0;;
30004;Barcos, Lia Marián;-;-;-;-;-;-;0;;
30584;Benitez, Apolo Salomon (*);5;-;-;-;-;-;0;;
30587;Benitez, Dolores Antonia (*);5;-;-;-;-;-;0;;
30585;Benitez, José Alexander (*);7;-;-;-;-;-;0;;
30586;Benitez, Santiago Miguel (*);6;-;-;-;-;-;0;;
30037;Blanco Wuest, Naldo Fabian (*);9;-;-;-;-;-;0;;
30588;Bordon, Guillermo Anibal (*);6;-;-;-;-;-;0;;
30589;Britez, Jonathan Javier (*);6;-;-;-;-;-;0;;
30590;Budiño, Valeria (*);8;-;-;-;-;-;0;;
30629;Cardozo, Benjamin Emanuel (*);8;-;-;-;-;-;0;;
30592;Cardozo, Diego Fabian (*);8;-;-;-;-;-;0;;
30591;Cardozo, Mauricio Nair (*);8;-;-;-;-;-;0;;
29144;Casanueva, Samuel Dario (*);8;-;-;-;-;-;0;;
30593;Castillo, Rodrigo Fabian (*);8;-;-;-;-;-;0;;
30005;Centurion, Tomas Emanuel (*);6;-;-;-;-;-;0;;
30594;Cislaghi, Gabriel Emilio (*);6;-;-;-;-;-;0;;
31215;Corrales, Ramiro Exequiel (*);9;-;-;-;-;-;0;;
30595;Cuellar, Alex Marcelo (*);6;-;-;-;-;-;0;;
30630;Fernandez, Abraham Danilo (*);8;-;-;-;-;-;0;;
29085;Gabriel, Silvio (*);8;-;-;-;-;-;0;;
30599;Garcia, Santiago Adriel (*);7;-;-;-;-;-;0;;
30631;Gines, Fabricio Facundo (*);8;-;-;-;-;-;0;;
30600;Gonzalez, Enrique Maximiliano (*);9;-;-;-;-;-;0;;
29108;Heretichi, Adriana Raquel (*);8;-;-;-;-;-;0;;
30601;Lopez, Gabriela Elvira (*);6;-;-;-;-;-;0;;
30603;Medina, Nadia Carolina (*);7;-;-;-;-;-;0;;
30604;Mendoza, Fabian Nicolas (*);7;-;-;-;-;-;0;;
30608;Molina, Patricia Elizabeth (*);6;-;-;-;-;-;0;;
30610;Nieva, Marcelo Fernando (*);8;-;-;-;-;-;0;;
30611;Nuñez, Matias Daniel (*);7;-;-;-;-;-;0;;
30612;Olmedo, Ivan Alejandro de Jesús (*);5;-;-;-;-;-;0;;
30633;Ortiz, Marcos Andres Ramon (*);6;-;-;-;-;-;0;;
30614;Oviedo, Micaela Tomasa (*);7;-;-;-;-;-;0;;
30615;Pineda, Antonio Gabriel (*);9;-;-;-;-;-;0;;
30009;Portillo, Anahí Elizabeth (*);6;-;-;-;-;-;0;;
30616;Quiroga, Alejandro Fabian (*);9;-;-;-;-;-;0;;
30010;Rahn, Ana Karen (*);7;-;-;-;-;-;0;;
30617;Riveros, María Laura (*);9;-;-;-;-;-;0;;
30618;Rojas Jojot, Jorge Luis (*);8;-;-;-;-;-;0;;
30013;Rojas, Yasmin Beatriz (*);8;-;-;-;-;-;0;;
30619;Sanchez, Sergio Martín Laureano (*);8;-;-;-;-;-;0;;
30620;Saucedo, Alexia Mariel (*);8;-;-;-;-;-;0;;
30621;Servian, Sixto Manuel (*);8;-;-;-;-;-;0;;
29091;Silvera, Cristian Anibal (*);7;-;-;-;-;-;0;;
30622;Soria del Valle, Sergio Nicolas (*);7;-;-;-;-;-;0;;
30626;Zaracho, Fernando Daniel;-;-;-;-;-;-;0;;';
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

    public function test_import_programacion_iv_sheet_with_p1_grades_and_no_attendance(): void
    {
        $institution = Institution::create([
            'name' => 'Instituto Politécnico',
            'code' => 'IP-01',
        ]);

        $service = app(AttendanceSheetImportService::class);
        $result = $service->import($this->prog4Csv, $institution->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(49, $result['students_total']);
        $this->assertEquals(47, $result['grades_count']); // 47 con nota numérica, 2 con '-'
        $this->assertEquals(0, $result['attendances_count']);
        $this->assertEmpty($result['dates_imported']);
        $this->assertEquals('Primer Parcial (P1)', $result['evaluation']->title);

        // Verificar estudiante Aguirre con nota 7
        $aguirre = Student::where('legajo', '30581')->first();
        $this->assertNotNull($aguirre);
        $this->assertEquals('Aguirre', $aguirre->last_name);
        $this->assertEquals('Sira Gretel', $aguirre->first_name);
        $aguirreGrade = Grade::where('student_id', $aguirre->id)->where('evaluation_id', $result['evaluation']->id)->first();
        $this->assertNotNull($aguirreGrade);
        $this->assertEquals(7.0, (float) $aguirreGrade->score);

        // Verificar estudiante Barcos sin nota
        $barcos = Student::where('legajo', '30004')->first();
        $this->assertNotNull($barcos);
        $barcosGrade = Grade::where('student_id', $barcos->id)->where('evaluation_id', $result['evaluation']->id)->first();
        $this->assertNull($barcosGrade);

        // Verificar estudiante Benitez, José Alexander con nota 7
        $benitez = Student::where('legajo', '30585')->first();
        $this->assertNotNull($benitez);
        $this->assertEquals('José Alexander', $benitez->first_name);
        $benitezGrade = Grade::where('student_id', $benitez->id)->first();
        $this->assertNotNull($benitezGrade);
        $this->assertEquals(7.0, (float) $benitezGrade->score);

        // Verificar estudiante Zaracho sin nota
        $zaracho = Student::where('legajo', '30626')->first();
        $this->assertNotNull($zaracho);
        $zarachoGrade = Grade::where('student_id', $zaracho->id)->first();
        $this->assertNull($zarachoGrade);
    }

    public function test_import_with_manually_selected_career_and_subject(): void
    {
        $institution = Institution::create([
            'name' => 'Universidad Central',
            'code' => 'UC-01',
        ]);

        $course = Course::create([
            'institution_id' => $institution->id,
            'name' => 'Tecnicatura en Programación - Comisión 21',
            'section' => 'Comisión 21',
            'academic_year' => '2026',
        ]);

        $subject = Subject::create([
            'institution_id' => $institution->id,
            'course_id' => $course->id,
            'name' => 'Programación IV Avanzada',
            'code' => 'TUP-401',
            'teacher_name' => 'Prof. Juan Pérez',
        ]);

        $postResponse = $this->post(route('import.process'), [
            'raw_content' => $this->prog4Csv,
            'institution_id' => $institution->id,
            'course_id' => $course->id,
            'subject_id' => $subject->id,
        ]);

        $postResponse->assertRedirect(route('import.index'));
        $postResponse->assertSessionHas('success');

        // Los estudiantes deben pertenecer al curso seleccionado manualmente
        $studentsInCourse = Student::where('course_id', $course->id)->count();
        $this->assertEquals(49, $studentsInCourse);

        // Las notas deben pertenecer a una evaluación de la materia seleccionada manualmente
        $evaluation = Evaluation::where('subject_id', $subject->id)->first();
        $this->assertNotNull($evaluation);
        $this->assertEquals(47, Grade::where('evaluation_id', $evaluation->id)->count());
    }

    public function test_import_with_dynamic_new_course_and_subject_names(): void
    {
        $institution = Institution::create([
            'name' => 'Universidad Regional',
            'code' => 'UR-01',
        ]);

        $postResponse = $this->post(route('import.process'), [
            'raw_content' => $this->prog4Csv,
            'institution_id' => $institution->id,
            'new_course_name' => 'Ingeniería en Sistemas - Com 21',
            'new_subject_name' => 'Programación IV Sistemas',
            'new_subject_code' => 'SIS-404',
        ]);

        $postResponse->assertRedirect(route('import.index'));
        $postResponse->assertSessionHas('success');

        $createdCourse = Course::where('name', 'Ingeniería en Sistemas - Com 21')->first();
        $this->assertNotNull($createdCourse);

        $createdSubject = Subject::where('name', 'Programación IV Sistemas')->first();
        $this->assertNotNull($createdSubject);
        $this->assertEquals('SIS-404', $createdSubject->code);

        $this->assertEquals(49, Student::where('course_id', $createdCourse->id)->count());
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
        $response->assertSee('Carrera / Curso');
        $response->assertSee('Materia / Asignatura');

        $postResponse = $this->post(route('import.process'), [
            'raw_content' => $this->sampleCsv,
            'institution_id' => $institution->id,
        ]);

        $postResponse->assertRedirect(route('import.index'));
        $postResponse->assertSessionHas('success');
        $postResponse->assertSessionHas('import_result');
    }

    public function test_import_processes_uploaded_file(): void
    {
        $institution = Institution::create([
            'name' => 'Universidad Upload Test',
            'code' => 'UPLD-01',
        ]);

        $file = UploadedFile::fake()->createWithContent('planilla.csv', $this->sampleCsv);

        $postResponse = $this->post(route('import.process'), [
            'file' => $file,
            'institution_id' => $institution->id,
        ]);

        $postResponse->assertRedirect(route('import.index'));
        $postResponse->assertSessionHas('success');
        $postResponse->assertSessionHas('import_result');
    }
}
