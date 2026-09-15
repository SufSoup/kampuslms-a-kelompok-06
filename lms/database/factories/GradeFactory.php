<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Grade>
 */
class GradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'graded_by' => User::factory()->dosen(),
            'score' => fake()->randomFloat(2, 60, 100),
            'feedback' => fake()->randomElement([
                'Hasil pekerjaan sudah baik.',
                'Pemahaman materi sudah cukup baik.',
                'Perlu meningkatkan ketelitian dalam pengerjaan.',
                'Analisis sudah baik, tetapi masih dapat dikembangkan.',
                'Jawaban sudah sesuai dengan materi yang diberikan.',
            ]),
            'graded_at' => now(),
        ];
    }
}