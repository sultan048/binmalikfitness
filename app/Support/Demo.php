<?php

namespace App\Support;

/**
 * Single switch for demonstration features (one-click role switcher, demo accounts,
 * seeded sample catalogue, design-system page, demo badges).
 *
 * Demo features run only when DEMO_MODE=true AND the app is not in production, so a
 * production deployment can never expose them even if DEMO_MODE is left on by mistake.
 */
final class Demo
{
    public static function enabled(): bool
    {
        return (bool) config('app.demo_mode') && ! app()->isProduction();
    }
}
