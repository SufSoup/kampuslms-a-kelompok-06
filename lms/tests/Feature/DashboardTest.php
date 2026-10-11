<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_live_statistics_and_quick_actions(): void
    {
        $admin = User::factory()->admin()->create();
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        Material::factory()->create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
        ]);
        Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pengguna')
            ->assertSee('Mata Kuliah')
            ->assertSee('Materi')
            ->assertSee('Tugas')
            ->assertSee('Kelola Mata Kuliah')
            ->assertSee('Kelola Pengguna')
            ->assertSee('3', false);

        $this->assertDatabaseHas('users', ['id' => $student->id]);
    }

    public function test_lecturer_dashboard_lists_only_courses_they_teach(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create([
            'name' => 'Kelas Dosen Sendiri',
            'lecturer_id' => $lecturer->id,
        ]);
        Course::factory()->create([
            'name' => 'Kelas Dosen Lain',
            'lecturer_id' => $otherLecturer->id,
        ]);

        $this->actingAs($lecturer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kelas Dosen Sendiri')
            ->assertDontSee('Kelas Dosen Lain')
            ->assertSee(route('dosen.courses.show', $course), false);
    }

    public function test_student_dashboard_shows_enrolled_courses_and_only_active_assignments(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['name' => 'Kelas Terdaftar']);
        $otherCourse = Course::factory()->create(['name' => 'Kelas Tidak Terdaftar']);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $active = Assignment::factory()->active()->create([
            'course_id' => $course->id,
            'title' => 'Tugas Belum Tenggat',
        ]);
        Assignment::factory()->pastDue()->create([
            'course_id' => $course->id,
            'title' => 'Tugas Lewat Tenggat',
        ]);
        Assignment::factory()->draft()->create([
            'course_id' => $course->id,
            'title' => 'Tugas Draft',
        ]);
        Assignment::factory()->active()->create([
            'course_id' => $otherCourse->id,
            'title' => 'Tugas Kelas Lain',
        ]);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kelas Terdaftar')
            ->assertDontSee('Kelas Tidak Terdaftar')
            ->assertSee('Tugas Belum Tenggat')
            ->assertSee(route('mahasiswa.assignments.show', $active), false)
            ->assertDontSee('Tugas Lewat Tenggat')
            ->assertDontSee('Tugas Draft')
            ->assertDontSee('Tugas Kelas Lain');
    }
}
