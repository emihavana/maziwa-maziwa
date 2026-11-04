<?php

namespace App\Filament\Resources\MilkPrices\Pages;

use App\Filament\Resources\MilkPrices\MilkPriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMilkPrices extends ListRecords
{
    protected static string $resource = MilkPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
