<?php

namespace App\Filament\Resources\MilkPrices\Schemas;

use App\Models\Route;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MilkPriceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // TextInput::make('route_id')
                //  ->numeric(),
                Select::make('route_id')
                    ->options(Route::all()->pluck("name", "id"))
                    ->required(),
                TextInput::make('price_per_liter')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                DatePicker::make('effective_date')
                    ->required(),
                Toggle::make('is_active'),
            ]);
    }
}
