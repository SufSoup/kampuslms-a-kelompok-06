<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'mahasiswa';
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Berkas tugas wajib diunggah.',
            'file.file' => 'Berkas yang diunggah tidak valid.',
            'file.mimes' => 'Format berkas harus berupa PDF, DOC, atau DOCX.',
            'file.max' => 'Ukuran berkas maksimal adalah 5 MB (5120 KB).',
            'note.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }
}
