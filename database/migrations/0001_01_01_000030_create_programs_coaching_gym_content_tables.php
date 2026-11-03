<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coaching_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 140);
            $table->string('slug', 160)->unique();
            $table->string('tagline', 190)->nullable();
            $table->unsignedSmallInteger('duration_days');
            $table->unsignedBigInteger('price'); // paisa
            $table->unsignedBigInteger('compare_at_price')->nullable();
            $table->string('billing_note', 190)->nullable();
            $table->unsignedTinyInteger('checkins_per_week')->default(1);
            $table->boolean('includes_custom_diet')->default(true);
            $table->boolean('includes_custom_workout')->default(true);
            $table->boolean('includes_video_library')->default(true);
            $table->boolean('includes_consultation')->default(true);
            $table->json('inclusions')->nullable();
            $table->text('who_is_it_for')->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('diet_plans', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->string('category', 80)->index();
            $table->string('type', 32)->default('paid')->index(); // free, paid, personalised
            $table->unsignedSmallInteger('duration_days')->default(28);
            $table->string('target_calories', 64)->nullable();
            $table->unsignedBigInteger('price')->default(0); // paisa
            $table->string('summary', 255);
            $table->longText('description')->nullable();
            $table->json('sample_day')->nullable();
            $table->json('inclusions')->nullable();
            $table->json('dietary_tags')->nullable();
            $table->text('disclaimer')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('workout_programs', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->string('category', 80)->index();
            $table->string('level', 40)->default('Intermediate');
            $table->unsignedTinyInteger('duration_weeks')->default(8);
            $table->unsignedTinyInteger('days_per_week')->default(4);
            $table->string('equipment_required', 120)->default('Full Gym');
            $table->unsignedBigInteger('price')->default(0);
            $table->string('summary', 255);
            $table->json('structure')->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->unsignedSmallInteger('duration_days');
            $table->unsignedBigInteger('price'); // paisa
            $table->unsignedBigInteger('registration_fee')->default(0);
            $table->string('access_hours', 120)->default('All Operating Hours');
            $table->json('inclusions')->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('opening_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_of_week')->unique(); // 1=Mon ... 7=Sun
            $table->string('day_name', 24);
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->string('ladies_only_window', 80)->nullable();
            $table->string('peak_window', 80)->nullable();
            $table->boolean('is_closed')->default(false);
            $table->string('note', 190)->nullable();
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->string('category', 80)->index();
            $table->string('access', 24)->default('public')->index(); // public, entitled
            $table->unsignedSmallInteger('duration_seconds')->default(180);
            $table->string('coach_note', 190)->nullable();
            $table->text('summary')->nullable();
            $table->string('thumbnail_path', 255)->nullable();
            $table->string('provider', 32)->default('external_private');
            $table->string('provider_ref', 190)->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('group', 64)->index();
            $table->string('question', 255);
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true)->index();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('display_name', 120);
            $table->string('context_label', 120)->nullable();
            $table->string('business_line', 32)->default('coaching');
            $table->text('quote');
            $table->foreignId('consent_record_id')->constrained('consent_records')->restrictOnDelete();
            $table->string('verification_note', 190);
            $table->string('status', 24)->default('draft')->index(); // draft, published
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('transformations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('consent_record_id')->constrained('consent_records')->restrictOnDelete();
            $table->string('title', 160);
            $table->unsignedTinyInteger('duration_weeks');
            $table->text('summary');
            $table->string('before_image_path', 255)->nullable();
            $table->string('after_image_path', 255)->nullable();
            $table->string('status', 24)->default('draft')->index(); // draft, published
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->default('general')->index(); // general, gym_trial, coaching, wholesale
            $table->string('name', 120);
            $table->string('email', 160)->nullable();
            $table->string('phone', 32);
            $table->string('city', 80)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 24)->default('new')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('seo_metadata', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');
            $table->string('title', 160)->nullable();
            $table->string('description', 320)->nullable();
            $table->string('canonical_url', 255)->nullable();
            $table->string('og_image', 255)->nullable();
            $table->string('robots', 64)->default('index,follow');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_metadata');
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('transformations');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('opening_hours');
        Schema::dropIfExists('membership_plans');
        Schema::dropIfExists('workout_programs');
        Schema::dropIfExists('diet_plans');
        Schema::dropIfExists('coaching_packages');
    }
};
