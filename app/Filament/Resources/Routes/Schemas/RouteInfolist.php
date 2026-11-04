<?php

namespace App\Filament\Resources\Routes\Schemas;

use App\Models\Route;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RouteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),

                TextEntry::make('current_milk_price')
                    ->label('Current Milk Price')
                    ->state(fn(Route $record): string => 'KES ' . number_format($record->milkPrices()->where('is_active', true)->latest('effective_date')->value('price_per_liter') ?? 0, 2))
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->badge(),

                TextEntry::make('farmers_count')
                    ->label('Total Farmers')
                    ->state(fn(Route $record): int => $record->farmers()->count())
                    ->icon('heroicon-o-users')
                    ->color('primary')
                    ->badge(),

                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}
