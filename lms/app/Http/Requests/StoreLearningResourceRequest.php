<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLearningResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi diperiksa via Gate di controller
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['article', 'video', 'book', 'documentation', 'other'])],
            'url' => ['required', 'url', 'max:500'],
            'source_platform' => ['nullable', 'string', 'max:100'],
            'is_recommended' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul sumber belajar wajib diisi.',
            'title.max' => 'Judul maksimal 150 karakter.',
            'type.required' => 'Tipe sumber belajar wajib dipilih.',
            'type.in' => 'Tipe sumber belajar tidak valid.',
            'url.required' => 'URL sumber belajar wajib diisi.',
            'url.url' => 'Format URL tidak valid (harus menyertakan http:// atau https://).',
            'url.max' => 'URL maksimal 500 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_recommended' => $this->boolean('is_recommended'),
        ]);
    }
}
