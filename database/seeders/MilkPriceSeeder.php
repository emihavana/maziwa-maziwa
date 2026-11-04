<?php

namespace Database\Seeders;

use App\Models\MilkPrice;
use App\Models\Route;
use Illuminate\Database\Seeder;

class MilkPriceSeeder extends Seeder
{
    public function run(): void
    {
        $routes = Route::all();

        if ($routes->isEmpty()) {
            return;
        }

        foreach ($routes as $route) {
            $base = 32 + ($route->id * 3);
            $effectiveDates = [
                now()->subMonths(2)->startOfMonth(),
                now()->subMonth()->startOfMonth(),
                now()->startOfMonth(),
            ];
            $prices = [$base, $base + 4, $base + 8];

            foreach ($effectiveDates as $index => $effectiveDate) {
                MilkPrice::updateOrCreate(
                    [
                        'route_id' => $route->id,
                        'effective_date' => $effectiveDate->toDateString(),
                    ],
                    [
                        'price_per_liter' => $prices[$index],
                        'is_active' => $index === 2,
                    ]
                );
            }
        }
    }
}
