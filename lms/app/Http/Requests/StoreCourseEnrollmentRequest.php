<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $course = $this->route('course');

        return [
            'student_id' => [
                'required',
                Rule::exists('users', 'id')
                    ->where('role', 'mahasiswa')
                    ->whereNull('deleted_at'),
                Rule::unique('course_user', 'user_id')
                    ->where('course_id', $course->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Mahasiswa wajib dipilih.',
            'student_id.exists' => 'Pengguna yang dipilih bukan mahasiswa aktif.',
            'student_id.unique' => 'Mahasiswa sudah terdaftar pada mata kuliah ini.',
        ];
    }
}
