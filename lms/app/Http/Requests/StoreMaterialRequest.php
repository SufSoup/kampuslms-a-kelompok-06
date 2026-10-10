<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['file', 'link'])],
            'external_url' => ['nullable', 'url', 'max:500', Rule::requiredIf($this->input('type') === 'link')],
            'file_path' => ['nullable', 'string', 'max:500'],
            'original_name' => ['nullable', 'string', 'max:255'],
            'file_size' => ['nullable', 'integer', 'min:0'],
            'mime_type' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul materi wajib diisi.',
            'title.max' => 'Judul materi maksimal 255 karakter.',
            'type.required' => 'Tipe materi wajib dipilih.',
            'type.in' => 'Tipe materi harus berupa file atau tautan.',
            'external_url.required' => 'URL materi wajib diisi ketika tipe materi adalah tautan.',
            'external_url.url' => 'Format URL tidak valid.',
            'file_path.max' => 'Lokasi berkas maksimal 500 karakter.',
        ];
    }
}
