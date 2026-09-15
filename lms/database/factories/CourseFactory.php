<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'SI' . fake()->unique()->numerify('#######'),
            'name' => fake()->randomElement([
                'Pemrograman Web',
                'Basis Data',
                'Jaringan Komputer',
                'Rekayasa Perangkat Lunak',
                'Sistem Informasi',
                'Pemrograman Berorientasi Objek',
                'Analisis dan Perancangan Sistem',
            ]),
            'description' => fake()->sentence(12),
            'sks' => fake()->numberBetween(2, 4),
            'lecturer_id' => User::factory()->dosen(),
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the course is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the course is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }
}