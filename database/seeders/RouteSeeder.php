<?php

namespace Database\Seeders;

use App\Models\Route;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $routes = ['Route A', 'Route B', 'Route C', 'Route D'];

        foreach ($routes as $routeName) {
            Route::firstOrCreate([
                'name' => $routeName,
            ]);
        }
    }
}
