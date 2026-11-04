<?php

namespace App\Filament\Resources\SalePrices\Pages;

use App\Filament\Resources\SalePrices\SalePriceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSalePrice extends EditRecord
{
    protected static string $resource = SalePriceResource::class;

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
