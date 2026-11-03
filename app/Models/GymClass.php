<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GymClass extends Model
{
    public const CATEGORIES = [
        'Barbell Strength',
        'Metabolic Conditioning',
        'Hypertrophy Foundations',
        'Mobility & Core',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'session_date' => 'date',
            'capacity' => 'integer',
            'booked_count' => 'integer',
            'requires_active_membership' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(GymClassBooking::class);
    }

    public function activeBookings(): HasMany
    {
        return $this->hasMany(GymClassBooking::class)->whereIn('status', ['booked', 'attended']);
    }

    public function remainingSpots(): int
    {
        return max(0, $this->capacity - $this->booked_count);
    }
}
