<?php

namespace App\Filament\Resources\FarmerDebtTransactions\Pages;

use App\Filament\Resources\FarmerDebtTransactions\FarmerDebtTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFarmerDebtTransaction extends EditRecord
{
    protected static string $resource = FarmerDebtTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
