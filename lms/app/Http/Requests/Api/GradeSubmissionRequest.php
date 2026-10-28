<?php

namespace App\Http\Requests\Api;

use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

class GradeSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'dosen';
    }

    public function rules(): array
    {
        $submission = $this->route('submission');
        if (! $submission instanceof Submission) {
            throw new LogicException('Submission route model is required for grading.');
        }

        return [
            'score' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:'.$submission->assignment->max_score,
            ],
            'feedback' => ['nullable', 'string'],
        ];
    }
}
