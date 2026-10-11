<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Docente Principal (gestiona 2 instituciones)
        $teacher = User::create([
            'name' => 'Prof. Carlos Mendoza',
            'email' => 'carlos.mendoza@escuela.edu',
            'title' => 'Docente Senior de Ciencias y Matemáticas',
            'password' => bcrypt('password123'),
        ]);

        // 2. Institución 1: Colegio San Agustín
        $inst1 = Institution::create([
            'name' => 'Colegio San Agustín',
            'code' => 'CSA-001',
            'address' => 'Av. Providencia 1420, Santiago',
            'phone' => '+56 2 2345 6789',
            'email' => 'contacto@sanagustin.edu',
            'logo_color' => '#4f46e5', // Indigo
        ]);

        // 3. Institución 2: Liceo Bicentenario Santa María
        $inst2 = Institution::create([
            'name' => 'Liceo Bicentenario Santa María',
            'code' => 'LBSM-002',
            'address' => 'Calle Las Heras 890, Concepción',
            'phone' => '+56 41 278 9900',
            'email' => 'admision@santamaria.edu',
            'logo_color' => '#059669', // Emerald Green
        ]);

        // Vincular docente a ambas instituciones
        $teacher->institutions()->attach([
            $inst1->id => ['role' => 'teacher'],
            $inst2->id => ['role' => 'teacher'],
        ]);

        // --- DATOS COLEGIO SAN AGUSTÍN ---
        $course1 = Course::create([
            'institution_id' => $inst1->id,
            'name' => '1º Medio',
            'section' => 'A',
            'academic_year' => '2026',
        ]);

        $course2 = Course::create([
            'institution_id' => $inst1->id,
            'name' => '3º Medio',
            'section' => 'B',
            'academic_year' => '2026',
        ]);

        $sub1_inst1 = Subject::create([
            'institution_id' => $inst1->id,
            'course_id' => $course1->id,
            'name' => 'Matemáticas I',
            'code' => 'MAT-101',
            'teacher_name' => $teacher->name,
        ]);

        $sub2_inst1 = Subject::create([
            'institution_id' => $inst1->id,
            'course_id' => $course1->id,
            'name' => 'Física General',
            'code' => 'FIS-101',
            'teacher_name' => $teacher->name,
        ]);

        // Alumnos de 1º Medio A (Colegio San Agustín)
        $students_inst1 = [
            ['rut_dni' => '21.450.981-2', 'first_name' => 'Sofía', 'last_name' => 'Valenzuela', 'email' => 'sofia.valenzuela@sanagustin.edu', 'guardian_name' => 'Roberto Valenzuela', 'guardian_phone' => '+56 9 8812 3456'],
            ['rut_dni' => '21.782.114-K', 'first_name' => 'Mateo', 'last_name' => 'Fernández', 'email' => 'mateo.fernandez@sanagustin.edu', 'guardian_name' => 'Elena Fernández', 'guardian_phone' => '+56 9 7723 4567'],
            ['rut_dni' => '21.903.441-5', 'first_name' => 'Camila', 'last_name' => 'Rojas', 'email' => 'camila.rojas@sanagustin.edu', 'guardian_name' => 'Jorge Rojas', 'guardian_phone' => '+56 9 6634 5678'],
            ['rut_dni' => '22.012.339-8', 'first_name' => 'Lucas', 'last_name' => 'Silva', 'email' => 'lucas.silva@sanagustin.edu', 'guardian_name' => 'Patricia Silva', 'guardian_phone' => '+56 9 5545 6789'],
            ['rut_dni' => '22.145.672-0', 'first_name' => 'Valentina', 'last_name' => 'Morales', 'email' => 'valentina.morales@sanagustin.edu', 'guardian_name' => 'Claudio Morales', 'guardian_phone' => '+56 9 4456 7890'],
        ];

        $created_students_inst1 = [];
        foreach ($students_inst1 as $data) {
            $created_students_inst1[] = Student::create(array_merge($data, [
                'institution_id' => $inst1->id,
                'course_id' => $course1->id,
                'birth_date' => '2010-04-15',
                'status' => 'active',
            ]));
        }

        // --- DATOS LICEO BICENTENARIO SANTA MARÍA ---
        $course_inst2 = Course::create([
            'institution_id' => $inst2->id,
            'name' => '2º Medio',
            'section' => 'C',
            'academic_year' => '2026',
        ]);

        $sub1_inst2 = Subject::create([
            'institution_id' => $inst2->id,
            'course_id' => $course_inst2->id,
            'name' => 'Álgebra y Trigonometría',
            'code' => 'ALG-201',
            'teacher_name' => $teacher->name,
        ]);

        $students_inst2 = [
            ['rut_dni' => '20.891.223-1', 'first_name' => 'Joaquín', 'last_name' => 'Navarro', 'email' => 'joaquin.navarro@santamaria.edu', 'guardian_name' => 'Marta Navarro', 'guardian_phone' => '+56 9 3344 5566'],
            ['rut_dni' => '20.944.551-7', 'first_name' => 'Isabella', 'last_name' => 'Castro', 'email' => 'isabella.castro@santamaria.edu', 'guardian_name' => 'Gonzalo Castro', 'guardian_phone' => '+56 9 2233 4455'],
            ['rut_dni' => '21.055.662-4', 'first_name' => 'Benjamin', 'last_name' => 'Gómez', 'email' => 'benjamin.gomez@santamaria.edu', 'guardian_name' => 'Lucía Gómez', 'guardian_phone' => '+56 9 1122 3344'],
        ];

        foreach ($students_inst2 as $data) {
            Student::create(array_merge($data, [
                'institution_id' => $inst2->id,
                'course_id' => $course_inst2->id,
                'birth_date' => '2009-08-20',
                'status' => 'active',
            ]));
        }

        // --- EVALUACIONES Y CALIFICACIONES (Colegio San Agustín) ---
        $eval1 = Evaluation::create([
            'institution_id' => $inst1->id,
            'subject_id' => $sub1_inst1->id,
            'title' => 'Prueba 1: Ecuaciones de 1er Grado',
            'description' => 'Evaluación individual escrita de álgebra básica',
            'weight_percentage' => 30,
            'date' => '2026-03-25',
        ]);

        $eval2 = Evaluation::create([
            'institution_id' => $inst1->id,
            'subject_id' => $sub1_inst1->id,
            'title' => 'Trabajo Práctico: Geometría Plana',
            'description' => 'Resolución de problemas en grupos de 2 alumnos',
            'weight_percentage' => 20,
            'date' => '2026-04-10',
        ]);

        // Notas de ejemplo (Escala 1.0 a 7.0)
        $scores1 = [6.8, 5.5, 6.2, 4.0, 7.0];
        $scores2 = [7.0, 6.0, 6.5, 5.2, 6.8];

        foreach ($created_students_inst1 as $index => $student) {
            Grade::create([
                'evaluation_id' => $eval1->id,
                'student_id' => $student->id,
                'score' => $scores1[$index],
                'feedback' => 'Buen desempeño en el desarrollo de ejercicios',
            ]);

            Grade::create([
                'evaluation_id' => $eval2->id,
                'student_id' => $student->id,
                'score' => $scores2[$index],
                'feedback' => 'Excelente presentación del trabajo práctico',
            ]);
        }

        // --- REGISTROS DE ASISTENCIA (Últimos días) ---
        $dates = ['2026-10-06', '2026-10-07', '2026-10-08', '2026-10-09', '2026-10-10'];
        $statuses = ['present', 'present', 'present', 'late', 'absent'];

        foreach ($created_students_inst1 as $s_idx => $student) {
            foreach ($dates as $d_idx => $date) {
                // variar levemente los estados
                $st = ($s_idx + $d_idx) % 5 === 0 ? 'late' : (($s_idx == 3 && $d_idx == 4) ? 'absent' : 'present');
                Attendance::create([
                    'institution_id' => $inst1->id,
                    'student_id' => $student->id,
                    'course_id' => $course1->id,
                    'subject_id' => $sub1_inst1->id,
                    'date' => $date,
                    'status' => $st,
                    'notes' => $st === 'late' ? 'Llegó 10 min atrasado por transporte' : null,
                ]);
            }
        }
    }
}
