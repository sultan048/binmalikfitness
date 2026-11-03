<?php

namespace App\Models;

use App\Domain\Identity\RolePermissionMatrix;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_demo' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ! $this->trashed();
    }

    public function isPrivilegedStaff(): bool
    {
        return $this->hasAnyRole([
            RolePermissionMatrix::ROLE_ADMIN,
            RolePermissionMatrix::ROLE_MANAGER,
            RolePermissionMatrix::ROLE_COACH,
        ]);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! empty($this->two_factor_secret) && $this->two_factor_confirmed_at !== null;
    }

    public function isAssignedToClient(int $clientId): bool
    {
        return $this->coachAssignmentsAsCoach()
            ->where('client_id', $clientId)
            ->where('starts_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->exists();
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function coachAssignmentsAsCoach(): HasMany
    {
        return $this->hasMany(CoachAssignment::class, 'coach_id');
    }

    public function coachAssignmentsAsClient(): HasMany
    {
        return $this->hasMany(CoachAssignment::class, 'client_id');
    }

    public function privateFiles(): HasMany
    {
        return $this->hasMany(PrivateFile::class, 'owner_id');
    }

    public function consentRecords(): HasMany
    {
        return $this->hasMany(ConsentRecord::class);
    }

    public function coachingSubscriptions(): HasMany
    {
        return $this->hasMany(CoachingSubscription::class);
    }

    public function coachingIntakes(): HasMany
    {
        return $this->hasMany(CoachingIntake::class);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(Entitlement::class);
    }

    public function workoutAssignments(): HasMany
    {
        return $this->hasMany(WorkoutAssignment::class);
    }

    public function coachingCheckIns(): HasMany
    {
        return $this->hasMany(CoachingCheckIn::class);
    }

    public function coachingConsultations(): HasMany
    {
        return $this->hasMany(CoachingConsultation::class);
    }

    public function coachingMessagesAsClient(): HasMany
    {
        return $this->hasMany(CoachingMessage::class, 'client_id');
    }

    public function personalisedPlanRequests(): HasMany
    {
        return $this->hasMany(PersonalisedPlanRequest::class);
    }

    public function gymMemberships(): HasMany
    {
        return $this->hasMany(GymMembership::class);
    }

    public function gymCheckIns(): HasMany
    {
        return $this->hasMany(GymCheckIn::class);
    }

    public function gymClassBookings(): HasMany
    {
        return $this->hasMany(GymClassBooking::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function roleNotifications(): HasMany
    {
        return $this->hasMany(RoleNotification::class);
    }

    public function privacyRequests(): HasMany
    {
        return $this->hasMany(PrivacyRequest::class);
    }
}
