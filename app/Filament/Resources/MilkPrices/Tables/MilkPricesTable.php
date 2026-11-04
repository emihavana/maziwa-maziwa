<?php

namespace App\Filament\Resources\MilkPrices\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MilkPricesTable
{
    public static function configure(Table $table): Table
    {
        $ranked = DB::table('milk_prices')
            ->select('id')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY route_id ORDER BY effective_date DESC, id DESC) as rn');

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($ranked) {
                return $query->whereIn('id', function ($sub) use ($ranked) {
                    $sub->select('id')->fromSub($ranked, 'ranked')->where('rn', '<=', 2);
                });
            })
            ->columns([
                TextColumn::make('route.name')
                    ->label('Route')
                    ->sortable(),
                TextColumn::make('price_per_liter')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('effective_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
