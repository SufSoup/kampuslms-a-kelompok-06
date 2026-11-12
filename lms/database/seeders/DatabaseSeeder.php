<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\LearningResource;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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

        $this->call(DemoAccountSeeder::class);

        $demoLecturer = User::where('email', 'dosen@kampuslms.test')->firstOrFail();
        $demoLecturer2 = User::where('email', 'budi.dosen@kampuslms.test')->firstOrFail();
        $dosen = collect([
            $demoLecturer,
            $demoLecturer2,
            User::factory()->dosen()->create([
                'name' => 'Siti Rahmawati',
                'email' => 'siti.dosen@kampuslms.test',
                'password' => Str::random(48),
            ]),
        ]);

        // 30 mahasiswa
        $demoStudent = User::where('email', 'mahasiswa@kampuslms.test')->firstOrFail();
        $idorStudent = User::where('email', 'mahasiswa2@kampuslms.test')->firstOrFail();
        $mahasiswa = collect([
            $demoStudent,
            $idorStudent,
            ...User::factory()
                ->mahasiswa()
                ->count(28)
                ->state(fn () => ['password' => Str::random(48)])
                ->create()
                ->all(),
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

        $firstCourse = $courses->first();
        if ($firstCourse && $demoStudent) {
            $firstCourse->students()->syncWithoutDetaching([
                $demoStudent->id => ['enrolled_at' => now()],
            ]);
        }

        /*
         * ============================================================
         * 4. MATERIALS
         * ============================================================
         */

        foreach ($courses as $course) {
            // 2-4 materi per course, campuran file dan link
            $materialCount = fake()->numberBetween(2, 4);

            for ($i = 0; $i < $materialCount; $i++) {
                Material::factory()
                    ->{fake()->boolean() ? 'file' : 'link'}()
                    ->create([
                        'course_id' => $course->id,
                        'uploaded_by' => $course->lecturer_id,
                    ]);
            }
        }

        /*
         * ============================================================
         * 5. ASSIGNMENTS
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
         * 6. SUBMISSIONS
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
         * 7. GRADES
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
         * 8. LEARNING RESOURCES (Fitur 17)
         * ============================================================
         */

        foreach ($courses as $course) {
            $resourceCount = fake()->numberBetween(2, 4);

            $resources = LearningResource::factory()
                ->count($resourceCount)
                ->create([
                    'course_id' => $course->id,
                    'added_by' => $course->lecturer_id,
                ]);

            $enrolledStudents = $course->students()->get();

            // Beberapa mahasiswa acak sudah membaca referensi
            foreach ($resources as $resource) {
                if ($enrolledStudents->isNotEmpty()) {
                    $readerCount = min($enrolledStudents->count(), fake()->numberBetween(3, 8));
                    $readers = $enrolledStudents->random($readerCount);

                    foreach ($readers as $reader) {
                        $now = now()->subDays(fake()->numberBetween(1, 10));
                        $resource->readers()->attach($reader->id, [
                            'read_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }
}
