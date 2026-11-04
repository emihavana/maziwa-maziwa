<?php

namespace App\Filament\Resources\MilkPrices\Pages;

use App\Filament\Resources\MilkPrices\MilkPriceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMilkPrice extends EditRecord
{
    protected static string $resource = MilkPriceResource::class;

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
