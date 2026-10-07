<?php

namespace Tests\Feature\Api;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_can_get_assignments_from_their_course(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        Assignment::factory()->count(2)->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);

        $response = $this->actingAs($lecturer, 'sanctum')
            ->getJson("/api/v1/courses/{$course->id}/assignments");

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_lecturer_cannot_get_assignments_from_another_lecturers_course(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();

        $course = Course::factory()->create([
            'lecturer_id' => $otherLecturer->id,
        ]);

        $response = $this->actingAs($lecturer, 'sanctum')
            ->getJson("/api/v1/courses/{$course->id}/assignments");

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);
    }

    public function test_lecturer_can_create_assignment_for_their_course(): void
    {
        $lecturer = User::factory()->dosen()->create();

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $response = $this->actingAs($lecturer, 'sanctum')
            ->postJson('/api/v1/assignments', [
                'course_id' => $course->id,
                'title' => 'Tugas API',
                'instructions' => 'Kerjakan tugas API.',
                'due_at' => now()->addDays(7)->toDateTimeString(),
                'max_score' => 100,
                'allow_late' => true,
                'status' => 'published',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.course_id', $course->id)
            ->assertJsonPath('data.created_by', $lecturer->id)
            ->assertJsonPath('data.title', 'Tugas API');

        $this->assertDatabaseHas('assignments', [
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas API',
        ]);
    }

    public function test_lecturer_cannot_update_another_lecturers_assignment(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();

        $course = Course::factory()->create([
            'lecturer_id' => $otherLecturer->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $otherLecturer->id,
        ]);

        $response = $this->actingAs($lecturer, 'sanctum')
            ->patchJson("/api/v1/assignments/{$assignment->id}", [
                'title' => 'Percobaan IDOR',
            ]);

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);
    }

    public function test_lecturer_can_update_their_own_assignment(): void
    {
        $lecturer = User::factory()->dosen()->create();

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);

        $response = $this->actingAs($lecturer, 'sanctum')
            ->patchJson("/api/v1/assignments/{$assignment->id}", [
                'title' => 'Judul Diperbarui',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.title', 'Judul Diperbarui');
    }

    public function test_lecturer_can_delete_their_own_assignment(): void
    {
        $lecturer = User::factory()->dosen()->create();

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);

        $response = $this->actingAs($lecturer, 'sanctum')
            ->deleteJson("/api/v1/assignments/{$assignment->id}");

       $response->assertNoContent();

        $this->assertDatabaseMissing('assignments', [
            'id' => $assignment->id,
        ]);
    }
}