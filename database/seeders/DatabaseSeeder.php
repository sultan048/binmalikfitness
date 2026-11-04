<?php

namespace Database\Seeders;

use App\Support\Demo;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        if (Demo::enabled()) {
            $this->call(DemoCatalogSeeder::class);
        }
    }
}
