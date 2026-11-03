<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        if (! app()->isProduction()) {
            $this->call(DemoCatalogSeeder::class);
        }
    }
}
