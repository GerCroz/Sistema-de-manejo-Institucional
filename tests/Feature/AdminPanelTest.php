<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Tester',
            'email' => 'admin.test@escuela.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $this->actingAs($this->admin);
    }

    public function test_can_render_teachers_index_and_create_teacher(): void
    {
        $institution = Institution::create([
            'name' => 'Colegio San Martín',
            'code' => 'CSM',
            'logo_color' => '#4f46e5',
        ]);

        $response = $this->get(route('teachers.index'));
        $response->assertStatus(200);
        $response->assertSee('Cuerpo Docente y Maestros');

        $postResponse = $this->post(route('teachers.store'), [
            'name' => 'Prof. Juan Pérez',
            'email' => 'juan.perez@escuela.test',
            'title' => 'Ingeniero en Sistemas',
            'role' => 'teacher',
            'institution_ids' => [$institution->id],
        ]);

        $postResponse->assertRedirect(route('teachers.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'juan.perez@escuela.test',
            'name' => 'Prof. Juan Pérez',
        ]);

        $teacher = User::where('email', 'juan.perez@escuela.test')->first();
        $this->assertTrue($teacher->institutions->contains($institution));
    }

    public function test_can_create_course_and_add_subject(): void
    {
        $institution = Institution::create([
            'name' => 'Instituto Tecnológico Central',
            'code' => 'ITC',
            'logo_color' => '#10b981',
        ]);

        session(['active_institution_id' => $institution->id]);

        $postCourse = $this->post(route('courses.store'), [
            'name' => '5to Año Secundaria',
            'section' => 'A',
            'academic_year' => '2026',
        ]);

        $postCourse->assertRedirect(route('courses.index'));
        $this->assertDatabaseHas('courses', [
            'name' => '5to Año Secundaria',
            'section' => 'A',
            'institution_id' => $institution->id,
        ]);

        $course = Course::where('name', '5to Año Secundaria')->first();

        $postSubject = $this->post(route('courses.subjects.store', $course->id), [
            'name' => 'Sistemas Operativos',
            'code' => 'SO-2026',
            'teacher_name' => 'Ing. Carlos Mendoza',
        ]);

        $postSubject->assertRedirect(route('courses.index'));
        $this->assertDatabaseHas('subjects', [
            'name' => 'Sistemas Operativos',
            'course_id' => $course->id,
            'institution_id' => $institution->id,
        ]);
    }

    public function test_can_edit_student(): void
    {
        $institution = Institution::create([
            'name' => 'Colegio Modelo',
            'code' => 'CM',
            'logo_color' => '#6366f1',
        ]);

        $course = Course::create([
            'institution_id' => $institution->id,
            'name' => '3er Año',
            'section' => 'B',
            'academic_year' => '2026',
        ]);

        $student = Student::create([
            'institution_id' => $institution->id,
            'course_id' => $course->id,
            'legajo' => '5012',
            'rut_dni' => '22.333.444-5',
            'first_name' => 'Martina',
            'last_name' => 'Gómez',
            'status' => 'active',
        ]);

        $editView = $this->get(route('students.edit', $student->id));
        $editView->assertStatus(200);
        $editView->assertSee('Martina Gómez');

        $updateResponse = $this->put(route('students.update', $student->id), [
            'course_id' => $course->id,
            'legajo' => '5012',
            'rut_dni' => '22.333.444-5',
            'first_name' => 'Martina Sofía',
            'last_name' => 'Gómez',
            'email' => 'martina@escuela.test',
            'status' => 'active',
        ]);

        $updateResponse->assertRedirect(route('students.index'));
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'first_name' => 'Martina Sofía',
            'email' => 'martina@escuela.test',
        ]);
    }
}
