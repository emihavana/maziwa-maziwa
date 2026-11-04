<?php

namespace App\Filament\Resources\MilkPrices\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MilkPriceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('route.name')
                    ->label('Route'),
                //->numeric()
                //->placeholder('-'),
                TextEntry::make('price_per_liter')
                    ->numeric(),
                TextEntry::make('effective_date')
                    ->date(),
                IconEntry::make('is_active')
                    ->boolean()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}
