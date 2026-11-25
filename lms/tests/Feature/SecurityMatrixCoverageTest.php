<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityMatrixCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolled_student_can_view_course_detail(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create([
            'code' => 'SI2514024',
            'name' => 'Pemrograman Web Lanjut',
        ]);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $this->actingAs($student)
            ->get(route('mahasiswa.courses.show', $course))
            ->assertOk()
            ->assertSee('Pemrograman Web Lanjut')
            ->assertSee('SI2514024');
    }

    public function test_unenrolled_student_cannot_view_another_course_detail(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $otherCourse = Course::factory()->create([
            'name' => 'Mata Kuliah Rahasia Kelas Sebelah',
        ]);

        $response = $this->actingAs($student)
            ->get(route('mahasiswa.courses.show', $otherCourse));

        $response->assertForbidden()
            ->assertSeeText('Akses Ditolak')
            ->assertDontSee('Mata Kuliah Rahasia Kelas Sebelah');
    }

    public function test_admin_can_access_admin_users_and_courses_management(): void
    {
        $admin = User::factory()->admin()->create();
        $lecturer = User::factory()->dosen()->create(['name' => 'Dosen Terdaftar']);
        Course::factory()->create([
            'name' => 'Arsitektur Web',
            'lecturer_id' => $lecturer->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Dosen Terdaftar');

        $this->actingAs($admin)
            ->get(route('admin.courses.index'))
            ->assertOk()
            ->assertSee('Arsitektur Web');
    }

    public function test_non_admin_cannot_access_admin_users_or_courses_routes(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $targetUser = User::factory()->mahasiswa()->create(['name' => 'Target Asli']);

        foreach ([$lecturer, $student] as $actor) {
            $this->actingAs($actor)->get(route('admin.users.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('admin.courses.index'))->assertForbidden();

            $this->actingAs($actor)->post(route('admin.courses.store'), [
                'code' => 'HACK999',
                'name' => 'MK Ilegal',
                'sks' => 3,
                'lecturer_id' => $lecturer->id,
                'status' => 'active',
            ])->assertForbidden();

            $this->actingAs($actor)->put(route('admin.users.update', $targetUser), [
                'name' => 'Diubah Non Admin',
                'email' => $targetUser->email,
                'role' => 'admin',
            ])->assertForbidden();
        }

        $this->assertDatabaseMissing('courses', ['code' => 'HACK999']);
        $this->assertSame('Target Asli', $targetUser->fresh()->name);
        $this->assertSame('mahasiswa', $targetUser->fresh()->role);
    }

    public function test_user_role_cannot_be_changed_via_mass_assignment(): void
    {
        $student = User::factory()->mahasiswa()->create(['name' => 'Budi Awal']);

        $student->fill([
            'name' => 'Budi Diubah',
            'role' => 'admin',
        ]);
        $student->save();

        $fresh = $student->fresh();
        $this->assertSame('Budi Diubah', $fresh->name);
        $this->assertSame('mahasiswa', $fresh->role);
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'role' => 'mahasiswa',
        ]);
    }
}
