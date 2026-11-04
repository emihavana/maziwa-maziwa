<?php

namespace App\Filament\Resources\SalePrices\Schemas;

use App\Models\SalePrice;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SalePriceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('price_per_liter')
                    ->label('Price per Liter')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                DatePicker::make('effective_date')
                    ->label('Effective Date')
                    ->required()
                    ->live()
                    ->helperText(function (Get $get, ?\Illuminate\Database\Eloquent\Model $record) {
                        $date = $get('effective_date');

                        if (! $date) {
                            return 'Enter the date the factory rate becomes effective.';
                        }

                        if (SalePrice::hasDuplicateEffectiveDate($date, $record?->getKey())) {
                            return 'Warning: a sale price already exists for this effective date. Please review before continuing.';
                        }

                        return 'This rate will apply from the selected date onward.';
                    })
                    ->hintIcon('heroicon-o-exclamation-triangle', function (Get $get, ?\Illuminate\Database\Eloquent\Model $record) {
                        $date = $get('effective_date');

                        if (! $date) {
                            return null;
                        }

                        return SalePrice::hasDuplicateEffectiveDate($date, $record?->getKey())
                            ? 'Duplicate date warning'
                            : null;
                    }),
                Checkbox::make('confirm_duplicate_date')
                    ->label('I understand this effective date already exists and want to continue')
                    ->helperText('This is a deliberate duplicate-date override for a late or updated factory price change.')
                    ->visible(function (Get $get, ?\Illuminate\Database\Eloquent\Model $record) {
                        $date = $get('effective_date');

                        if (! $date) {
                            return false;
                        }

                        return SalePrice::hasDuplicateEffectiveDate($date, $record?->getKey());
                    })
                    ->required(function (Get $get, ?\Illuminate\Database\Eloquent\Model $record) {
                        $date = $get('effective_date');

                        if (! $date) {
                            return false;
                        }

                        return SalePrice::hasDuplicateEffectiveDate($date, $record?->getKey());
                    })
                    ->rules(['accepted']),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
