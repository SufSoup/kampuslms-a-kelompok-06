<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceEagerLoadingTest extends TestCase
{
    use RefreshDatabase;

    // ─── Setup ──────────────────────────────────────────────────────────────

    private User $lecturer;
    private User $student;
    private Course $course;
    private Assignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lecturer = User::factory()->dosen()->create();
        $this->student  = User::factory()->mahasiswa()->create();

        $this->course = Course::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'status'      => 'active',
        ]);

        $this->course->students()->attach($this->student->id, ['enrolled_at' => now()]);

        $this->assignment = Assignment::factory()->create([
            'course_id'  => $this->course->id,
            'created_by' => $this->lecturer->id,
            'status'     => 'published',
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id'       => $this->student->id,
        ]);

        Grade::factory()->create([
            'submission_id' => $submission->id,
            'graded_by'     => $this->lecturer->id,
        ]);
    }

    // ─── 1. preventLazyLoading aktif di luar produksi ───────────────────────

    public function test_prevent_lazy_loading_is_active_outside_production(): void
    {
        // Model::preventsLazyLoading() true saat APP_ENV=testing (bukan production)
        $this->assertTrue(Model::preventsLazyLoading());
    }

    // ─── 2. Halaman rekap nilai dosen tidak ada lazy loading violation ────────

    public function test_course_grades_page_has_no_lazy_loading_violations(): void
    {
        // Kalau ada lazy loading, preventLazyLoading akan lempar exception dan test ini fail
        $response = $this->actingAs($this->lecturer)
            ->get(route('dosen.courses.grades.index', $this->course));

        $response->assertOk();
    }

    // ─── 3. Halaman rekap nilai mahasiswa tidak ada lazy loading violation ────

    public function test_student_grades_page_has_no_lazy_loading_violations(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('mahasiswa.grades.index'));

        $response->assertOk();
    }

    // ─── 4. Rekap nilai dosen berjalan < 15 query ────────────────────────────

    public function test_course_grades_runs_under_15_queries(): void
    {
        DB::enableQueryLog();

        $this->actingAs($this->lecturer)
            ->get(route('dosen.courses.grades.index', $this->course))
            ->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // ponytail: threshold 15 sesuai modul.md §11.3 BUILD poin 8
        $this->assertLessThan(15, $queryCount,
            "Halaman rekap nilai dosen menjalankan {$queryCount} query, target < 15."
        );
    }

    // ─── 5. Rekap nilai mahasiswa berjalan < 15 query ────────────────────────

    public function test_student_grades_runs_under_15_queries(): void
    {
        DB::enableQueryLog();

        $this->actingAs($this->student)
            ->get(route('mahasiswa.grades.index'))
            ->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(15, $queryCount,
            "Halaman rekap nilai mahasiswa menjalankan {$queryCount} query, target < 15."
        );
    }

    // ─── 6. Tidak ada env() di luar config/ ─────────────────────────────────

    public function test_no_env_calls_outside_config_directory(): void
    {
        // Scan app/, routes/, database/ — env() di sana akan null setelah config:cache
        $dirs = [
            base_path('app'),
            base_path('routes'),
            base_path('database'),
        ];

        $violations = [];

        foreach ($dirs as $dir) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $lines = file($file->getPathname());
                foreach ($lines as $line) {
                    // Skip baris komentar, cek env( pada kode aktif
                    $trimmed = ltrim($line);
                    if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '#')) {
                        continue;
                    }
                    // Hapus inline comment dulu sebelum cek
                    $codePart = preg_replace('/\/\/.*$/', '', $line);
                    if (preg_match('/\benv\s*\(/', $codePart)) {
                        $violations[] = str_replace(base_path() . '/', '', $file->getPathname());
                        break; // satu kali per file cukup
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            'File berikut memanggil env() di luar config/ (akan null setelah config:cache): '
            . implode(', ', $violations)
        );
    }
}
