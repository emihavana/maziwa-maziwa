<?php

namespace App\Filament\Resources\SalePrices\Pages;

use App\Filament\Resources\SalePrices\SalePriceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalePrices extends ListRecords
{
    protected static string $resource = SalePriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
