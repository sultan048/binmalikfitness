<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'business_line')) {
                $table->string('business_line', 32)->default('shop')->after('order_number')->index();
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'business_line')) {
                $table->string('business_line', 32)->default('shop')->after('order_id')->index();
                $table->string('purchasable_type', 120)->nullable()->after('product_variant_id');
                $table->unsignedBigInteger('purchasable_id')->nullable()->after('purchasable_type');
                $table->index(['purchasable_type', 'purchasable_id'], 'order_items_purchasable_idx');
            }
        });

        Schema::create('diet_plan_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diet_plan_id')->constrained('diet_plans')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('version_number')->default(1);
            $table->string('title', 160);
            $table->unsignedSmallInteger('daily_calories')->default(2000);
            $table->unsignedSmallInteger('protein_grams')->default(160);
            $table->unsignedSmallInteger('carbs_grams')->default(180);
            $table->unsignedSmallInteger('fat_grams')->default(60);
            $table->decimal('hydration_target_liters', 3, 1)->default(3.5);
            $table->json('meal_schedule');
            $table->json('substitutions');
            $table->json('shopping_list');
            $table->text('coach_notes')->nullable();
            $table->string('revision_note', 255)->nullable();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('coaching_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_number', 40)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('coaching_package_id')->constrained('coaching_packages')->restrictOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('latest_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('status', 32)->default('pending_payment')->index(); // pending_payment, active, paused, expired, cancelled, revoked
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('paused_at')->nullable();
            $table->unsignedSmallInteger('pause_days_used')->default(0);
            $table->unsignedSmallInteger('max_pause_days')->default(14);
            $table->string('pause_reason', 255)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->timestamp('renewal_reminder_sent_at')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->text('coach_summary_notes')->nullable();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('coaching_intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('coaching_subscription_id')->nullable()->constrained('coaching_subscriptions')->nullOnDelete();
            $table->foreignId('diet_plan_id')->nullable()->constrained('diet_plans')->nullOnDelete();
            $table->string('fitness_goal', 80);
            $table->unsignedTinyInteger('age');
            $table->unsignedSmallInteger('height_cm');
            $table->decimal('weight_kg', 5, 2);
            $table->string('activity_level', 64);
            $table->string('training_experience', 64);
            $table->text('food_preferences');
            $table->text('allergies')->nullable();
            $table->text('dietary_restrictions')->nullable();
            $table->string('equipment_availability', 120);
            $table->string('preferred_schedule', 160);
            $table->boolean('health_consent_granted')->default(false);
            $table->foreignId('consent_record_id')->nullable()->constrained('consent_records')->nullOnDelete();
            $table->text('health_notes_encrypted')->nullable();
            $table->boolean('needs_professional_review')->default(false)->index();
            $table->string('review_flag_reason', 255)->nullable();
            $table->string('review_status', 32)->default('pending_review')->index(); // pending_review, flagged_medical, cleared, plan_delivered
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('personalised_plan_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('diet_plan_id')->constrained('diet_plans')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('coaching_intake_id')->nullable()->constrained('coaching_intakes')->nullOnDelete();
            $table->foreignId('assigned_coach_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delivered_version_id')->nullable()->constrained('diet_plan_versions')->nullOnDelete();
            $table->string('status', 32)->default('awaiting_payment')->index(); // awaiting_payment, awaiting_intake, in_preparation, medical_hold, delivered, revision_requested
            $table->text('customer_revision_notes')->nullable();
            $table->json('custom_substitution_overrides')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('entitleable_type', 120);
            $table->unsignedBigInteger('entitleable_id');
            $table->string('source_type', 120);
            $table->unsignedBigInteger('source_id');
            $table->string('status', 24)->default('active')->index(); // active, expired, revoked
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('revoke_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'entitleable_type', 'entitleable_id'], 'entitlements_user_target_idx');
            $table->unique(
                ['user_id', 'entitleable_type', 'entitleable_id', 'source_type', 'source_id'],
                'entitlements_unique_source_grant'
            );
        });

        Schema::create('workout_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('coaching_subscription_id')->nullable()->constrained('coaching_subscriptions')->nullOnDelete();
            $table->foreignId('workout_program_id')->constrained('workout_programs')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title_override', 160)->nullable();
            $table->json('scheduled_days')->nullable();
            $table->text('custom_guidance')->nullable();
            $table->unsignedTinyInteger('target_sessions_per_week')->default(4);
            $table->unsignedSmallInteger('completed_sessions_count')->default(0);
            $table->timestamp('last_completed_at')->nullable();
            $table->json('last_session_log')->nullable();
            $table->string('status', 24)->default('active')->index(); // active, completed, archived
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
        });

        Schema::create('coaching_check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('coaching_subscription_id')->nullable()->constrained('coaching_subscriptions')->nullOnDelete();
            $table->date('check_in_date')->index();
            $table->decimal('weight_kg', 5, 2);
            $table->decimal('waist_cm', 5, 1)->nullable();
            $table->decimal('chest_cm', 5, 1)->nullable();
            $table->decimal('hips_cm', 5, 1)->nullable();
            $table->decimal('arms_cm', 5, 1)->nullable();
            $table->decimal('thighs_cm', 5, 1)->nullable();
            $table->decimal('body_fat_pct', 4, 1)->nullable();
            $table->decimal('sleep_hours', 3, 1)->nullable();
            $table->decimal('water_liters', 3, 1)->nullable();
            $table->unsignedInteger('step_count')->nullable();
            $table->unsignedTinyInteger('diet_adherence_score')->default(8);
            $table->unsignedTinyInteger('energy_score')->default(8);
            $table->unsignedTinyInteger('workouts_completed_this_week')->default(0);
            $table->text('client_notes')->nullable();
            $table->foreignId('progress_photo_file_id')->nullable()->constrained('private_files')->nullOnDelete();
            $table->foreignId('photo_consent_record_id')->nullable()->constrained('consent_records')->nullOnDelete();
            $table->boolean('is_public_display_consented')->default(false);
            $table->text('coach_feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('status', 24)->default('submitted')->index(); // submitted, reviewed
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('coaching_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('coaching_subscription_id')->nullable()->constrained('coaching_subscriptions')->nullOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at')->index();
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('channel', 32)->default('whatsapp_video'); // whatsapp_video, zoom, in_person_gym
            $table->string('topic', 180);
            $table->text('client_notes')->nullable();
            $table->text('coach_summary')->nullable();
            $table->string('meeting_reference', 190)->nullable();
            $table->string('status', 24)->default('requested')->index(); // requested, confirmed, completed, cancelled
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('coaching_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('coaching_subscription_id')->nullable()->constrained('coaching_subscriptions')->nullOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->foreignId('attachment_private_file_id')->nullable()->constrained('private_files')->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();

            $table->index(['client_id', 'created_at'], 'coaching_messages_client_thread_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coaching_messages');
        Schema::dropIfExists('coaching_consultations');
        Schema::dropIfExists('coaching_check_ins');
        Schema::dropIfExists('workout_assignments');
        Schema::dropIfExists('entitlements');
        Schema::dropIfExists('personalised_plan_requests');
        Schema::dropIfExists('coaching_intakes');
        Schema::dropIfExists('coaching_subscriptions');
        Schema::dropIfExists('diet_plan_versions');

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'business_line')) {
                $table->dropIndex('order_items_purchasable_idx');
                $table->dropColumn(['business_line', 'purchasable_type', 'purchasable_id']);
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'business_line')) {
                $table->dropColumn('business_line');
            }
        });
    }
};
