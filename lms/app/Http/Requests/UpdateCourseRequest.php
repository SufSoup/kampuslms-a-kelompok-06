<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: Implementasi Policy pada minggu 7.
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('courses', 'code')->ignore($this->course->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'sks' => ['required', 'integer', 'min:1', 'max:6'],
            'lecturer_id' => ['required', 'exists:users,id'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode mata kuliah wajib diisi.',
            'code.string' => 'Kode mata kuliah harus berupa teks.',
            'code.max' => 'Kode mata kuliah maksimal 255 karakter.',
            'code.unique' => 'Kode mata kuliah sudah digunakan oleh mata kuliah lain.',
            'name.required' => 'Nama mata kuliah wajib diisi.',
            'name.string' => 'Nama mata kuliah harus berupa teks.',
            'name.max' => 'Nama mata kuliah maksimal 255 karakter.',
            'description.required' => 'Deskripsi mata kuliah wajib diisi.',
            'description.string' => 'Deskripsi mata kuliah harus berupa teks.',
            'sks.required' => 'SKS wajib diisi.',
            'sks.integer' => 'SKS harus berupa angka bulat.',
            'sks.min' => 'SKS minimal 1.',
            'sks.max' => 'SKS maksimal 6.',
            'lecturer_id.required' => 'Dosen wajib dipilih.',
            'lecturer_id.exists' => 'Dosen yang dipilih tidak valid.',
            'status.required' => 'Status mata kuliah wajib dipilih.',
            'status.in' => 'Status mata kuliah tidak valid.',
        ];
    }
}