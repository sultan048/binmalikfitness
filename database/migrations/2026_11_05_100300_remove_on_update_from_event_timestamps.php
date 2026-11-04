<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * On MariaDB/MySQL with explicit_defaults_for_timestamp=OFF (XAMPP's default and many cPanel hosts),
 * a NOT NULL `timestamp` column created without a default silently becomes
 * "DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP". Every later update of the row then
 * overwrote these event times (e.g. changing a consultation's status reset scheduled_at,
 * withdrawing consent reset granted_at). This keeps the default but drops ON UPDATE.
 */
return new class extends Migration
{
    private array $columns = [
        'coach_assignments' => 'starts_at',
        'consent_records' => 'granted_at',
        'coaching_consultations' => 'scheduled_at',
        'gym_check_ins' => 'checked_in_at',
        'gym_class_bookings' => 'booked_at',
    ];

    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach ($this->columns as $table => $column) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }
    }

    public function down(): void
    {
        // Intentionally not restoring ON UPDATE CURRENT_TIMESTAMP: it corrupted event times.
    }
};
