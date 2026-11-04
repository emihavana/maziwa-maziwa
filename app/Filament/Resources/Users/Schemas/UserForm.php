<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->nullable(),
                TextInput::make('password')
                    ->password()
                    ->nullable(),
                TextInput::make('role')
                    ->required()
                    ->default('collector'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                Toggle::make('is_active'),
            ]);
    }
}