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
                TextColumn::make('farmer_id')
                ->searchable(),
                TextColumn::make('name')
                    ->searchable(isIndividual: true),
                
                
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