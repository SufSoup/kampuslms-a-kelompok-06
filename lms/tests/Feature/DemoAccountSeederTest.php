<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class DemoAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_requires_each_account_password(): void
    {
        config([
            'demo.passwords.admin' => null,
            'demo.passwords.lecturer' => 'lecturer-secret',
            'demo.passwords.lecturer_2' => 'lecturer-2-secret',
            'demo.passwords.student' => 'student-secret',
            'demo.passwords.idor_student' => 'idor-secret',
        ]);

        try {
            $this->seed(DemoAccountSeeder::class);
            $this->fail('Seeding must fail when a demo account password is missing.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('admin@kampuslms.test', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_demo_seeder_upserts_accounts_using_configured_passwords(): void
    {
        config([
            'demo.passwords.admin' => 'admin-secret',
            'demo.passwords.lecturer' => 'lecturer-secret',
            'demo.passwords.lecturer_2' => 'lecturer-2-secret',
            'demo.passwords.student' => 'student-secret',
            'demo.passwords.idor_student' => 'idor-secret',
        ]);

        $this->seed(DemoAccountSeeder::class);
        $this->seed(DemoAccountSeeder::class);

        $this->assertDatabaseCount('users', 5);

        $admin = User::where('email', 'admin@kampuslms.test')->firstOrFail();
        $this->assertTrue(Hash::check('admin-secret', $admin->password));
    }
}
