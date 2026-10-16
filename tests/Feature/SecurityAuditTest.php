<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_modifying_student_records_activity_with_authenticated_causer(): void
    {
        $admin = User::create([
            'name' => 'Admin Auditor',
            'email' => 'admin@audit.cl',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $institution = Institution::create([
            'name' => 'Colegio San Martin',
            'code' => 'CSM01',
            'address' => 'Av. Central 123',
            'phone' => '+56911112222',
            'email' => 'admin@csm.cl',
            'is_active' => true,
        ]);

        $course = Course::create([
            'institution_id' => $institution->id,
            'name' => '1ro Medio',
            'section' => 'A',
            'academic_year' => 2026,
        ]);

        $student = Student::create([
            'institution_id' => $institution->id,
            'course_id' => $course->id,
            'legajo' => 'L-101',
            'rut_dni' => '12.345.678-9',
            'first_name' => 'Carlos',
            'last_name' => 'Gomez',
            'email' => 'carlos@estudiante.cl',
            'status' => 'active',
        ]);

        $this->actingAs($admin);

        $student->update([
            'first_name' => 'Carlos Alberto',
            'status' => 'inactive',
        ]);

        $lastActivity = Activity::forSubject($student)->orderByDesc('id')->first();

        $this->assertNotNull($lastActivity);
        $this->assertEquals('updated', $lastActivity->event);
        $this->assertEquals($admin->id, $lastActivity->causer_id);
        $this->assertEquals('Carlos Alberto', $lastActivity->properties['attributes']['first_name']);
        $this->assertEquals('Carlos', $lastActivity->properties['old']['first_name']);
    }

    public function test_grade_modifications_are_strictly_tracked(): void
    {
        $teacher = User::create([
            'name' => 'Profesor Matematicas',
            'email' => 'teacher@school.test',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
        ]);

        $institution = Institution::create([
            'name' => 'Colegio San Martin',
            'code' => 'CSM01',
            'is_active' => true,
        ]);

        $course = Course::create([
            'institution_id' => $institution->id,
            'name' => '1ro Medio',
            'section' => 'A',
            'academic_year' => 2026,
        ]);

        $student = Student::create([
            'institution_id' => $institution->id,
            'course_id' => $course->id,
            'legajo' => 'L-102',
            'first_name' => 'Maria',
            'last_name' => 'Lopez',
        ]);

        $subject = Subject::create([
            'institution_id' => $institution->id,
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'Matemáticas',
            'code' => 'MAT-1',
        ]);

        $evaluation = Evaluation::create([
            'institution_id' => $institution->id,
            'subject_id' => $subject->id,
            'title' => 'Prueba 1',
            'weight_percentage' => 30,
            'date' => '2026-04-10',
        ]);

        $this->actingAs($teacher);

        $grade = Grade::create([
            'evaluation_id' => $evaluation->id,
            'student_id' => $student->id,
            'score' => 4.5,
            'feedback' => 'Regular',
        ]);

        $createdActivity = Activity::forSubject($grade)->where('event', 'created')->first();
        $this->assertNotNull($createdActivity);
        $this->assertEquals($teacher->id, $createdActivity->causer_id);

        // Teacher updates the grade
        $grade->update([
            'score' => 6.8,
            'feedback' => 'Corrección aprobada',
        ]);

        $updatedActivity = Activity::forSubject($grade)->where('event', 'updated')->first();
        $this->assertNotNull($updatedActivity);
        $this->assertEquals(4.5, $updatedActivity->properties['old']['score']);
        $this->assertEquals(6.8, $updatedActivity->properties['attributes']['score']);
    }

    public function test_activity_observer_dispatches_events_to_audit_log_channel(): void
    {
        $channelSpy = \Mockery::spy(LoggerInterface::class);
        Log::shouldReceive('channel')
            ->with('audit')
            ->andReturn($channelSpy);

        $admin = User::create([
            'name' => 'Admin Canal Log',
            'email' => 'admin-log@audit.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        Institution::create([
            'name' => 'Colegio Audit Log',
            'code' => 'AUD01',
            'is_active' => true,
        ]);

        $channelSpy->shouldHaveReceived('info')
            ->atLeast()->once();
    }

    public function test_user_sensitive_credentials_are_not_logged(): void
    {
        $user = User::create([
            'name' => 'Usuario Test',
            'email' => 'test@school.com',
            'password' => bcrypt('SecretPassword123!'),
            'role' => 'teacher',
        ]);

        $user->update([
            'name' => 'Usuario Renombrado',
            'password' => bcrypt('NewSecretPassword999!'),
            'two_factor_secret' => 'SECRET_2FA_TOKEN',
        ]);

        $activity = Activity::forSubject($user)->latest()->first();
        $this->assertNotNull($activity);

        // Name should be tracked
        $this->assertArrayHasKey('name', $activity->properties['attributes']);

        // Password and 2FA secret MUST NOT be tracked in activity properties
        $this->assertArrayNotHasKey('password', $activity->properties['attributes'] ?? []);
        $this->assertArrayNotHasKey('two_factor_secret', $activity->properties['attributes'] ?? []);
    }
}
