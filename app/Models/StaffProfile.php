<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffProfile extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'qualifications' => 'array',
            'specialties' => 'array',
            'is_public_coach' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
