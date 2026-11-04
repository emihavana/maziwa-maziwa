<?php

namespace Database\Seeders;

use App\Models\SalePrice;
use Illuminate\Database\Seeder;

class SalePriceSeeder extends Seeder
{
    public function run(): void
    {
        $priceRecords = [
            ['date' => now()->subMonths(2)->startOfMonth()->toDateString(), 'price' => 58.00],
            ['date' => now()->subMonth()->startOfMonth()->toDateString(), 'price' => 62.00],
            ['date' => now()->startOfMonth()->toDateString(), 'price' => 65.00],
        ];

        foreach ($priceRecords as $record) {
            SalePrice::updateOrCreate(
                [
                    'effective_date' => $record['date'],
                ],
                [
                    'price_per_liter' => $record['price'],
                    'is_active' => $record['date'] === now()->startOfMonth()->toDateString(),
                ]
            );
        }
    }
}
