<?php

namespace App\Filament\Resources\Farmers\Schemas;

use App\models\Route;
use App\Models\Farmer;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class FarmerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //TextInput::make('farmer_id')
                //  ->required(),
                TextInput::make('name')
                    ->required(),
                Select::make('route_id')
                    ->options(Route::all()->pluck("name", "id"))
                    ->required(),
                // ->numeric(),
                TextInput::make('phone')
                    ->tel()
                    ->unique(ignorable: fn($record) => $record)
                    ->validationMessages([
                        'unique' => 'This phone number is already registered to another farmer.',
                    ]),
                // TextInput::make('debt_balance')
                //->required()
                //  ->numeric()
                //->default(0.0),
                TextInput::make('national_id')
                    ->unique(ignorable: fn($record) => $record)
                    ->validationMessages([
                        'unique' => 'This National ID number has already been taken.',
                    ]),
                // Textarea::make('address')
                // ->columnSpanFull(),
                //TextInput::make('account_balance')
                // ->numeric()
                // ->default(0.0),
                // Select::make('payment_frequency')
                //    ->options(['weekly' => 'Weekly', 'monthly' => 'Monthly', 'daily' => 'Daily'])
                //   ->default('monthly'),
            ]);
    }
}
