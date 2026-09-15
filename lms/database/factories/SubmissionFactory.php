<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),
            'user_id' => User::factory()->mahasiswa(),
            'file_path' => 'submissions/' . fake()->uuid() . '.pdf',
            'original_name' => fake()->randomElement([
                'tugas.pdf',
                'jawaban.pdf',
                'laporan.pdf',
                'proyek.pdf',
            ]),
            'file_size' => fake()->numberBetween(100_000, 5_000_000),
            'note' => fake()->optional()->sentence(),
            'submitted_at' => now(),
            'is_late' => false,
        ];
    }

    /**
     * Indicate that the submission was submitted late.
     */
    public function late(): static
    {
        return $this->state(function (array $attributes) {
            $assignment = Assignment::find($attributes['assignment_id']);

            return [
                'submitted_at' => $assignment
                    ? $assignment->due_at->copy()->addHours(fake()->numberBetween(1, 48))
                    : now()->subHours(2),
                'is_late' => true,
            ];
        });
    }
}