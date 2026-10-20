<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;

class GradePolicy
{
    /**
     * Daftar nilai hanya untuk admin dan dosen.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'dosen'], true);
    }

    /**
     * Mahasiswa hanya melihat nilainya sendiri.
     * Dosen hanya melihat nilai pada mata kuliah yang diampu.
     * Admin dapat melihat semua nilai.
     */
    public function view(User $user, Grade $grade): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $submission = $grade->submission;

        if (!$submission) {
            return false;
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
     * Dosen pengampu dan admin boleh membuat nilai.
     */
    public function create(User $user, Submission $submission): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $course = $submission->assignment?->course;

        return $user->role === 'dosen'
            && $course !== null
            && (int) $course->lecturer_id === (int) $user->id;
    }

    /**
     * Dosen pengampu dan admin boleh mengubah nilai.
     */
    public function update(User $user, Grade $grade): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role !== 'dosen') {
            return false;
        }

        $course = $grade->submission?->assignment?->course;

        return $course !== null
            && (int) $course->lecturer_id === (int) $user->id;
    }

    /**
     * Dosen pengampu dan admin boleh menghapus nilai.
     */
    public function delete(User $user, Grade $grade): bool
    {
        return $this->update($user, $grade);
    }
}