<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Material>
 */
class MaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isFile = fake()->boolean();

        return [
            'course_id' => Course::factory(),
            'uploaded_by' => User::factory()->dosen(),
            'title' => fake()->randomElement([
                'Slide Pertemuan 1',
                'Slide Pertemuan 2',
                'Modul Praktikum',
                'Referensi Tambahan',
                'Panduan Tugas',
                'Catatan Kuliah',
                'Contoh Kode',
                'Ringkasan Materi',
            ]),
            'description' => fake()->sentence(10),
            'type' => $isFile ? 'file' : 'link',
            'file_path' => $isFile ? 'materials/' . fake()->uuid() . '.pdf' : null,
            'original_name' => $isFile ? fake()->word() . '.pdf' : null,
            'file_size' => $isFile ? fake()->numberBetween(100_000, 5_000_000) : null,
            'mime_type' => $isFile ? 'application/pdf' : null,
            'external_url' => $isFile ? null : fake()->url(),
        ];
    }

    /**
     * Indicate that the material is a file upload.
     */
    public function file(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'file',
            'file_path' => 'materials/' . fake()->uuid() . '.pdf',
            'original_name' => fake()->word() . '.pdf',
            'file_size' => fake()->numberBetween(100_000, 5_000_000),
            'mime_type' => 'application/pdf',
            'external_url' => null,
        ]);
    }

    /**
     * Indicate that the material is an external link.
     */
    public function link(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'link',
            'file_path' => null,
            'original_name' => null,
            'file_size' => null,
            'mime_type' => null,
            'external_url' => fake()->url(),
        ]);
    }
}
