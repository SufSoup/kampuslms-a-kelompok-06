<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_enroll_a_student_in_a_course(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.courses.enrollments.store', $course),
            ['student_id' => $student->id]
        );

        $response->assertRedirect(route('admin.courses.show', $course));
        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
        $this->assertNotNull(
            DB::table('course_user')
                ->where('course_id', $course->id)
                ->where('user_id', $student->id)
                ->value('enrolled_at')
        );
    }

    public function test_admin_course_detail_lists_enrolled_and_available_students(): void
    {
        $admin = User::factory()->admin()->create();
        $enrolledStudent = User::factory()->mahasiswa()->create(['name' => 'Mahasiswa Terdaftar']);
        $availableStudent = User::factory()->mahasiswa()->create(['name' => 'Mahasiswa Belum Terdaftar']);
        $course = Course::factory()->create();
        $course->students()->attach($enrolledStudent->id, ['enrolled_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.courses.show', $course));

        $response->assertOk()
            ->assertSee($enrolledStudent->email)
            ->assertSee('Daftarkan mahasiswa')
            ->assertViewHas(
                'availableStudents',
                fn ($students) => $students->contains('id', $availableStudent->id)
                    && ! $students->contains('id', $enrolledStudent->id)
            );
    }

    public function test_admin_cannot_enroll_non_students_or_duplicate_students(): void
    {
        $admin = User::factory()->admin()->create();
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $this->actingAs($admin)
            ->post(route('admin.courses.enrollments.store', $course), ['student_id' => $lecturer->id])
            ->assertSessionHasErrors('student_id');

        $this->post(route('admin.courses.enrollments.store', $course), ['student_id' => $student->id])
            ->assertSessionHasErrors('student_id');

        $this->assertDatabaseCount('course_user', 1);
    }

    public function test_only_admin_can_manage_enrollments(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();

        $this->actingAs($lecturer)
            ->post(route('admin.courses.enrollments.store', $course), ['student_id' => $student->id])
            ->assertForbidden();

        $this->assertDatabaseMissing('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_admin_can_remove_an_enrolled_student(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $response = $this->actingAs($admin)->delete(
            route('admin.courses.enrollments.destroy', [$course, $student])
        );

        $response->assertRedirect(route('admin.courses.show', $course));
        $this->assertDatabaseMissing('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }
}
