<?php

namespace Database\Seeders;

use App\Models\Farmer;
use App\Models\MilkCollection;
use App\Models\User;
use Illuminate\Database\Seeder;

class MilkCollectionSeeder extends Seeder
{
    public function run(): void
    {
        $collector = User::where('role', 'collector')->first();

        if (! $collector) {
            return;
        }

        $dates = [
            now()->startOfMonth()->addDays(2)->toDateString(),
            now()->startOfMonth()->addDays(6)->toDateString(),
            now()->startOfMonth()->addDays(10)->toDateString(),
            now()->startOfMonth()->addDays(15)->toDateString(),
            now()->startOfMonth()->addDays(20)->toDateString(),
            now()->startOfMonth()->addDays(25)->toDateString(),
        ];

        foreach (Farmer::all() as $index => $farmer) {
            $selectedDates = array_slice($dates, 0, (int) (($index % 3) + 2));

            foreach ($selectedDates as $day) {
                MilkCollection::updateOrCreate(
                    [
                        'farmer_id' => $farmer->farmer_id,
                        'collection_date' => $day,
                    ],
                    [
                        'collector_id' => $collector->id,
                        'route_id' => $farmer->route_id,
                        'qty' => round(18 + (($index * 7) % 24) + (rand(0, 20) / 10), 2),
                        'is_correction' => false,
                        'correction_reason' => null,
                        'corrected_by' => null,
                    ]
                );
            }
        }
    }
}
