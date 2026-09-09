<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CourseController extends Controller
{
    // data statis sementara, database baru minggu 3
    private array $courses = [
        ['id' => 1, 'code' => 'SI2514024', 'name' => 'Pemrograman Web', 'sks' => 3, 'lecturer' => 'Dosen A'],
        ['id' => 2, 'code' => 'SI2514025', 'name' => 'Basis Data', 'sks' => 3, 'lecturer' => 'Dosen B'],
        ['id' => 3, 'code' => 'SI2514026', 'name' => 'Jaringan Komputer', 'sks' => 2, 'lecturer' => 'Dosen C'],
    ];

public function index()
{
    $courses = $this->courses;
    $nama = '<script>alert("XSS")</script>';
    return view('courses.index', compact('courses', 'nama'));
}

    public function show(int $id)
    {
        $course = collect($this->courses)->firstWhere('id', $id);

        abort_if($course === null, 404);

        return view('courses.show', compact('course'));
    }
}