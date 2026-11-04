<?php

namespace App\Filament\Resources\FarmerDebtTransactions\Pages;

use App\Filament\Resources\FarmerDebtTransactions\FarmerDebtTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFarmerDebtTransactions extends ListRecords
{
    protected static string $resource = FarmerDebtTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
