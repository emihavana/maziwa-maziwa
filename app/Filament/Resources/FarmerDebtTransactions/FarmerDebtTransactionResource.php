<?php

namespace App\Filament\Resources\FarmerDebtTransactions;

use App\Filament\Resources\FarmerDebtTransactions\Pages\CreateFarmerDebtTransaction;
use App\Filament\Resources\FarmerDebtTransactions\Pages\EditFarmerDebtTransaction;
use App\Filament\Resources\FarmerDebtTransactions\Pages\ListFarmerDebtTransactions;
use App\Filament\Resources\FarmerDebtTransactions\Pages\ViewFarmerDebtTransaction;
use App\Filament\Resources\FarmerDebtTransactions\Schemas\FarmerDebtTransactionForm;
use App\Filament\Resources\FarmerDebtTransactions\Schemas\FarmerDebtTransactionInfolist;
use App\Filament\Resources\FarmerDebtTransactions\Tables\FarmerDebtTransactionsTable;
use App\Models\Farmer_debt_transaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FarmerDebtTransactionResource extends Resource
{
    protected static ?string $model = Farmer_debt_transaction::class;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('transaction_type', 'debt')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $isGloballySearchable = false;

    public static function form(Schema $schema): Schema
    {
        return FarmerDebtTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FarmerDebtTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FarmerDebtTransactionsTable::configure($table)
            ->defaultSort('farmer_name', 'asc')
            ->defaultKeySort(false);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFarmerDebtTransactions::route('/'),
            'create' => CreateFarmerDebtTransaction::route('/create'),
            'view' => ViewFarmerDebtTransaction::route('/{record}'),
            'edit' => EditFarmerDebtTransaction::route('/{record}/edit'),
        ];
    }

    // public static function getRecordRouteBindingEloquentQuery(): Builder
    // {
    //     return parent::getRecordRouteBindingEloquentQuery()
    //         ->withoutGlobalScopes([
    //             SoftDeletingScope::class,
    //         ]);
    // }
}
