<?php

namespace App\Filament\Resources\SalePrices;

use App\Filament\Resources\SalePrices\Pages\CreateSalePrice;
use App\Filament\Resources\SalePrices\Pages\EditSalePrice;
use App\Filament\Resources\SalePrices\Pages\ListSalePrices;
use App\Filament\Resources\SalePrices\Pages\ViewSalePrice;
use App\Filament\Resources\SalePrices\Schemas\SalePriceForm;
use App\Filament\Resources\SalePrices\Schemas\SalePriceInfolist;
use App\Filament\Resources\SalePrices\Tables\SalePricesTable;
use App\Models\SalePrice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SalePriceResource extends Resource
{
    protected static ?string $model = SalePrice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CurrencyDollar;

    protected static ?string $recordTitleAttribute = 'SalePrice';

    public static function form(Schema $schema): Schema
    {
        return SalePriceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SalePriceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalePricesTable::configure($table);
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
            'index' => ListSalePrices::route('/'),
            'create' => CreateSalePrice::route('/create'),
            'view' => ViewSalePrice::route('/{record}'),
            'edit' => EditSalePrice::route('/{record}/edit'),
        ];
    }
}
