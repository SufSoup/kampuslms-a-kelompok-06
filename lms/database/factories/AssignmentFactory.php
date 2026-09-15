<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assignment>
 */
class AssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'created_by' => User::factory()->dosen(),
            'title' => fake()->randomElement([
                'Tugas Pemrograman Dasar',
                'Tugas Analisis Materi',
                'Tugas Studi Kasus',
                'Tugas Implementasi',
                'Tugas Proyek Mingguan',
            ]),
            'instructions' => fake()->paragraph(),
            'due_at' => now()->addDays(7),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ];
    }

    /**
     * Indicate that the assignment is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the assignment has a past deadline.
     */
    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => now()->subDays(fake()->numberBetween(1, 14)),
            'status' => 'published',
        ]);
    }

    /**
     * Indicate that the assignment is currently active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_at' => now()->addDays(fake()->numberBetween(1, 14)),
            'status' => 'published',
        ]);
    }
}