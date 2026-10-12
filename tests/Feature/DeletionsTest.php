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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeletionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private Institution $inst1;

    private Institution $inst2;

    private Course $course;

    private Subject $subject;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inst1 = Institution::create([
            'name' => 'Colegio San Agustín',
            'code' => 'CSA',
            'logo_color' => '#4f46e5',
        ]);

        $this->inst2 = Institution::create([
            'name' => 'Liceo Santa María',
            'code' => 'LSM',
            'logo_color' => '#059669',
        ]);

        $this->admin = User::create([
            'name' => 'Admin Directivo',
            'email' => 'admin.directivo@escuela.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);
        $this->admin->institutions()->attach([$this->inst1->id, $this->inst2->id]);

        $this->teacher = User::create([
            'name' => 'Prof. Carlos Mendoza',
            'email' => 'carlos.mendoza@escuela.test',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
        ]);
        $this->teacher->institutions()->attach($this->inst1->id);

        $this->course = Course::create([
            'institution_id' => $this->inst1->id,
            'name' => '3º Medio A',
            'academic_year' => '2026',
        ]);

        $this->subject = Subject::create([
            'institution_id' => $this->inst1->id,
            'course_id' => $this->course->id,
            'teacher_id' => $this->teacher->id,
            'name' => 'Física Cuántica',
            'teacher_name' => $this->teacher->name,
        ]);

        $this->student = Student::create([
            'institution_id' => $this->inst1->id,
            'course_id' => $this->course->id,
            'first_name' => 'Martina',
            'last_name' => 'Gómez',
            'rut_dni' => '22.333.444-5',
        ]);
    }

    public function test_admin_can_delete_student_and_cascades_records(): void
    {
        // Registrar asistencia y nota de prueba para el alumno
        Attendance::create([
            'institution_id' => $this->inst1->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'date' => '2026-10-12',
            'status' => 'present',
        ]);

        $eval = Evaluation::create([
            'institution_id' => $this->inst1->id,
            'subject_id' => $this->subject->id,
            'title' => 'Prueba 1',
            'weight_percentage' => 30,
            'date' => '2026-10-12',
        ]);

        Grade::create([
            'evaluation_id' => $eval->id,
            'student_id' => $this->student->id,
            'score' => 6.5,
        ]);

        $this->actingAs($this->admin);

        $response = $this->delete(route('students.destroy', $this->student->id));
        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('students', ['id' => $this->student->id]);
        $this->assertDatabaseMissing('attendances', ['student_id' => $this->student->id]);
        $this->assertDatabaseMissing('grades', ['student_id' => $this->student->id]);
    }

    public function test_teacher_cannot_delete_student(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->delete(route('students.destroy', $this->student->id));
        $response->assertStatus(403);

        $this->assertDatabaseHas('students', ['id' => $this->student->id]);
    }

    public function test_admin_can_delete_course(): void
    {
        $this->actingAs($this->admin);

        $response = $this->delete(route('courses.destroy', $this->course->id));
        $response->assertRedirect(route('courses.index'));

        $this->assertDatabaseMissing('courses', ['id' => $this->course->id]);
    }

    public function test_teacher_cannot_delete_course(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->delete(route('courses.destroy', $this->course->id));
        $response->assertStatus(403);

        $this->assertDatabaseHas('courses', ['id' => $this->course->id]);
    }

    public function test_admin_can_delete_subject(): void
    {
        $this->actingAs($this->admin);

        $response = $this->delete(route('subjects.destroy', $this->subject->id));
        $response->assertRedirect(route('courses.index'));

        $this->assertDatabaseMissing('subjects', ['id' => $this->subject->id]);
    }

    public function test_admin_can_delete_teacher_safely(): void
    {
        $this->actingAs($this->admin);

        $response = $this->delete(route('teachers.destroy', $this->teacher->id));
        $response->assertRedirect(route('teachers.index'));

        $this->assertDatabaseMissing('users', ['id' => $this->teacher->id]);

        $this->subject->refresh();
        $this->assertNull($this->subject->teacher_id);
    }

    public function test_admin_cannot_delete_own_active_account(): void
    {
        $this->actingAs($this->admin);

        $response = $this->delete(route('teachers.destroy', $this->admin->id));
        $response->assertSessionHasErrors('error');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_admin_can_delete_institution_when_multiple_exist(): void
    {
        $this->actingAs($this->admin);

        $response = $this->delete(route('institutions.destroy', $this->inst2->id));
        $response->assertRedirect(route('institutions.index'));

        $this->assertDatabaseMissing('institutions', ['id' => $this->inst2->id]);
    }

    public function test_admin_cannot_delete_sole_remaining_institution(): void
    {
        $this->inst2->delete();

        $this->actingAs($this->admin);

        $response = $this->delete(route('institutions.destroy', $this->inst1->id));
        $response->assertSessionHasErrors('error');

        $this->assertDatabaseHas('institutions', ['id' => $this->inst1->id]);
    }

    public function test_admin_can_delete_evaluation(): void
    {
        $eval = Evaluation::create([
            'institution_id' => $this->inst1->id,
            'subject_id' => $this->subject->id,
            'title' => 'Parcial 1',
            'weight_percentage' => 30,
            'date' => '2026-10-12',
        ]);

        Grade::create([
            'evaluation_id' => $eval->id,
            'student_id' => $this->student->id,
            'score' => 5.8,
        ]);

        $this->actingAs($this->admin);

        $response = $this->delete(route('grades.destroyEvaluation', $eval->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('evaluations', ['id' => $eval->id]);
        $this->assertDatabaseMissing('grades', ['evaluation_id' => $eval->id]);
    }

    public function test_admin_can_clear_attendance_for_date(): void
    {
        session(['active_institution_id' => $this->inst1->id]);

        Attendance::create([
            'institution_id' => $this->inst1->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'date' => '2026-10-12',
            'status' => 'late',
        ]);

        $this->actingAs($this->admin);

        $response = $this->delete(route('attendance.destroy'), [
            'course_id' => $this->course->id,
            'date' => '2026-10-12',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('attendances', [
            'course_id' => $this->course->id,
            'date' => '2026-10-12',
        ]);
    }
}
