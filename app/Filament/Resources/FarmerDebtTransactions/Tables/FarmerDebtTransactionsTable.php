<?php

namespace App\Filament\Resources\FarmerDebtTransactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FarmerDebtTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->join('farmers', 'farmer_debt_transactions.farmer_id', '=', 'farmers.farmer_id')
                    ->selectRaw('
                        MIN(farmer_debt_transactions.id) as id,
                        farmer_debt_transactions.farmer_id,
                        farmers.name as farmer_name,
                        SUM(farmer_debt_transactions.amount) as total_debt
                    ')
                    ->where('farmer_debt_transactions.transaction_type', 'debt')
                    ->whereMonth('farmer_debt_transactions.transaction_date', now()->month)
                    ->whereYear('farmer_debt_transactions.transaction_date', now()->year)
                    ->groupBy('farmer_debt_transactions.farmer_id', 'farmers.name')
            )
            ->defaultSort('farmer_name', 'asc')
            ->defaultKeySort(false)
            ->columns([
                TextColumn::make('farmer_name')
                    ->label('Farmer Name')
                    ->searchable(query: function (Builder $query, string $search) {
                        return $query->where('farmers.name', 'like', "%{$search}%");
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->orderBy('farmers.name', $direction);
                    }),

                TextColumn::make('total_debt')
                    ->label('Debt This Month')
                    ->money('KES')
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->orderBy('total_debt', $direction);
                    }),
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