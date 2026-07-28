<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Note: model events stay enabled on purpose — orders derive their
     * order_no and articles their slug from `booted()` hooks.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
