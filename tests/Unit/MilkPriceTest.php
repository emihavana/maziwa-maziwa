<?php

namespace Tests\Unit;

use App\Models\MilkPrice;
use App\Models\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MilkPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_the_price_effective_on_a_specific_date(): void
    {
        $route = Route::create(['name' => 'Route A']);

        MilkPrice::create([
            'route_id' => $route->id,
            'price_per_liter' => 30,
            'effective_date' => '2024-01-01',
            'is_active' => true,
        ]);

        MilkPrice::create([
            'route_id' => $route->id,
            'price_per_liter' => 35,
            'effective_date' => '2024-02-01',
            'is_active' => true,
        ]);

        $this->assertSame(30.0, MilkPrice::getPriceOnDate($route->id, '2024-01-15'));
        $this->assertSame(35.0, MilkPrice::getPriceOnDate($route->id, '2024-02-15'));
        $this->assertSame(35.0, MilkPrice::getPriceOnDate($route->id, '2024-03-10'));
        $this->assertSame(0.0, MilkPrice::getPriceOnDate($route->id, '2023-12-15'));
    }
}
