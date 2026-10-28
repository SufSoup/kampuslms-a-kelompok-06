<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\Assignment;
use App\Models\User;

class SubmissionPolicy
{
    /**
     * Daftar pengumpulan hanya untuk admin dan dosen pengampu.
     */
    public function viewAny(User $user, ?Assignment $assignment = null): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            if ($assignment === null) {
                return true;
            }

            return $assignment->course !== null
                && (int) $assignment->course->lecturer_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Mahasiswa hanya melihat pengumpulannya sendiri.
     * Dosen hanya melihat pengumpulan pada mata kuliah yang diampu.
     * Admin dapat melihat semua pengumpulan.
     */
    public function view(User $user, Submission $submission): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'mahasiswa') {
            return (int) $submission->user_id === (int) $user->id;
        }

        if ($user->role === 'dosen') {
            $course = $submission->assignment?->course;

            return $course !== null
                && (int) $course->lecturer_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Mahasiswa yang login boleh membuat pengumpulan.
     * Kepemilikan tugas dan pendaftaran mata kuliah
     * tetap harus divalidasi saat proses penyimpanan.
     */
    public function create(User $user, Assignment $assignment): bool
    {
        return $user->role === 'mahasiswa'
            && $assignment->status === 'published'
            && $assignment->course?->students()
                ->where('users.id', $user->id)
                ->exists();
    }

    /**
     * Mahasiswa hanya boleh mengubah pengumpulannya sendiri.
     */
    public function update(User $user, Submission $submission): bool
    {
        return $user->role === 'mahasiswa'
            && (int) $submission->user_id === (int) $user->id;
    }

    /**
     * Mahasiswa hanya boleh menghapus pengumpulannya sendiri.
     */
    public function delete(User $user, Submission $submission): bool
    {
        return $this->update($user, $submission);
    }
}