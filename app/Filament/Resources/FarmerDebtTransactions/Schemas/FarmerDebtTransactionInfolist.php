<?php

namespace App\Filament\Resources\FarmerDebtTransactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FarmerDebtTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('farmer.name')
                    ->label('Farmer'),
                TextEntry::make('amount')
                    ->money('KES'),
                TextEntry::make('reason'),
                TextEntry::make('transaction_date')
                    ->date(),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}
