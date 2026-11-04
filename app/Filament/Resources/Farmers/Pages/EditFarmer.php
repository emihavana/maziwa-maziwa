<?php

namespace App\Filament\Resources\Farmers\Pages;

use App\Filament\Resources\Farmers\FarmerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFarmer extends EditRecord
{
    protected static string $resource = FarmerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
