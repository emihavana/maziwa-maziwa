<?php

namespace App\Filament\Resources\MilkPrices\Pages;

use App\Filament\Resources\MilkPrices\MilkPriceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMilkPrice extends CreateRecord
{
    protected static string $resource = MilkPriceResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
