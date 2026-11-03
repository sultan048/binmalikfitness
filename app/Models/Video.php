<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function formattedDuration(): string
    {
        $mins = intdiv($this->duration_seconds, 60);
        $secs = $this->duration_seconds % 60;

        return sprintf('%02d:%02d', $mins, $secs);
    }
}
