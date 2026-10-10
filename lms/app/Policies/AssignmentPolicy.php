<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;

class AssignmentPolicy
{
    /**
     * Menentukan siapa yang boleh melihat daftar tugas.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            'admin',
            'dosen',
            'mahasiswa',
        ], true);
    }

    /**
     * Admin dapat melihat semua tugas.
     * Dosen hanya dapat melihat tugas mata kuliah yang diampu.
     * Mahasiswa hanya dapat melihat tugas mata kuliah yang diikuti.
     */
    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $course = $assignment->course;

        if (!$course) {
            return false;
        }

        if ($user->role === 'dosen') {
            return (int) $course->lecturer_id === (int) $user->id;
        }

        if ($user->role === 'mahasiswa') {
            return $assignment->status === 'published'
                && $course->students()
                ->where('users.id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Hanya dosen yang dapat membuat tugas.
     */
    public function create(User $user, ?Course $course = null): bool
    {
        if ($user->role !== 'dosen') {
            return false;
        }

        return $course === null
            || (int) $course->lecturer_id === (int) $user->id;
    }

    /**
     * Hanya dosen pengampu yang dapat mengubah tugas.
     */
    public function update(User $user, Assignment $assignment): bool
    {
        return $user->role === 'dosen'
            && $assignment->course !== null
            && (int) $assignment->course->lecturer_id === (int) $user->id;
    }

    /**
     * Hanya dosen pengampu yang dapat menghapus tugas.
     */
    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }
}