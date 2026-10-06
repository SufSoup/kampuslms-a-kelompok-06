<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * Pastikan DatabaseSeeder berjalan sukses tanpa error.
     */
    public function test_database_seeder_runs_successfully(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'admin@kampuslms.test']);
        $this->assertDatabaseHas('users', ['email' => 'dosen@kampuslms.test']);
        $this->assertDatabaseHas('users', ['email' => 'mahasiswa@kampuslms.test']);
        $this->assertGreaterThan(0, \App\Models\LearningResource::count());
        $this->assertGreaterThan(0, \Illuminate\Support\Facades\DB::table('learning_resource_reads')->count());
    }
}
