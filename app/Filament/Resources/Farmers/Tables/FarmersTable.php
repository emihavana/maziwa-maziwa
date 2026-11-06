<?php

namespace App\Filament\Resources\Farmers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FarmersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultGroup("route.name")
            ->persistColumnSearchesInSession()
            ->columns([
                //TextColumn::make('farmer_id')
                //>searchable(),
                TextColumn::make('name')
                    ->searchable(isIndividual: true),
                //TextColumn::make('route.name')
                //->numeric()
                //>sortable(),
                //extColumn::make('phone')
                //>searchable(isIndividual: true),
                TextColumn::make('current_month_debt')
                    ->numeric()
                    ->sortable(),
                // TextColumn::make('national_id')
                //->searchable(),
                TextColumn::make('account_balance')
                    ->label('Gross Balance')
                    ->numeric()
                    ->sortable(),
                //  TextColumn::make('payment_frequency')
                //   ->badge(),
                // TextColumn::make('created_at')
                // ->dateTime()
                //  ->sortable()
                // ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                // ← added
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
