<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // ✅ Navigation and labels
    protected static ?string $navigationLabel = 'Collectors';
    protected static ?string $modelLabel = 'Collector';
    protected static ?string $pluralModelLabel = 'Collectors';

    // ✅ Use a string for the icon (valid Heroicon name)
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    // ✅ Record title attribute (column used for display in dropdowns)
    protected static ?string $recordTitleAttribute = 'name';

    /**
     * ✅ Filter query to show only users with role = 'collector'
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('role', 'collector');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}