<?php

namespace App\Filament\Resources\SalePrices\Pages;

use App\Filament\Resources\SalePrices\SalePriceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalePrice extends CreateRecord
{
    protected static string $resource = SalePriceResource::class;
    protected function getCreatedNotificationMessage(): ?string
    {
        return 'Sale Price recorded successfully!';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
