<?php

namespace App\Filament\Resources\SalePrices\Pages;

use App\Filament\Resources\SalePrices\SalePriceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSalePrice extends ViewRecord
{
    protected static string $resource = SalePriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
