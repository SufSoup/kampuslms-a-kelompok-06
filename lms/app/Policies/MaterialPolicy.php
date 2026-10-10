<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    public function viewAny(User $user, ?Course $course = null): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($course === null) {
            return false;
        }

        if ($user->role === 'dosen') {
            return $course->lecturer_id === $user->id;
        }

        if ($user->role === 'mahasiswa') {
            return $course->students()->whereKey($user->id)->exists();
        }

        return false;
    }

    public function view(User $user, Material $material): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $course = $material->course;

        if ($user->role === 'dosen') {
            return $course->lecturer_id === $user->id;
        }

        if ($user->role === 'mahasiswa') {
            return $course->students()->whereKey($user->id)->exists();
        }

        return false;
    }

    public function create(User $user, Course $course): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'dosen' && $course->lecturer_id === $user->id;
    }

    public function update(User $user, Material $material): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'dosen' && $material->course->lecturer_id === $user->id;
    }

    public function delete(User $user, Material $material): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'dosen' && $material->course->lecturer_id === $user->id;
    }
}
