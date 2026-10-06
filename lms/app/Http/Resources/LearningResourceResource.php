<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearningResourceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isRead = false;
        $readAt = null;

        if ($user) {
            if ($this->relationLoaded('readers')) {
                $reader = $this->readers->firstWhere('id', $user->id);
                $isRead = (bool) $reader;
                $readAt = $reader?->pivot?->read_at;
            } else {
                $readRecord = $this->readers()->where('user_id', $user->id)->first();
                $isRead = (bool) $readRecord;
                $readAt = $readRecord?->pivot?->read_at;
            }
        }

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'url' => $this->url,
            'source_platform' => $this->source_platform,
            'is_recommended' => (bool) $this->is_recommended,
            'curator' => new UserResource($this->whenLoaded('curator')),
            'is_read' => $isRead,
            'read_at' => $readAt ? (is_string($readAt) ? $readAt : $readAt->toIso8601String()) : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
