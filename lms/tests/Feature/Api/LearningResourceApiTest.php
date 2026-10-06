<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\LearningResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningResourceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_can_curate_learning_resource_for_their_course(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $payload = [
            'title' => 'Dokumentasi Resmi Laravel 12',
            'description' => 'Panduan instalasi dan fitur baru Laravel 12.',
            'type' => 'documentation',
            'url' => 'https://laravel.com/docs/12.x',
            'source_platform' => 'Laravel Docs',
            'is_recommended' => true,
        ];

        $response = $this->actingAs($lecturer, 'sanctum')
            ->postJson("/api/v1/courses/{$course->id}/resources", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Dokumentasi Resmi Laravel 12')
            ->assertJsonPath('data.type', 'documentation')
            ->assertJsonPath('data.is_recommended', true);

        $this->assertDatabaseHas('learning_resources', [
            'course_id' => $course->id,
            'added_by' => $lecturer->id,
            'title' => 'Dokumentasi Resmi Laravel 12',
        ]);
    }

    public function test_lecturer_cannot_curate_resource_for_course_they_do_not_teach(): void
    {
        $lecturerA = User::factory()->dosen()->create();
        $lecturerB = User::factory()->dosen()->create();
        $courseB = Course::factory()->create(['lecturer_id' => $lecturerB->id]);

        $payload = [
            'title' => 'Upaya Bypass IDOR',
            'type' => 'article',
            'url' => 'https://example.com/exploit',
        ];

        // Dosen A mencoba menambahkan resource pada mata kuliah Dosen B
        $response = $this->actingAs($lecturerA, 'sanctum')
            ->postJson("/api/v1/courses/{$courseB->id}/resources", $payload);

        $response->assertStatus(403);
    }

    public function test_student_cannot_curate_learning_resource(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $payload = [
            'title' => 'Resource dari mahasiswa',
            'type' => 'article',
            'url' => 'https://example.com/student',
        ];

        $response = $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/courses/{$course->id}/resources", $payload);

        $response->assertStatus(403);
    }

    public function test_enrolled_student_can_view_resources_of_course(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $resource = LearningResource::factory()->create([
            'course_id' => $course->id,
            'title' => 'Tutorial Video Web',
        ]);

        $response = $this->actingAs($student, 'sanctum')
            ->getJson("/api/v1/courses/{$course->id}/resources");

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'Tutorial Video Web')
            ->assertJsonPath('data.0.is_read', false);
    }

    public function test_unenrolled_student_cannot_view_resources(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        // Mahasiswa TIDAK di-enroll ke dalam course

        LearningResource::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($student, 'sanctum')
            ->getJson("/api/v1/courses/{$course->id}/resources");

        $response->assertStatus(403);
    }

    public function test_enrolled_student_can_toggle_read_status(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $resource = LearningResource::factory()->create(['course_id' => $course->id]);

        // Toggle pertama: Tandai sudah dibaca
        $response1 = $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/resources/{$resource->id}/toggle-read");

        $response1->assertOk()
            ->assertJson([
                'is_read' => true,
                'message' => 'Status sumber belajar ditandai sudah dibaca.',
            ]);

        $this->assertDatabaseHas('learning_resource_reads', [
            'learning_resource_id' => $resource->id,
            'user_id' => $student->id,
        ]);

        // Toggle kedua: Tandai belum dibaca
        $response2 = $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/resources/{$resource->id}/toggle-read");

        $response2->assertOk()
            ->assertJson([
                'is_read' => false,
                'message' => 'Status sumber belajar ditandai belum dibaca.',
            ]);

        $this->assertDatabaseMissing('learning_resource_reads', [
            'learning_resource_id' => $resource->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_unenrolled_student_cannot_toggle_read_status(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        // Mahasiswa tidak terdaftar
        $resource = LearningResource::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/resources/{$resource->id}/toggle-read");

        $response->assertStatus(403);
    }

    public function test_student_read_status_is_isolated_per_user_anti_idor(): void
    {
        $studentA = User::factory()->mahasiswa()->create();
        $studentB = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create();
        $course->students()->attach([
            $studentA->id => ['enrolled_at' => now()],
            $studentB->id => ['enrolled_at' => now()],
        ]);

        $resource = LearningResource::factory()->create(['course_id' => $course->id]);

        // Mahasiswa A menandai sudah dibaca
        $this->actingAs($studentA, 'sanctum')
            ->postJson("/api/v1/resources/{$resource->id}/toggle-read")
            ->assertOk();

        // Mahasiswa B memeriksa resource yang sama: is_read harus tetap false
        $responseB = $this->actingAs($studentB, 'sanctum')
            ->getJson("/api/v1/courses/{$course->id}/resources");

        $responseB->assertOk()
            ->assertJsonPath('data.0.is_read', false);

        // Mahasiswa A memeriksa: is_read harus true
        $responseA = $this->actingAs($studentA, 'sanctum')
            ->getJson("/api/v1/courses/{$course->id}/resources");

        $responseA->assertOk()
            ->assertJsonPath('data.0.is_read', true);
    }
}
