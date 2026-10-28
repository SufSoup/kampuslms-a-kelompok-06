<?php

namespace App\Models\Concerns;

trait HasFormattedFileSize
{
    /**
     * Mengubah ukuran file dalam byte menjadi teks yang mudah dibaca.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if ($this->file_size === null) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        $size = (float) $this->file_size;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return number_format($size, $unitIndex === 0 ? 0 : 1).' '.$units[$unitIndex];
    }
}
