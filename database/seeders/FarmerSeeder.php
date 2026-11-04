<?php

namespace Database\Seeders;

use App\Models\Farmer;
use App\Models\Route;
use Illuminate\Database\Seeder;

class FarmerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $routes = Route::query()->pluck('id', 'name')->all();
        $routeNames = array_keys($routes);
        $lastFarmerId = Farmer::query()->max('farmer_id');
        $nextNumber = $lastFarmerId ? (int) substr($lastFarmerId, 1) : 0;

        for ($i = 1; $i <= 100; $i++) {
            $routeName = $routeNames[($i - 1) % count($routeNames)];
            $nextNumber++;

            Farmer::create([
                'farmer_id' => 'F' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT),
                'name' => fake()->name(),
                'national_id' => str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'phone' => '+2547' . random_int(10000000, 99999999),
                'route_id' => $routes[$routeName],
                'debt_balance' => 0.00,
            ]);
        }
    }
}
