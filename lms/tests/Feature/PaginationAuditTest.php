<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class PaginationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_list_is_paginated_and_keeps_query_string(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        Material::factory()->count(16)->create(['course_id' => $course->id]);

        $response = $this->actingAs($admin)->get(
            route('admin.courses.materials.index', [$course, 'type' => 'file'])
        );

        $response->assertOk();
        $response->assertSee('page=2');
        $response->assertViewHas('materials', function ($materials): bool {
            return $materials instanceof LengthAwarePaginator
                && $materials->perPage() === 15
                && $materials->count() === 15;
        });
    }

    public function test_submission_monitoring_list_is_paginated(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);
        $students = User::factory()->mahasiswa()->count(26)->create();
        $course->students()->attach($students->pluck('id')->mapWithKeys(
            fn (int $id): array => [$id => ['enrolled_at' => now()]]
        )->all());

        $response = $this->actingAs($lecturer)->get(
            route('dosen.assignments.submissions.index', $assignment)
        );

        $response->assertOk();
        $response->assertSee('page=2');
        $response->assertViewHas('students', function ($students): bool {
            return $students instanceof LengthAwarePaginator
                && $students->perPage() === 25
                && $students->count() === 25;
        });
    }
}
