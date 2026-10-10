<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\LearningResource;
use App\Models\User;

class LearningResourcePolicy
{
    /**
     * Semua pengguna terautentikasi dapat melihat daftar sumber belajar
     * (penyaringan objek spesifik dilakukan di level query/view).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Hak melihat sumber belajar:
     * - Admin: semua sumber belajar.
     * - Dosen: hanya pada mata kuliah yang dia ampu.
     * - Mahasiswa: hanya pada mata kuliah yang diikutinya (enrolled).
     */
    public function view(User $user, LearningResource $learningResource): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $course = $learningResource->course;

        if ($user->role === 'dosen') {
            return $course->lecturer_id === $user->id;
        }

        if ($user->role === 'mahasiswa') {
            return $course->students()->whereKey($user->id)->exists();
        }

        return false;
    }

    /**
     * Dosen hanya boleh menambahkan sumber belajar pada MK miliknya. Admin boleh di semua MK.
     */
    public function create(User $user, Course $course): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'dosen' && $course->lecturer_id === $user->id;
    }

    /**
     * Dosen hanya boleh mengubah sumber belajar pada MK miliknya.
     */
    public function update(User $user, LearningResource $learningResource): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'dosen' && $learningResource->course->lecturer_id === $user->id;
    }

    /**
     * Dosen hanya boleh menghapus sumber belajar pada MK miliknya.
     */
    public function delete(User $user, LearningResource $learningResource): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'dosen' && $learningResource->course->lecturer_id === $user->id;
    }

    /**
     * Anti-IDOR Fitur 17:
     * Mahasiswa hanya boleh menandai baca jika terdaftar (enrolled) pada mata kuliah tersebut.
     */
    public function toggleRead(User $user, LearningResource $learningResource): bool
    {
        if ($user->role !== 'mahasiswa') {
            return false;
        }

        return $learningResource->course->students()->whereKey($user->id)->exists();
    }
}
