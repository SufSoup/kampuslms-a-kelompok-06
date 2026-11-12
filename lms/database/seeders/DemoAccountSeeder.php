<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoAccountSeeder extends Seeder
{
    /**
     * Seed demo accounts.
     */
    public function run(): void
    {
        $accounts = [
            [
                'email' => 'admin@kampuslms.test',
                'name' => 'Admin Kampus LMS',
                'password' => config('demo.passwords.admin'),
                'role' => 'admin',
                'nim_nip' => null,
            ],
            [
                'email' => 'dosen@kampuslms.test',
                'name' => 'Dosen Demo',
                'password' => config('demo.passwords.lecturer'),
                'role' => 'dosen',
                'nim_nip' => '198001010001',
            ],
            [
                'email' => 'budi.dosen@kampuslms.test',
                'name' => 'Budi Santoso',
                'password' => config('demo.passwords.lecturer_2'),
                'role' => 'dosen',
                'nim_nip' => '198001010002',
            ],
            [
                'email' => 'mahasiswa@kampuslms.test',
                'name' => 'Mahasiswa Demo',
                'password' => config('demo.passwords.student'),
                'role' => 'mahasiswa',
                'nim_nip' => '2024105001',
            ],
            [
                'email' => 'mahasiswa2@kampuslms.test',
                'name' => 'Mahasiswa IDOR Test',
                'password' => config('demo.passwords.idor_student'),
                'role' => 'mahasiswa',
                'nim_nip' => null,
            ],
        ];

        foreach ($accounts as $account) {
            if (! is_string($account['password']) || $account['password'] === '') {
                throw new RuntimeException(
                    "Set a non-empty production password for {$account['email']} before seeding demo accounts."
                );
            }
        }

        foreach ($accounts as $account) {
            $user = User::withTrashed()->firstOrNew([
                'email' => $account['email'],
            ]);

            $user->forceFill([
                'name' => $account['name'],
                'password' => $account['password'],
                'role' => $account['role'],
                'nim_nip' => $account['nim_nip'],
                'email_verified_at' => now(),
            ]);

            if ($user->trashed()) {
                $user->restore();
            }

            $user->save();
        }
    }
}
