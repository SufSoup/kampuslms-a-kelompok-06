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
            'external_url' => ['nullable', 'url', 'max:255', Rule::requiredIf($this->input('type') === 'link')],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip', 'max:20480', Rule::requiredIf($this->input('type') === 'file')],
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
            'external_url.max' => 'URL materi maksimal 255 karakter.',
            'file.required' => 'Berkas materi wajib diunggah.',
            'file.file' => 'Berkas materi tidak valid.',
            'file.mimes' => 'Format berkas harus PDF, Word, PowerPoint, Excel, TXT, atau ZIP.',
            'file.max' => 'Ukuran berkas maksimal 20 MB.',
        ];
    }
}
