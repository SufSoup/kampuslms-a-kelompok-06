<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'submission_id' => $this->submission_id,
            'score' => $this->score,
            'feedback' => $this->feedback,
            'graded_by' => $this->graded_by,
            'graded_at' => $this->graded_at?->toIso8601String(),

            'grader' => new UserResource(
                $this->whenLoaded('grader')
            ),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}