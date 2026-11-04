<?php

namespace Database\Seeders;

use App\Models\Sale;
use App\Models\SalePrice;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $dates = [
            now()->startOfMonth()->addDays(1)->toDateString(),
            now()->startOfMonth()->addDays(5)->toDateString(),
            now()->startOfMonth()->addDays(9)->toDateString(),
            now()->startOfMonth()->addDays(12)->toDateString(),
            now()->startOfMonth()->addDays(18)->toDateString(),
            now()->startOfMonth()->addDays(22)->toDateString(),
            now()->startOfMonth()->addDays(27)->toDateString(),
        ];

        foreach ($dates as $index => $date) {
            $price = SalePrice::getPriceOnDate($date);

            Sale::updateOrCreate(
                [
                    'sale_date' => $date,
                ],
                [
                    'qty' => 120 + ($index * 30),
                    'sale_price_per_liter' => $price,
                ]
            );
        }
    }
}
