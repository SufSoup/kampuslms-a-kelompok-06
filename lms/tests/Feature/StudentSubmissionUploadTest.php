<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentSubmissionUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $lecturer;
    private Course $course;
    private Assignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->lecturer = User::factory()->dosen()->create();
        $this->course = Course::factory()->create(['lecturer_id' => $this->lecturer->id]);
        $this->student = User::factory()->mahasiswa()->create();

        // Daftarkan mahasiswa ke mata kuliah
        $this->course->students()->attach($this->student->id, ['enrolled_at' => now()]);

        $this->assignment = Assignment::factory()->create([
            'course_id' => $this->course->id,
            'created_by' => $this->lecturer->id,
            'status' => 'published',
            'due_at' => now()->addDays(3),
            'allow_late' => true,
        ]);
    }

    public function test_enrolled_student_can_upload_pdf_submission(): void
    {
        $this->actingAs($this->student);

        $file = UploadedFile::fake()->create('laporan.pdf', 1024, 'application/pdf');

        $response = $this->post(route('mahasiswa.assignments.submissions.store', $this->assignment), [
            'file' => $file,
            'note' => 'Pengumpulan tugas praktikum minggu 9.',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $this->assignment->id,
            'user_id' => $this->student->id,
            'original_name' => 'laporan.pdf',
            'is_late' => false,
            'note' => 'Pengumpulan tugas praktikum minggu 9.',
        ]);

        $submission = Submission::where('assignment_id', $this->assignment->id)
            ->where('user_id', $this->student->id)
            ->firstOrFail();

        Storage::disk('local')->assertExists($submission->file_path);
    }

    public function test_resubmission_updates_existing_record_and_replaces_physical_file(): void
    {
        $this->actingAs($this->student);

        // Pengumpulan pertama
        $file1 = UploadedFile::fake()->create('draft.docx', 500, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->post(route('mahasiswa.assignments.submissions.store', $this->assignment), [
            'file' => $file1,
            'note' => 'Versi 1',
        ])->assertSessionHas('success');

        $submission1 = Submission::where('assignment_id', $this->assignment->id)
            ->where('user_id', $this->student->id)
            ->firstOrFail();
        $oldFilePath = $submission1->file_path;
        Storage::disk('local')->assertExists($oldFilePath);

        // Pengumpulan kedua (re-submit)
        $file2 = UploadedFile::fake()->create('final.pdf', 800, 'application/pdf');
        $this->post(route('mahasiswa.assignments.submissions.store', $this->assignment), [
            'file' => $file2,
            'note' => 'Versi Final',
        ])->assertSessionHas('success');

        // Pastikan hanya ada 1 baris di tabel submissions (updateOrCreate)
        $this->assertSame(
            1,
            Submission::where('assignment_id', $this->assignment->id)
                ->where('user_id', $this->student->id)
                ->count()
        );

        $submission2 = Submission::where('assignment_id', $this->assignment->id)
            ->where('user_id', $this->student->id)
            ->firstOrFail();

        $this->assertSame('final.pdf', $submission2->original_name);
        $this->assertSame('Versi Final', $submission2->note);

        // Berkas lama harus sudah dihapus dari disk
        Storage::disk('local')->assertMissing($oldFilePath);
        // Berkas baru harus tersimpan
        Storage::disk('local')->assertExists($submission2->file_path);
    }

    public function test_non_enrolled_student_cannot_upload_submission(): void
    {
        $otherStudent = User::factory()->mahasiswa()->create();
        $this->actingAs($otherStudent);

        $file = UploadedFile::fake()->create('tugas.pdf', 500, 'application/pdf');

        $response = $this->post(route('mahasiswa.assignments.submissions.store', $this->assignment), [
            'file' => $file,
        ]);

        $response->assertForbidden();
    }

    public function test_draft_assignment_cannot_receive_submissions(): void
    {
        $this->assignment->update(['status' => 'draft']);

        $this->actingAs($this->student);

        $file = UploadedFile::fake()->create('tugas.pdf', 500, 'application/pdf');

        $response = $this->post(route('mahasiswa.assignments.submissions.store', $this->assignment), [
            'file' => $file,
        ]);

        $response->assertForbidden();
    }

    public function test_disallowed_file_extension_or_mime_is_rejected(): void
    {
        $this->actingAs($this->student);

        $maliciousFile = UploadedFile::fake()->create('exploit.php', 100, 'text/x-php');

        $response = $this->post(route('mahasiswa.assignments.submissions.store', $this->assignment), [
            'file' => $maliciousFile,
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('submissions', [
            'user_id' => $this->student->id,
            'assignment_id' => $this->assignment->id,
        ]);
    }

    public function test_file_exceeding_max_size_is_rejected(): void
    {
        $this->actingAs($this->student);

        // Batas 5120 KB, unggah 6000 KB
        $largeFile = UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf');

        $response = $this->post(route('mahasiswa.assignments.submissions.store', $this->assignment), [
            'file' => $largeFile,
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('submissions', [
            'user_id' => $this->student->id,
            'assignment_id' => $this->assignment->id,
        ]);
    }

    public function test_late_submission_is_rejected_when_allow_late_is_false(): void
    {
        $this->assignment->update([
            'due_at' => now()->subDay(),
            'allow_late' => false,
        ]);

        $this->actingAs($this->student);

        $file = UploadedFile::fake()->create('terlambat.pdf', 500, 'application/pdf');

        $response = $this->post(route('mahasiswa.assignments.submissions.store', $this->assignment), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('submissions', [
            'user_id' => $this->student->id,
            'assignment_id' => $this->assignment->id,
        ]);
    }

    public function test_late_submission_is_accepted_and_marked_late_when_allow_late_is_true(): void
    {
        $this->assignment->update([
            'due_at' => now()->subDay(),
            'allow_late' => true,
        ]);

        $this->actingAs($this->student);

        $file = UploadedFile::fake()->create('terlambat.pdf', 500, 'application/pdf');

        $response = $this->post(route('mahasiswa.assignments.submissions.store', $this->assignment), [
            'file' => $file,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $this->assignment->id,
            'user_id' => $this->student->id,
            'is_late' => true,
        ]);
    }

    public function test_student_owner_and_lecturer_can_download_submission(): void
    {
        $this->actingAs($this->student);

        $file = UploadedFile::fake()->create('jawaban.pdf', 500, 'application/pdf');
        $this->post(route('mahasiswa.assignments.submissions.store', $this->assignment), [
            'file' => $file,
        ]);

        $submission = Submission::where('assignment_id', $this->assignment->id)
            ->where('user_id', $this->student->id)
            ->firstOrFail();

        // 1. Pemilik (mahasiswa) dapat mengunduh
        $this->actingAs($this->student);
        $downloadResponse = $this->get(route('submissions.download', $submission));
        $downloadResponse->assertOk();

        // 2. Dosen pengampu dapat mengunduh
        $this->actingAs($this->lecturer);
        $downloadLecturer = $this->get(route('submissions.download', $submission));
        $downloadLecturer->assertOk();

        // 3. Mahasiswa lain ditolak (403 Forbidden)
        $otherStudent = User::factory()->mahasiswa()->create();
        $this->actingAs($otherStudent);
        $downloadOther = $this->get(route('submissions.download', $submission));
        $downloadOther->assertForbidden();
    }
}
