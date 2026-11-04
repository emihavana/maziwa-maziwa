<?php

namespace Database\Seeders;

use App\Models\Farmer;
use App\Models\Farmer_debt_transaction;
use Illuminate\Database\Seeder;

class FarmerDebtTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $farmers = Farmer::query()->take(15)->get();

        foreach ($farmers as $index => $farmer) {
            Farmer_debt_transaction::updateOrCreate(
                [
                    'farmer_id' => $farmer->farmer_id,
                    'transaction_date' => now()->startOfMonth()->addDays($index * 4 + 2)->toDateString(),
                    'transaction_type' => 'debt',
                ],
                [
                    'amount' => 500 + ($index * 180),
                    'reason' => 'Feed advance / monthly debt',
                ]
            );
        }
    }
}
