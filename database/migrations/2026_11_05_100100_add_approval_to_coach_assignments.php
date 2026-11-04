<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Coach assignments grant access to a client's health data, so assignments created by
 * non-admin staff wait for admin approval. Existing rows stay approved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coach_assignments', function (Blueprint $table) {
            $table->string('status', 16)->default('approved')->index()->after('context'); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('coach_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['status', 'approved_at']);
        });
    }
};
