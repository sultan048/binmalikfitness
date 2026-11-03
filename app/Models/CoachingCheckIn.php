<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachingCheckIn extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'weight_kg' => 'float',
            'waist_cm' => 'float',
            'chest_cm' => 'float',
            'hips_cm' => 'float',
            'arms_cm' => 'float',
            'thighs_cm' => 'float',
            'body_fat_pct' => 'float',
            'sleep_hours' => 'float',
            'water_liters' => 'float',
            'is_public_display_consented' => 'boolean',
            'reviewed_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CoachingSubscription::class, 'coaching_subscription_id');
    }

    public function progressPhoto(): BelongsTo
    {
        return $this->belongsTo(PrivateFile::class, 'progress_photo_file_id');
    }

    public function consentRecord(): BelongsTo
    {
        return $this->belongsTo(ConsentRecord::class, 'photo_consent_record_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
