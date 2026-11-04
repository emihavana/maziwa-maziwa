<?php

namespace App\Filament\Resources\FarmerDebtTransactions\Pages;

use App\Filament\Resources\FarmerDebtTransactions\FarmerDebtTransactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFarmerDebtTransaction extends ViewRecord
{
    protected static string $resource = FarmerDebtTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
