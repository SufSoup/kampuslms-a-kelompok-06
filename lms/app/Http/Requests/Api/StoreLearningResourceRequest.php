<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreLearningResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:article,video,book,documentation,other'],
            'url' => ['required', 'url', 'max:500'],
            'source_platform' => ['nullable', 'string', 'max:100'],
            'is_recommended' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul sumber belajar wajib diisi.',
            'type.required' => 'Tipe sumber belajar wajib dipilih.',
            'type.in' => 'Tipe sumber belajar tidak valid.',
            'url.required' => 'Tautan sumber belajar wajib diisi.',
            'url.url' => 'Format tautan URL tidak valid.',
        ];
    }
}
