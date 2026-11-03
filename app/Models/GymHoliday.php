<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GymHoliday extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'is_closed' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }
}
