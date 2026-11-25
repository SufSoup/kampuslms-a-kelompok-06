<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_course(): void
    {
        $admin = User::factory()->admin()->create();
        $lecturer = User::factory()->dosen()->create();

        $response = $this->actingAs($admin)->post(route('admin.courses.store'), [
            'code' => 'SI2514999',
            'name' => 'Mata Kuliah Baru',
            'description' => 'Deskripsi mata kuliah baru.',
            'sks' => 3,
            'lecturer_id' => $lecturer->id,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.courses.index'));

        $this->assertDatabaseHas('courses', [
            'code' => 'SI2514999',
            'name' => 'Mata Kuliah Baru',
            'lecturer_id' => $lecturer->id,
        ]);
    }

    public function test_admin_can_update_a_course(): void
    {
        $admin = User::factory()->admin()->create();
        $oldLecturer = User::factory()->dosen()->create();
        $newLecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create([
            'code' => 'SI2514001',
            'name' => 'Mata Kuliah Lama',
            'lecturer_id' => $oldLecturer->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.courses.update', $course), [
            'code' => 'SI2514002',
            'name' => 'Mata Kuliah Diperbarui',
            'description' => 'Deskripsi mata kuliah yang diperbarui.',
            'sks' => 4,
            'lecturer_id' => $newLecturer->id,
            'status' => 'archived',
        ]);

        $response->assertRedirect(route('admin.courses.show', $course));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'code' => 'SI2514002',
            'name' => 'Mata Kuliah Diperbarui',
            'sks' => 4,
            'lecturer_id' => $newLecturer->id,
            'status' => 'archived',
        ]);
    }

    public function test_course_code_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        $lecturer = User::factory()->dosen()->create();
        Course::factory()->create(['code' => 'SI2514100']);

        $response = $this->actingAs($admin)->post(route('admin.courses.store'), [
            'code' => 'SI2514100',
            'name' => 'Mata Kuliah Duplikat',
            'description' => 'Kode mata kuliah sudah dipakai.',
            'sks' => 3,
            'lecturer_id' => $lecturer->id,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('courses', ['name' => 'Mata Kuliah Duplikat']);
    }

    public function test_course_sks_must_be_within_the_allowed_range(): void
    {
        $admin = User::factory()->admin()->create();
        $lecturer = User::factory()->dosen()->create();

        $response = $this->actingAs($admin)->post(route('admin.courses.store'), [
            'code' => 'SI2514101',
            'name' => 'Mata Kuliah SKS Tidak Valid',
            'description' => 'SKS di luar rentang yang diizinkan.',
            'sks' => 7,
            'lecturer_id' => $lecturer->id,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('sks');

        $this->assertDatabaseMissing('courses', ['name' => 'Mata Kuliah SKS Tidak Valid']);
    }

    public function test_course_lecturer_must_exist(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.courses.store'), [
            'code' => 'SI2514102',
            'name' => 'Mata Kuliah Dosen Tidak Ada',
            'description' => 'Dosen dengan ID tersebut tidak ditemukan.',
            'sks' => 3,
            'lecturer_id' => 999999,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('lecturer_id');

        $this->assertDatabaseMissing('courses', ['name' => 'Mata Kuliah Dosen Tidak Ada']);
    }
}
