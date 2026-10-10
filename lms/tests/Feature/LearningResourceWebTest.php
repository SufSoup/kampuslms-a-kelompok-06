<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearningResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningResourceWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_can_view_create_resource_page_for_their_course(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $response = $this->actingAs($lecturer)->get(route('learning-resources.create', $course));

        $response->assertOk();
        $response->assertSee('Tambah Sumber Belajar');
    }

    public function test_lecturer_cannot_view_create_resource_page_for_another_course(): void
    {
        $lecturerA = User::factory()->dosen()->create();
        $lecturerB = User::factory()->dosen()->create();
        $courseB = Course::factory()->create(['lecturer_id' => $lecturerB->id]);

        $response = $this->actingAs($lecturerA)->get(route('learning-resources.create', $courseB));

        $response->assertForbidden();
    }

    public function test_lecturer_can_store_learning_resource(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $response = $this->actingAs($lecturer)->post(route('learning-resources.store', $course), [
            'title' => 'Dokumentasi Laravel Blade',
            'type' => 'documentation',
            'url' => 'https://laravel.com/docs/blade',
            'source_platform' => 'Laravel Docs',
            'description' => 'Panduan lengkap Blade templating engine',
            'is_recommended' => true,
        ]);

        $response->assertRedirect(route('dosen.courses.show', $course));

        $this->assertDatabaseHas('learning_resources', [
            'course_id' => $course->id,
            'title' => 'Dokumentasi Laravel Blade',
            'type' => 'documentation',
            'added_by' => $lecturer->id,
            'is_recommended' => true,
        ]);
    }

    public function test_enrolled_student_can_toggle_read_status(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $resource = LearningResource::factory()->create([
            'course_id' => $course->id,
            'added_by' => $lecturer->id,
        ]);

        // Toggle 1: Mark as read
        $response = $this->actingAs($student)->post(route('learning-resources.toggle-read', $resource));

        $response->assertRedirect();
        $this->assertDatabaseHas('learning_resource_reads', [
            'learning_resource_id' => $resource->id,
            'user_id' => $student->id,
        ]);

        // Toggle 2: Unmark as read
        $response2 = $this->actingAs($student)->post(route('learning-resources.toggle-read', $resource));

        $response2->assertRedirect();
        $this->assertDatabaseMissing('learning_resource_reads', [
            'learning_resource_id' => $resource->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_unenrolled_student_cannot_toggle_read_status_anti_idor(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        // Mahasiswa TIDAK di-enroll ke course ini

        $resource = LearningResource::factory()->create([
            'course_id' => $course->id,
            'added_by' => $lecturer->id,
        ]);

        $response = $this->actingAs($student)->post(route('learning-resources.toggle-read', $resource));

        $response->assertForbidden();
        $this->assertDatabaseMissing('learning_resource_reads', [
            'learning_resource_id' => $resource->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_lecturer_can_update_their_learning_resource(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $resource = LearningResource::factory()->create([
            'course_id' => $course->id,
            'added_by' => $lecturer->id,
            'title' => 'Judul Lama',
        ]);

        $response = $this->actingAs($lecturer)->put(route('learning-resources.update', [$course, $resource]), [
            'title' => 'Judul Baru',
            'type' => 'article',
            'url' => 'https://example.com/new',
            'is_recommended' => false,
        ]);

        $response->assertRedirect(route('dosen.courses.show', $course));
        $this->assertDatabaseHas('learning_resources', [
            'id' => $resource->id,
            'title' => 'Judul Baru',
        ]);
    }

    public function test_lecturer_cannot_update_another_lecturers_learning_resource(): void
    {
        $lecturerA = User::factory()->dosen()->create();
        $lecturerB = User::factory()->dosen()->create();
        $courseB = Course::factory()->create(['lecturer_id' => $lecturerB->id]);
        $resourceB = LearningResource::factory()->create([
            'course_id' => $courseB->id,
            'added_by' => $lecturerB->id,
        ]);

        $response = $this->actingAs($lecturerA)->put(route('learning-resources.update', [$courseB, $resourceB]), [
            'title' => 'Hacked Title',
            'type' => 'article',
            'url' => 'https://hacked.com',
        ]);

        $response->assertForbidden();
    }

    public function test_lecturer_can_delete_their_learning_resource(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $resource = LearningResource::factory()->create([
            'course_id' => $course->id,
            'added_by' => $lecturer->id,
        ]);

        $response = $this->actingAs($lecturer)->delete(route('learning-resources.destroy', [$course, $resource]));

        $response->assertRedirect(route('dosen.courses.show', $course));
        $this->assertDatabaseMissing('learning_resources', [
            'id' => $resource->id,
        ]);
    }

    public function test_lecturer_cannot_delete_another_lecturers_learning_resource(): void
    {
        $lecturerA = User::factory()->dosen()->create();
        $lecturerB = User::factory()->dosen()->create();
        $courseB = Course::factory()->create(['lecturer_id' => $lecturerB->id]);
        $resourceB = LearningResource::factory()->create([
            'course_id' => $courseB->id,
            'added_by' => $lecturerB->id,
        ]);

        $response = $this->actingAs($lecturerA)->delete(route('learning-resources.destroy', [$courseB, $resourceB]));

        $response->assertForbidden();
        $this->assertDatabaseHas('learning_resources', [
            'id' => $resourceB->id,
        ]);
    }
}
