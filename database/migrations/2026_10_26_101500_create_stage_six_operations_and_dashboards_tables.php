<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 40)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('category', 32)->default('general')->index();
            $table->string('business_line', 32)->default('general')->index();
            $table->string('priority', 24)->default('normal')->index();
            $table->string('subject', 180);
            $table->string('status', 24)->default('open')->index();
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_internal_note')->default(false);
            $table->timestamps();
        });

        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->string('section', 64)->default('policy')->index();
            $table->string('excerpt', 300)->nullable();
            $table->longText('body');
            $table->string('status', 24)->default('published')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('collection', 64)->default('general')->index();
            $table->string('disk', 32)->default('public');
            $table->string('path', 255);
            $table->string('mime_type', 80)->default('image/png');
            $table->string('alt_text', 255);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name', 140);
            $table->string('channel', 32)->default('database');
            $table->string('subject', 190)->nullable();
            $table->text('body_template');
            $table->json('placeholders')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('role_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('role_scope', 32)->nullable()->index();
            $table->string('type', 80)->default('general')->index();
            $table->string('title', 160);
            $table->string('body', 500);
            $table->string('action_url', 255)->nullable();
            $table->string('severity', 24)->default('info');
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('privacy_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('request_type', 40)->index();
            $table->text('customer_notes')->nullable();
            $table->string('status', 24)->default('pending')->index();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->string('resolution_notes', 500)->nullable();
            $table->timestamps();
        });

        Schema::table('consent_records', function (Blueprint $table) {
            if (! Schema::hasColumn('consent_records', 'consent_type')) {
                $table->string('consent_type', 64)->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('consent_records', 'version')) {
                $table->string('version', 24)->default('v1.0')->after('type');
            }
        });

        Schema::table('testimonials', function (Blueprint $table) {
            if (! Schema::hasColumn('testimonials', 'author_label')) {
                $table->string('author_label', 120)->nullable()->after('id');
            }
            if (! Schema::hasColumn('testimonials', 'program_context')) {
                $table->string('program_context', 120)->nullable()->after('author_label');
            }
            if (! Schema::hasColumn('testimonials', 'rating')) {
                $table->unsignedTinyInteger('rating')->default(5)->after('quote');
            }
        });

        Schema::table('seo_metadata', function (Blueprint $table) {
            if (! Schema::hasColumn('seo_metadata', 'meta_title')) {
                $table->string('meta_title', 160)->nullable()->after('seoable_id');
            }
            if (! Schema::hasColumn('seo_metadata', 'meta_description')) {
                $table->string('meta_description', 320)->nullable()->after('meta_title');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_requests');
        Schema::dropIfExists('role_notifications');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('cms_pages');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
