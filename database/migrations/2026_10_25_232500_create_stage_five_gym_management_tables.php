<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_memberships', function (Blueprint $table) {
            $table->id();
            $table->string('membership_number', 48)->unique();
            $table->string('member_card_code', 48)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->constrained('membership_plans')->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('assigned_trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('pending_payment')->index(); // pending_payment, active, paused, expired, cancelled
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('last_check_in_at')->nullable();
            $table->unsignedInteger('total_visits_count')->default(0);
            $table->timestamp('expiry_reminder_sent_at')->nullable();
            $table->text('staff_notes')->nullable();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('gym_check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_membership_id')->constrained('gym_memberships')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('check_in_method', 24)->default('desk_manual'); // desk_manual, qr_staff_verified
            $table->string('qr_nonce', 80)->nullable()->unique(); // Replay protection on signed QR tokens
            $table->timestamp('checked_in_at')->index();
            $table->timestamp('checked_out_at')->nullable();
            $table->string('verification_status', 24)->default('admitted')->index(); // admitted, denied_expired, denied_hours
            $table->string('entry_note', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('gym_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('title', 140);
            $table->date('holiday_date')->unique();
            $table->boolean('is_closed')->default(true);
            $table->time('special_opens_at')->nullable();
            $table->time('special_closes_at')->nullable();
            $table->string('note', 255)->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('gym_classes', function (Blueprint $table) {
            $table->id();
            $table->string('title', 140);
            $table->string('slug', 160)->unique();
            $table->string('category', 80)->index();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('day_of_week')->index(); // 1=Mon ... 7=Sun
            $table->time('starts_at_time');
            $table->time('ends_at_time');
            $table->date('session_date')->nullable()->index();
            $table->string('room_or_zone', 80)->default('Main Strength Floor');
            $table->unsignedSmallInteger('capacity')->default(16);
            $table->unsignedSmallInteger('booked_count')->default(0);
            $table->boolean('requires_active_membership')->default(true);
            $table->string('status', 24)->default('scheduled')->index(); // scheduled, cancelled, completed
            $table->string('summary', 255)->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('gym_class_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_class_id')->constrained('gym_classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('gym_membership_id')->nullable()->constrained('gym_memberships')->nullOnDelete();
            $table->string('status', 24)->default('booked')->index(); // booked, attended, cancelled
            $table->timestamp('booked_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['gym_class_id', 'user_id'], 'gym_class_bookings_class_user_unique');
        });

        Schema::table('enquiries', function (Blueprint $table) {
            $table->date('preferred_trial_date')->nullable()->after('city');
            $table->string('preferred_time_window', 80)->nullable()->after('preferred_trial_date');
            $table->foreignId('interested_membership_plan_id')->nullable()->after('preferred_time_window')->constrained('membership_plans')->nullOnDelete();
            $table->text('staff_notes')->nullable()->after('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('interested_membership_plan_id');
            $table->dropColumn(['preferred_trial_date', 'preferred_time_window', 'staff_notes']);
        });

        Schema::dropIfExists('gym_class_bookings');
        Schema::dropIfExists('gym_classes');
        Schema::dropIfExists('gym_holidays');
        Schema::dropIfExists('gym_check_ins');
        Schema::dropIfExists('gym_memberships');
    }
};
