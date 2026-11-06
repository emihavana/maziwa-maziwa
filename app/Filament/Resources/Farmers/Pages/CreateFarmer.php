<?php

namespace App\Filament\Resources\Farmers\Pages;

use App\Filament\Resources\Farmers\FarmerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFarmer extends CreateRecord
{
    protected static string $resource = FarmerResource::class;
    protected function getCreatedNotificationMessage(): ?string
    {
        return 'Farmer created successfully!';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
