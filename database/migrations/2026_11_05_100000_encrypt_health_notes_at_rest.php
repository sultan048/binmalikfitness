<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Encrypts existing plaintext health notes now that the models cast these columns as `encrypted`.
 * Values that already decrypt are left alone, so the migration is safe to re-run.
 */
return new class extends Migration
{
    private array $columns = [
        'coaching_intakes' => ['allergies', 'dietary_restrictions', 'reviewer_notes'],
        'coaching_check_ins' => ['client_notes', 'coach_feedback'],
    ];

    public function up(): void
    {
        foreach ($this->columns as $table => $columns) {
            DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table, $columns) {
                foreach ($rows as $row) {
                    $updates = [];
                    foreach ($columns as $column) {
                        $value = $row->{$column};
                        if ($value === null || $value === '' || $this->isEncrypted($value)) {
                            continue;
                        }
                        $updates[$column] = Crypt::encryptString($value);
                    }

                    if ($updates) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $table => $columns) {
            DB::table($table)->orderBy('id')->chunkById(200, function ($rows) use ($table, $columns) {
                foreach ($rows as $row) {
                    $updates = [];
                    foreach ($columns as $column) {
                        $value = $row->{$column};
                        if ($value !== null && $value !== '' && $this->isEncrypted($value)) {
                            $updates[$column] = Crypt::decryptString($value);
                        }
                    }

                    if ($updates) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });
        }
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
