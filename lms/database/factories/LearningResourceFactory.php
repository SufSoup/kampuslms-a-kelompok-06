<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\LearningResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LearningResource>
 */
class LearningResourceFactory extends Factory
{
    protected $model = LearningResource::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'added_by' => User::factory()->dosen(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['article', 'video', 'book', 'documentation', 'other']),
            'url' => fake()->url(),
            'source_platform' => fake()->randomElement(['YouTube', 'MDN', 'Laravel Docs', 'GitHub']),
            'is_recommended' => fake()->boolean(20),
        ];
    }
}
