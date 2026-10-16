<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Subject;
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

        // Probar asignación mediante teacher_id
        $teacher = User::create([
            'name' => 'Prof. Laura Gómez',
            'email' => 'laura.gomez@escuela.test',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
        ]);

        $postSubjectWithTeacher = $this->post(route('courses.subjects.store', $course->id), [
            'name' => 'Bases de Datos',
            'code' => 'BD-2026',
            'teacher_id' => $teacher->id,
        ]);

        $postSubjectWithTeacher->assertRedirect(route('courses.index'));
        $this->assertDatabaseHas('subjects', [
            'name' => 'Bases de Datos',
            'teacher_id' => $teacher->id,
            'teacher_name' => 'Prof. Laura Gómez',
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

    public function test_admin_can_update_subject_and_assign_existing_teacher(): void
    {
        $institution = Institution::create([
            'name' => 'Colegio San José',
            'code' => 'CSJ',
        ]);
        session(['active_institution_id' => $institution->id]);

        $course = Course::create([
            'institution_id' => $institution->id,
            'name' => '4to Año',
            'academic_year' => '2026',
        ]);

        $subject = Subject::create([
            'institution_id' => $institution->id,
            'course_id' => $course->id,
            'name' => 'Física Cuántica',
            'code' => 'FIS-01',
            'teacher_name' => 'Docente Titular',
        ]);

        $teacher = User::create([
            'name' => 'Dr. Alberto Einstein',
            'email' => 'alberto@escuela.test',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
        ]);

        $response = $this->put(route('subjects.update', $subject->id), [
            'name' => 'Física Moderna',
            'code' => 'FIS-MOD',
            'teacher_id' => $teacher->id,
        ]);

        $response->assertRedirect(route('courses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'name' => 'Física Moderna',
            'code' => 'FIS-MOD',
            'teacher_id' => $teacher->id,
            'teacher_name' => 'Dr. Alberto Einstein',
        ]);

        // Verifica que el docente se haya vinculado a la institución
        $this->assertTrue($teacher->fresh()->institutions->contains($institution));
    }

    public function test_admin_can_update_subject_and_create_new_teacher_on_the_fly(): void
    {
        $institution = Institution::create([
            'name' => 'Colegio Las Heras',
            'code' => 'CLH',
        ]);
        session(['active_institution_id' => $institution->id]);

        $course = Course::create([
            'institution_id' => $institution->id,
            'name' => '2do Año',
            'academic_year' => '2026',
        ]);

        $subject = Subject::create([
            'institution_id' => $institution->id,
            'course_id' => $course->id,
            'name' => 'Química Orgánica',
            'code' => 'QUI-01',
            'teacher_name' => 'Docente Titular',
        ]);

        $response = $this->put(route('subjects.update', $subject->id), [
            'name' => 'Química Avanzada',
            'code' => 'QUI-AVZ',
            'new_teacher_name' => 'Dra. Marie Curie',
            'new_teacher_email' => 'marie.curie@escuela.test',
            'new_teacher_title' => 'Investigadora y Profesora',
        ]);

        $response->assertRedirect(route('courses.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'marie.curie@escuela.test',
            'name' => 'Dra. Marie Curie',
            'role' => 'teacher',
        ]);

        $newTeacher = User::where('email', 'marie.curie@escuela.test')->first();

        $this->assertDatabaseHas('subjects', [
            'id' => $subject->id,
            'name' => 'Química Avanzada',
            'teacher_id' => $newTeacher->id,
            'teacher_name' => 'Dra. Marie Curie',
        ]);

        $this->assertTrue($newTeacher->institutions->contains($institution));
    }

    public function test_admin_can_quick_create_teacher_via_ajax(): void
    {
        $institution = Institution::create([
            'name' => 'Instituto Politécnico',
            'code' => 'IPOL',
        ]);

        $response = $this->postJson(route('teachers.quick'), [
            'name' => 'Lic. Alan Turing',
            'email' => 'alan.turing@escuela.test',
            'title' => 'Especialista en Computación',
            'institution_id' => $institution->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'teacher' => [
                'name' => 'Lic. Alan Turing',
                'email' => 'alan.turing@escuela.test',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'alan.turing@escuela.test',
            'role' => 'teacher',
        ]);
    }

    public function test_teacher_cannot_update_subject_or_access_quick_teacher(): void
    {
        $teacher = User::create([
            'name' => 'Docente Regular',
            'email' => 'docente.regular@escuela.test',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
        ]);

        $this->actingAs($teacher);

        $institution = Institution::create([
            'name' => 'Colegio Belgrano',
            'code' => 'CBEL',
        ]);

        $course = Course::create([
            'institution_id' => $institution->id,
            'name' => '1er Año',
            'academic_year' => '2026',
        ]);

        $subject = Subject::create([
            'institution_id' => $institution->id,
            'course_id' => $course->id,
            'name' => 'Biología',
            'teacher_id' => $teacher->id,
        ]);

        $updateResponse = $this->put(route('subjects.update', $subject->id), [
            'name' => 'Biología II',
        ]);

        $updateResponse->assertStatus(403);

        $quickResponse = $this->postJson(route('teachers.quick'), [
            'name' => 'Otro Docente',
            'email' => 'otro@escuela.test',
        ]);

        $quickResponse->assertStatus(403);
    }
}
