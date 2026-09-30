<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function show(Material $material): View
    {
        $user = request()->user();
        $course = $material->course;

        $canView = $user !== null && match ($user->role) {
            'admin' => true,
            'dosen' => $course->lecturer_id === $user->id,
            'mahasiswa' => $course->students()->whereKey($user->id)->exists(),
            default => false,
        };

        abort_unless($canView, 403);

        return view('materials.show', compact('material'));
    }
}