<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'user_id' => $this->user_id,
            'file_path' => $this->file_path,
            'original_name' => $this->original_name,
            'file_size' => $this->file_size,
            'note' => $this->note,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'is_late' => (bool) $this->is_late,

            'student' => new UserResource(
                $this->whenLoaded('student')
            ),

            'grade' => new GradeResource(
                $this->whenLoaded('grade')
            ),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}