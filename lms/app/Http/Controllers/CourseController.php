<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $courses = Course::query()
            ->with('lecturer')
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->q . '%')
                      ->orWhere('code', 'like', '%' . $request->q . '%');
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('courses.index', compact('courses'));
    }

    public function create()
    {
        $lecturers = User::where('role', 'dosen')->orderBy('name')->get();

        return view('courses.create', compact('lecturers'));
    }

    public function store(StoreCourseRequest $request)
    {
        $validated = $request->validated();

        $lecturerId = $validated['lecturer_id'];
        unset($validated['lecturer_id']);

        $course = new Course();
        $course->fill($validated);
        $course->lecturer_id = $lecturerId;
        $course->save();

        return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil dibuat.');
    }

    public function show(Course $course)
    {
        $course->load('lecturer');

        return view('courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        $lecturers = User::where('role', 'dosen')->orderBy('name')->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        $validated = $request->validated();

        $lecturerId = $validated['lecturer_id'];
        unset($validated['lecturer_id']);

        $course->fill($validated);
        $course->lecturer_id = $lecturerId;
        $course->save();

        return redirect()->route('courses.show', $course)->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil dihapus.');
    }
}