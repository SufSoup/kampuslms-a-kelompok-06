<?php

namespace Tests\Feature\Api;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_only_sees_courses_they_teach_with_counts_and_pagination(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        Course::factory()->create(['lecturer_id' => $otherLecturer->id]);
        Material::factory()->count(2)->create(['course_id' => $course->id]);
        Assignment::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($lecturer, 'sanctum')
            ->getJson('/api/v1/courses');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $course->id)
            ->assertJsonPath('data.0.lecturer.id', $lecturer->id)
            ->assertJsonPath('data.0.counts.materials', 2)
            ->assertJsonPath('data.0.counts.assignments', 1)
            ->assertJsonStructure([
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    public function test_student_only_sees_enrolled_courses(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $enrolledCourse = Course::factory()->create();
        Course::factory()->create();
        $enrolledCourse->students()->attach($student->id, ['enrolled_at' => now()]);

        $response = $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/courses');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $enrolledCourse->id);
    }

    public function test_admin_can_list_all_courses(): void
    {
        $admin = User::factory()->admin()->create();
        Course::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/courses');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_course_detail_includes_lecturer_and_material_and_assignment_counts(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        Material::factory()->create(['course_id' => $course->id]);
        Assignment::factory()->count(2)->create(['course_id' => $course->id]);

        $response = $this->actingAs($lecturer, 'sanctum')
            ->getJson("/api/v1/courses/{$course->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $course->id)
            ->assertJsonPath('data.lecturer.id', $lecturer->id)
            ->assertJsonPath('data.counts.materials', 1)
            ->assertJsonPath('data.counts.assignments', 2);
    }

    public function test_user_cannot_view_another_users_course_detail(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);

        $this->actingAs($lecturer, 'sanctum')
            ->getJson("/api/v1/courses/{$course->id}")
            ->assertForbidden();
    }

    public function test_enrolled_student_can_list_course_materials_without_file_path(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $material = Material::factory()->file()->create([
            'course_id' => $course->id,
            'file_path' => 'private/materials/secret.pdf',
        ]);

        $response = $this->actingAs($student, 'sanctum')
            ->getJson("/api/v1/courses/{$course->id}/materials");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $material->id)
            ->assertJsonPath('data.0.title', $material->title)
            ->assertJsonMissingPath('data.0.file_path')
            ->assertJsonMissing(['private/materials/secret.pdf'])
            ->assertJsonStructure([
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    public function test_unenrolled_student_cannot_list_course_materials(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();

        $this->actingAs($student, 'sanctum')
            ->getJson("/api/v1/courses/{$course->id}/materials")
            ->assertForbidden();
    }

    public function test_lecturer_cannot_list_materials_for_another_lecturers_course(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);

        $this->actingAs($lecturer, 'sanctum')
            ->getJson("/api/v1/courses/{$course->id}/materials")
            ->assertForbidden();
    }
}
