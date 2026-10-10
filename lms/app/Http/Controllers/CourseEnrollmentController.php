<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseEnrollmentRequest;
use App\Models\Course;
use App\Models\User;

class CourseEnrollmentController extends Controller
{
    public function store(StoreCourseEnrollmentRequest $request, Course $course)
    {
        $course->students()->attach($request->validated('student_id'), [
            'enrolled_at' => now(),
        ]);

        return redirect()
            ->route('admin.courses.show', $course)
            ->with('success', 'Mahasiswa berhasil didaftarkan ke mata kuliah.');
    }

    public function destroy(Course $course, User $student)
    {
        abort_unless(
            $student->role === 'mahasiswa'
                && $course->students()->whereKey($student->id)->exists(),
            404
        );

        $course->students()->detach($student->id);

        return redirect()
            ->route('admin.courses.show', $course)
            ->with('success', 'Pendaftaran mahasiswa berhasil dihapus.');
    }
}
