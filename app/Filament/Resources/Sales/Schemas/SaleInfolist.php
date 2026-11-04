<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('sale_date')
                    ->date(),
                TextEntry::make('qty')
                    ->numeric(),
                TextEntry::make('sale_price_per_liter')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('sale_price')
                    ->money()
                    ->placeholder('-'),
            ]);
    }
}
