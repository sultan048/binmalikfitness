<?php

namespace App\Support;

final class Money
{
    /**
     * Format an integer amount stored in paisa (1 PKR = 100 paisa) into a human-readable PKR string.
     */
    public static function formatPaisa(?int $paisa, bool $withCurrency = true): string
    {
        if ($paisa === null) {
            return '';
        }

        $rupees = intdiv($paisa, 100);
        $formatted = number_format($rupees, 0, '.', ',');

        return $withCurrency ? 'PKR '.$formatted : $formatted;
    }

    /**
     * Convenience alias for formatPaisa().
     */
    public static function format(?int $paisa, bool $withCurrency = true): string
    {
        return self::formatPaisa($paisa, $withCurrency);
    }

    /**
     * Convert whole PKR rupees into paisa for database storage.
     */
    public static function fromRupees(int|float $rupees): int
    {
        return (int) round($rupees * 100);
    }

    /**
     * Convert paisa into whole PKR rupees.
     */
    public static function toRupees(?int $paisa): int
    {
        if ($paisa === null) {
            return 0;
        }

        return intdiv($paisa, 100);
    }
}
