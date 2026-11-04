<?php

namespace App\Filament\Resources\FarmerDebtTransactions\Pages;

use App\Filament\Resources\FarmerDebtTransactions\FarmerDebtTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFarmerDebtTransaction extends CreateRecord
{
    protected static string $resource = FarmerDebtTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['transaction_type'] = 'debt';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
