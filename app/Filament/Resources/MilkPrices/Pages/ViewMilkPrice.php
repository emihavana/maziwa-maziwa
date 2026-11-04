<?php

namespace App\Filament\Resources\MilkPrices\Pages;

use App\Filament\Resources\MilkPrices\MilkPriceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMilkPrice extends ViewRecord
{
    protected static string $resource = MilkPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
