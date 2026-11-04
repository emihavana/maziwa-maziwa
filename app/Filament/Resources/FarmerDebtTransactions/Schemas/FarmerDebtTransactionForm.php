<?php

namespace App\Filament\Resources\FarmerDebtTransactions\Schemas;

use App\Models\Farmer;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FarmerDebtTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('farmer_id')
                    ->label('Farmer')
                    ->options(
                        Farmer::query()
                            ->orderBy('name')
                            ->pluck('name', 'farmer_id')
                            ->toArray()
                    )
                    ->searchable()
                    ->required(),
                TextInput::make('amount')
                    ->label('Debt Amount')
                    ->numeric()
                    ->prefix('KES')
                    ->required(),
                TextInput::make('reason')
                    ->required(),
                DatePicker::make('transaction_date')
                    ->required()
                    ->default(now()),
            ]);
    }
}
