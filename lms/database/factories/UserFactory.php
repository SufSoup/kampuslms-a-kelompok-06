<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'mahasiswa',
            'nim_nip' => fake()->unique()->numerify('##########'),
            'email_verified_at' => now(),
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'nim_nip' => null,
        ]);
    }

    /**
     * Indicate that the user is a lecturer.
     */
    public function dosen(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'dosen',
            'nim_nip' => fake()->unique()->numerify('##########'),
        ]);
    }

    /**
     * Indicate that the user is a student.
     */
    public function mahasiswa(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'mahasiswa',
            'nim_nip' => fake()->unique()->numerify('##########'),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}