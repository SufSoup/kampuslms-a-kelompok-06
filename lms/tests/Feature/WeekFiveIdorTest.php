<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WeekFiveIdorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_any_material(): void
    {
        $course = Course::factory()->create();
        $material = Material::factory()->create([
            'course_id' => $course->id,
            'uploaded_by' => $course->lecturer_id,
            'title' => 'Materi terbatas',
        ]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.materials.show', $material));

        $response->assertOk();
        $response->assertSee('Materi terbatas');
    }

    public function test_lecturer_can_view_material_only_for_a_course_they_teach(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);
        $courseMaterial = Material::factory()->create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
            'title' => 'Materi dosen sendiri',
        ]);
        $otherMaterial = Material::factory()->create([
            'course_id' => $otherCourse->id,
            'uploaded_by' => $otherLecturer->id,
        ]);

        $this->actingAs($lecturer)
            ->get(route('dosen.materials.show', $courseMaterial))
            ->assertOk();

        $response = $this->actingAs($lecturer)
            ->get(route('dosen.materials.show', $otherMaterial));

        $response->assertForbidden();
        $response->assertSeeText('Akses Ditolak');
        $response->assertDontSee($otherLecturer->name);
    }

    public function test_student_can_view_material_only_for_an_enrolled_course(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $enrolledCourse = Course::factory()->create();
        $otherCourse = Course::factory()->create();
        $enrolledCourse->students()->attach($student->id, ['enrolled_at' => now()]);
        $enrolledMaterial = Material::factory()->create([
            'course_id' => $enrolledCourse->id,
            'uploaded_by' => $enrolledCourse->lecturer_id,
            'title' => 'Materi terdaftar',
        ]);
        $otherMaterial = Material::factory()->create([
            'course_id' => $otherCourse->id,
            'uploaded_by' => $otherCourse->lecturer_id,
        ]);

        $this->actingAs($student)
            ->get(route('mahasiswa.materials.show', $enrolledMaterial))
            ->assertOk()
            ->assertSee('Materi terdaftar');

        $response = $this->actingAs($student)
            ->get(route('mahasiswa.materials.show', $otherMaterial));

        $response->assertForbidden();
        $response->assertSeeText('Akses Ditolak');
        $response->assertDontSee($otherCourse->name);
    }

    public function test_course_show_denies_a_lecturer_who_does_not_teach_it(): void
    {
        $course = Course::factory()->create();
        $otherLecturer = User::factory()->dosen()->create();

        $response = $this->actingAs($otherLecturer)
            ->get(route('dosen.courses.show', $course));

        $response->assertForbidden();
        $response->assertSeeText('Akses Ditolak');
    }

    public function test_scope_bindings_reject_an_assignment_from_another_course(): void
    {
        Route::get('/_test-unscoped/courses/{course}/assignments/{assignment}', function (Course $course, Assignment $assignment) {
            return response()->json(['course' => $course->id, 'assignment' => $assignment->id]);
        })->middleware(\Illuminate\Routing\Middleware\SubstituteBindings::class);

        Route::get('/_test-scoped/courses/{course}/assignments/{assignment}', function (Course $course, Assignment $assignment) {
            return response()->json(['course' => $course->id, 'assignment' => $assignment->id]);
        })->scopeBindings()->middleware(\Illuminate\Routing\Middleware\SubstituteBindings::class);

        $course = Course::factory()->create();
        $otherCourse = Course::factory()->create();
        $assignment = Assignment::factory()->create(['course_id' => $otherCourse->id]);

        $scopedRoute = Route::getRoutes()->match(\Illuminate\Http\Request::create(
            "/_test-scoped/courses/{$course->id}/assignments/{$assignment->id}"
        ));

        $this->assertTrue($scopedRoute->enforcesScopedBindings());
        $this->assertSame((string) $course->id, $scopedRoute->parentOfParameter('assignment'));

        $this->get("/_test-unscoped/courses/{$course->id}/assignments/{$assignment->id}")
            ->assertOk();

        $this->get("/_test-scoped/courses/{$course->id}/assignments/{$assignment->id}")
            ->assertNotFound();
    }
}