<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            DefaultSettingsSeeder::class,
        ]);

        // SampleProductsSeeder tidak dijalankan otomatis (bisa penuh data
        // dummy di production). Jalankan manual saat butuh, mis. demo/dev:
        //   php artisan db:seed --class=SampleProductsSeeder
    }
}
