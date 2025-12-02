<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Store;
use Illuminate\Support\Facades\Hash;


class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $store_id = Store::factory()->create([
            'name' => 'Dream Store',
            'address' => '',
            'phone' => '',
            'logo' => '',
            'currency' => '',
            'email' => ''
        ]);

        User::factory()->create([
            'name' => 'super User',
            'email' => 'k@k.com', 
            'store_id' => $store_id->id,
            'password' => Hash::make('password')
        ]);

        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);
    }
}
