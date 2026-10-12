<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Evaluation;
use App\Models\Institution;
use App\Models\Subject;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAndRolesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'Colegio San Agustín',
            'code' => 'CSA-01',
            'logo_color' => '#4f46e5',
        ]);

        $this->admin = User::create([
            'name' => 'Admin General',
            'email' => 'admin@escuela.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);
        $this->admin->institutions()->attach($this->institution->id, ['role' => 'admin']);

        $this->teacher = User::create([
            'name' => 'Prof. Carlos Mendoza',
            'email' => 'carlos@escuela.test',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
        ]);
        $this->teacher->institutions()->attach($this->institution->id, ['role' => 'teacher']);
    }

    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_can_login_with_valid_credentials(): void
    {
        $response = $this->post(route('login.post'), [
            'email' => 'admin@escuela.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->post(route('login.post'), [
            'email' => 'admin@escuela.test',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_can_logout(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('logout'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_user_with_2fa_is_redirected_to_2fa_challenge(): void
    {
        $service = app(TwoFactorAuthService::class);
        $secret = $service->generateSecretKey();

        $this->teacher->update([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['TEST-12345'],
        ]);

        $response = $this->post(route('login.post'), [
            'email' => 'carlos@escuela.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('2fa.challenge'));
        $this->assertGuest();
        $this->assertEquals($this->teacher->id, session('auth.2fa.user_id'));
    }

    public function test_2fa_challenge_validates_totp_code_and_logs_in(): void
    {
        $service = app(TwoFactorAuthService::class);
        $secret = $service->generateSecretKey();
        $otp = $service->getOtp($secret);

        $this->teacher->update([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['TEST-12345'],
        ]);

        session(['auth.2fa.user_id' => $this->teacher->id]);

        $response = $this->post(route('2fa.verify'), [
            'code' => $otp,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->teacher);
    }

    public function test_2fa_challenge_accepts_and_consumes_recovery_code(): void
    {
        $this->teacher->update([
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['RECOV-CODE1', 'RECOV-CODE2'],
        ]);

        session(['auth.2fa.user_id' => $this->teacher->id]);

        $response = $this->post(route('2fa.verify'), [
            'code' => 'RECOV-CODE1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->teacher);

        $this->teacher->refresh();
        $this->assertNotContains('RECOV-CODE1', $this->teacher->two_factor_recovery_codes);
        $this->assertContains('RECOV-CODE2', $this->teacher->two_factor_recovery_codes);
    }

    public function test_admin_can_access_institutions_and_teachers(): void
    {
        $this->actingAs($this->admin);

        $resInst = $this->get(route('institutions.index'));
        $resInst->assertStatus(200);

        $resTeach = $this->get(route('teachers.index'));
        $resTeach->assertStatus(200);
    }

    public function test_teacher_is_forbidden_from_admin_modules(): void
    {
        $this->actingAs($this->teacher);

        $resInst = $this->get(route('institutions.index'));
        $resInst->assertStatus(403);

        $resTeach = $this->get(route('teachers.index'));
        $resTeach->assertStatus(403);
    }

    public function test_teacher_only_sees_their_courses_and_subjects(): void
    {
        session(['active_institution_id' => $this->institution->id]);

        $course1 = Course::create([
            'institution_id' => $this->institution->id,
            'name' => '1º Medio A',
            'academic_year' => '2026',
        ]);

        $course2 = Course::create([
            'institution_id' => $this->institution->id,
            'name' => '4º Medio B',
            'academic_year' => '2026',
        ]);

        // Materia de Carlos (teacher)
        Subject::create([
            'institution_id' => $this->institution->id,
            'course_id' => $course1->id,
            'teacher_id' => $this->teacher->id,
            'name' => 'Matemáticas Avanzadas',
            'teacher_name' => $this->teacher->name,
        ]);

        // Materia de otro profesor
        Subject::create([
            'institution_id' => $this->institution->id,
            'course_id' => $course2->id,
            'teacher_id' => null,
            'name' => 'Filosofía Contemporánea',
            'teacher_name' => 'Prof. Otro',
        ]);

        $this->actingAs($this->teacher);

        $response = $this->get(route('courses.index'));
        $response->assertStatus(200);
        $response->assertSee('Matemáticas Avanzadas');
        $response->assertDontSee('Filosofía Contemporánea');
    }

    public function test_teacher_cannot_grade_another_teachers_subject(): void
    {
        session(['active_institution_id' => $this->institution->id]);

        $course = Course::create([
            'institution_id' => $this->institution->id,
            'name' => 'Curso Prueba',
            'academic_year' => '2026',
        ]);

        $otherSubject = Subject::create([
            'institution_id' => $this->institution->id,
            'course_id' => $course->id,
            'teacher_id' => $this->admin->id,
            'name' => 'Química Orgánica',
            'teacher_name' => 'Admin',
        ]);

        $eval = Evaluation::create([
            'institution_id' => $this->institution->id,
            'subject_id' => $otherSubject->id,
            'title' => 'Prueba de Laboratorio',
            'weight_percentage' => 30,
            'date' => '2026-05-10',
        ]);

        $this->actingAs($this->teacher);

        $response = $this->post(route('grades.storeGrades'), [
            'evaluation_id' => $eval->id,
            'scores' => [1 => 7.0],
        ]);

        $response->assertStatus(403);
    }
}
