<?php

namespace App\Filament\Resources\MilkPrices;

use App\Filament\Resources\MilkPrices\Pages\CreateMilkPrice;
use App\Filament\Resources\MilkPrices\Pages\EditMilkPrice;
use App\Filament\Resources\MilkPrices\Pages\ListMilkPrices;
use App\Filament\Resources\MilkPrices\Pages\ViewMilkPrice;
use App\Filament\Resources\MilkPrices\Schemas\MilkPriceForm;
use App\Filament\Resources\MilkPrices\Schemas\MilkPriceInfolist;
use App\Filament\Resources\MilkPrices\Tables\MilkPricesTable;
use App\Models\MilkPrice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MilkPriceResource extends Resource
{
    protected static ?string $model = MilkPrice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CurrencyDollar;

    protected static ?string $recordTitleAttribute = 'MilkPrice';

    public static function form(Schema $schema): Schema
    {
        return MilkPriceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MilkPriceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MilkPricesTable::configure($table);
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
            'index' => ListMilkPrices::route('/'),
            'create' => CreateMilkPrice::route('/create'),
            'view' => ViewMilkPrice::route('/{record}'),
            'edit' => EditMilkPrice::route('/{record}/edit'),
        ];
    }
}
