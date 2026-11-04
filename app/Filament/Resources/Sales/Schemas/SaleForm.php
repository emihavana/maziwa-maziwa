<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\SalePrice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('sale_date')
                    ->default(now())
                    ->required()
                    ->live()
                    ->unique(
                        table: 'sales',
                        column: 'sale_date',
                        ignoreRecord: true
                    )
                    ->afterStateUpdated(function ($state, $set) {
                        if (! empty($state)) {
                            $set('sale_price_per_liter', SalePrice::getPriceOnDate($state));
                        }
                    })
                    ->validationMessages([
                        'unique' => __('A sale entry already exists for this date. Please edit the existing entry from the table.'),
                    ]),

                TextInput::make('qty')
                    ->label('Quantity (Liters)')
                    ->required()
                    ->numeric(),

                TextInput::make('sale_price_per_liter')
                    ->label('Price Per Liter')
                    ->numeric()
                    ->required()
                    ->default(fn (Get $get) => $get('sale_date')
                        ? SalePrice::getPriceOnDate($get('sale_date'))
                        : 0.00),
            ]);
    }
}
