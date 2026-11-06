<?php

namespace App\Filament\Resources\Farmers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FarmerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('farmer_id'),
                TextEntry::make('name'),
                TextEntry::make('route.name')
                    ->label('Route'),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('national_id')
                    ->placeholder('-'),
                TextEntry::make('current_month_debt')
                    ->numeric()
                    ->label('Current Month Debt')
                    ->weight('bold')
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success'),
                TextEntry::make('account_balance')
                    ->label('Gross Milk Earnings')
                    ->numeric(),

                TextEntry::make('net_payable')
                    ->label('Net Payout Balance')
                    ->numeric()
                    ->weight('bold')
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success'),
                TextEntry::make('payment_frequency')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('last_30_days_debt_history_table')
                    ->label('Debt History (Last 30 Days)')
                    ->columnSpanFull()
                    ->html(),
                TextEntry::make('last_30_days_milk_collection_history_table')
                    ->label('Milk Collection (Last 30 Days)')
                    ->columnSpanFull()
                    ->html(),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}
