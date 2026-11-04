<?php

namespace App\Filament\Resources\SalePrices\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SalePriceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('price_per_liter')
                    ->numeric(),
                TextEntry::make('effective_date')
                    ->date(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}
