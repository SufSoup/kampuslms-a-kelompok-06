<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LearningResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'url',
        'source_platform',
        'is_recommended',
    ];

    protected function casts(): array
    {
        return [
            'is_recommended' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function curator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'learning_resource_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }
}
