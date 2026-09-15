<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

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
                'password' => 'password',
                'role' => 'admin',
                'nim_nip' => null,
            ],
            [
                'email' => 'dosen@kampuslms.test',
                'name' => 'Dosen Demo',
                'password' => 'password',
                'role' => 'dosen',
                'nim_nip' => '198001010001',
            ],
            [
                'email' => 'mahasiswa@kampuslms.test',
                'name' => 'Mahasiswa Demo',
                'password' => 'password',
                'role' => 'mahasiswa',
                'nim_nip' => '2024105001',
            ],
        ];

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