<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAndPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_can_search_course_by_name_or_code(): void
    {
        $dosen = User::factory()->dosen()->create();

        $courseA = Course::factory()->create([
            'code' => 'IF101',
            'name' => 'Pemrograman Web Lanjut',
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);

        $courseB = Course::factory()->create([
            'code' => 'SI202',
            'name' => 'Basis Data Relasional',
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);

        // Search by name
        $response = $this->get(route('admin.courses.index', ['q' => 'Web']));
        $response->assertOk();
        $response->assertSee('Pemrograman Web Lanjut');
        $response->assertDontSee('Basis Data Relasional');

        // Search by code
        $responseCode = $this->get(route('admin.courses.index', ['q' => 'SI202']));
        $responseCode->assertOk();
        $responseCode->assertSee('Basis Data Relasional');
        $responseCode->assertDontSee('Pemrograman Web Lanjut');
    }

    public function test_can_filter_course_by_status(): void
    {
        $dosen = User::factory()->dosen()->create();

        $activeCourse = Course::factory()->create([
            'name' => 'Kalkulus 1',
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);

        $draftCourse = Course::factory()->create([
            'name' => 'Aljabar Linear',
            'lecturer_id' => $dosen->id,
            'status' => 'draft',
        ]);

        $response = $this->get(route('admin.courses.index', ['status' => 'draft']));
        $response->assertOk();
        $response->assertSee('Aljabar Linear');
        $response->assertDontSee('Kalkulus 1');
    }

    public function test_course_pagination_keeps_query_string(): void
    {
        $dosen = User::factory()->dosen()->create();

        // Create 20 courses matching search term
        Course::factory()->count(20)->create([
            'name' => 'Modul Laravel Web',
            'lecturer_id' => $dosen->id,
            'status' => 'active',
        ]);

        $response = $this->get(route('admin.courses.index', ['q' => 'Laravel', 'status' => 'active']));
        $response->assertOk();
        // Check that page 2 link contains query params
        $response->assertSee('q=Laravel');
        $response->assertSee('status=active');
        $response->assertSee('page=2');
    }

    public function test_lecturer_only_sees_courses_they_teach(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();

        Course::factory()->create([
            'name' => 'Mata Kuliah Dosen Ini',
            'lecturer_id' => $lecturer->id,
        ]);
        Course::factory()->create([
            'name' => 'Mata Kuliah Dosen Lain',
            'lecturer_id' => $otherLecturer->id,
        ]);

        $response = $this->actingAs($lecturer)->get(route('dosen.courses.index'));

        $response->assertOk();
        $response->assertSee('Mata Kuliah Dosen Ini');
        $response->assertDontSee('Mata Kuliah Dosen Lain');
    }

    public function test_student_only_sees_enrolled_courses(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $lecturer = User::factory()->dosen()->create();
        $enrolledCourse = Course::factory()->create([
            'name' => 'Mata Kuliah Diikuti',
            'lecturer_id' => $lecturer->id,
        ]);
        Course::factory()->create([
            'name' => 'Mata Kuliah Tidak Diikuti',
            'lecturer_id' => $lecturer->id,
        ]);
        $enrolledCourse->students()->attach($student->id, ['enrolled_at' => now()]);

        $response = $this->actingAs($student)->get(route('mahasiswa.courses.index'));

        $response->assertOk();
        $response->assertSee('Mata Kuliah Diikuti');
        $response->assertDontSee('Mata Kuliah Tidak Diikuti');
    }

    public function test_can_search_user_by_name_or_email(): void
    {
        User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@kampus.ac.id',
            'role' => 'mahasiswa',
        ]);

        User::factory()->create([
            'name' => 'Siti Rahma',
            'email' => 'siti@kampus.ac.id',
            'role' => 'dosen',
        ]);

        // Search by name
        $response = $this->get(route('admin.users.index', ['q' => 'Budi']));
        $response->assertOk();
        $response->assertSee('Budi Santoso');
        $response->assertDontSee('Siti Rahma');

        // Search by email
        $responseEmail = $this->get(route('admin.users.index', ['q' => 'siti@']));
        $responseEmail->assertOk();
        $responseEmail->assertSee('Siti Rahma');
        $responseEmail->assertDontSee('Budi Santoso');
    }

    public function test_can_filter_user_by_role(): void
    {
        User::factory()->create([
            'name' => 'Agus Dosen',
            'role' => 'dosen',
        ]);

        User::factory()->create([
            'name' => 'Rini Mahasiswa',
            'role' => 'mahasiswa',
        ]);

        $response = $this->get(route('admin.users.index', ['role' => 'dosen']));
        $response->assertOk();
        $response->assertSee('Agus Dosen');
        $response->assertDontSee('Rini Mahasiswa');
    }

    public function test_user_pagination_keeps_query_string(): void
    {
        User::factory()->count(20)->create([
            'name' => 'Siswa Camp',
            'role' => 'mahasiswa',
        ]);

        $response = $this->get(route('admin.users.index', ['q' => 'Camp', 'role' => 'mahasiswa']));
        $response->assertOk();
        $response->assertSee('q=Camp');
        $response->assertSee('role=mahasiswa');
        $response->assertSee('page=2');
    }

    public function test_flash_message_displays_in_layout(): void
    {
        $response = $this->withSession(['success' => 'Operasi berhasil dilakukan!'])
            ->get(route('admin.courses.index'));

        $response->assertOk();
        $response->assertSee('Operasi berhasil dilakukan!');
        $response->assertSee('alert-success');
    }
}
