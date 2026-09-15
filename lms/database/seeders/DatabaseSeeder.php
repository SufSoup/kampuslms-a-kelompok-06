<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /*
         * ============================================================
         * 1. USERS
         * ============================================================
         */

        // 1 admin
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Kampus LMS',
            'email' => 'admin@kampuslms.test',
            'password' => 'password',
        ]);

        // 3 dosen
        $dosen = collect([
            User::factory()->dosen()->create([
                'name' => 'Dosen Demo',
                'email' => 'dosen@kampuslms.test',
                'nim_nip' => '198001010001',
                'password' => 'password',
            ]),
            User::factory()->dosen()->create([
                'name' => 'Budi Santoso',
                'email' => 'budi.dosen@kampuslms.test',
            ]),
            User::factory()->dosen()->create([
                'name' => 'Siti Rahmawati',
                'email' => 'siti.dosen@kampuslms.test',
            ]),
        ]);

        // 30 mahasiswa
        $mahasiswa = collect([
            User::factory()->mahasiswa()->create([
                'name' => 'Mahasiswa Demo',
                'email' => 'mahasiswa@kampuslms.test',
                'nim_nip' => '2024105001',
                'password' => 'password',
            ]),
            ...User::factory()->mahasiswa()->count(29)->create()->all(),
        ]);

        /*
         * ============================================================
         * 2. COURSES
         * ============================================================
         */

        $courseNames = [
            'Pemrograman Web',
            'Basis Data',
            'Jaringan Komputer',
            'Rekayasa Perangkat Lunak',
            'Sistem Informasi',
        ];

        $courses = collect();

        foreach ($courseNames as $index => $courseName) {
            $courses->push(
                Course::factory()->create([
                    'name' => $courseName,
                    'lecturer_id' => $dosen[$index % $dosen->count()]->id,
                    'status' => 'active',
                ])
            );
        }

        /*
         * ============================================================
         * 3. ENROLLMENT
         * ============================================================
         */

        foreach ($courses as $course) {
            // Setiap course memiliki 20 mahasiswa.
            // Karena ada 30 mahasiswa, setiap course pasti
            // memenuhi syarat minimal 15 mahasiswa.
            $students = $mahasiswa->random(20);

            $course->students()->attach(
                $students->pluck('id')->mapWithKeys(
                    fn ($userId) => [
                        $userId => [
                            'enrolled_at' => now()->subDays(
                                fake()->numberBetween(7, 30)
                            ),
                        ],
                    ]
                )->all()
            );
        }

        /*
         * ============================================================
         * 4. ASSIGNMENTS
         * ============================================================
         */

        $assignments = collect();

        foreach ($courses as $course) {
            // Assignment 1: deadline sudah lewat
            $assignments->push(
                Assignment::factory()->pastDue()->create([
                    'course_id' => $course->id,
                    'created_by' => $course->lecturer_id,
                    'title' => 'Tugas Dasar ' . $course->name,
                ])
            );

            // Assignment 2: masih aktif
            $assignments->push(
                Assignment::factory()->active()->create([
                    'course_id' => $course->id,
                    'created_by' => $course->lecturer_id,
                    'title' => 'Tugas Analisis ' . $course->name,
                ])
            );

            // Assignment 3: draft
            $assignments->push(
                Assignment::factory()->draft()->create([
                    'course_id' => $course->id,
                    'created_by' => $course->lecturer_id,
                    'title' => 'Tugas Proyek ' . $course->name,
                ])
            );
        }

        /*
         * ============================================================
         * 5. SUBMISSIONS
         * ============================================================
         */

        $submissions = collect();

        foreach ($assignments as $assignment) {
            $course = $courses->firstWhere('id', $assignment->course_id);

            // Ambil mahasiswa yang benar-benar terdaftar
            // pada course assignment tersebut.
            $enrolledStudents = $course->students()->get();

            /*
             * Untuk assignment published:
             * buat submission dari seluruh mahasiswa yang terdaftar.
             *
             * Untuk draft:
             * tidak perlu membuat submission.
             */
            if ($assignment->status === 'published') {
                foreach ($enrolledStudents as $student) {
                    $isLate = fake()->boolean(20);

                    $submittedAt = $isLate
                        ? $assignment->due_at->copy()->addHours(
                            fake()->numberBetween(1, 48)
                        )
                        : $assignment->due_at->copy()->subHours(
                            fake()->numberBetween(1, 72)
                        );

                    $submissions->push(
                        Submission::factory()->create([
                            'assignment_id' => $assignment->id,
                            'user_id' => $student->id,
                            'submitted_at' => $submittedAt,
                            'is_late' => $isLate,
                        ])
                    );
                }
            }
        }

        /*
         * ============================================================
         * 6. GRADES
         * ============================================================
         */

        /*
         * Sekitar 60% submission diberi nilai.
         */
        $numberOfGrades = (int) floor($submissions->count() * 0.6);

        $submissionsToGrade = $submissions
            ->shuffle()
            ->take($numberOfGrades);

        foreach ($submissionsToGrade as $submission) {
            $assignment = $submission->assignment;

            Grade::factory()->create([
                'submission_id' => $submission->id,
                'graded_by' => $assignment->created_by,
                'score' => fake()->randomFloat(
                    2,
                    60,
                    $assignment->max_score
                ),
                'graded_at' => now()->subDays(
                    fake()->numberBetween(0, 5)
                ),
            ]);
        }

        /*
         * ============================================================
         * 7. DEMO ACCOUNT
         * ============================================================
         *
         * Akun demo sudah dibuat di atas sebagai bagian dari
         * user utama, sehingga tidak membuat user tambahan.
         */
    }
}