<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Duplicates allowed: no unique rule here
                TextInput::make('username')
                    ->required(),

                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    // No two users can share an email. ignoreRecord lets a user
                    // save their own record without tripping the rule.
                    ->unique(ignoreRecord: true)
                    ->nullable(),

                TextInput::make('password')
                    ->password()
                    ->revealable()
                    // FIX: hash before saving (this was the missing piece that caused
                    // "This password does not use the Bcrypt algorithm")
                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                    // Only save when something was typed, so editing a user without
                    // touching this field keeps their existing password
                    ->dehydrated(fn($state) => filled($state))
                    // Required when creating, optional when editing
                    ->required(fn(string $operation) => $operation === 'create')
                    ->minLength(8),

                // Dropdown prevents typos like "colector" that would break role checks.
                // Make these values match what EnsureUserIsCollector / isAdmin() expect.
                Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'collector' => 'Collector',
                    ])
                    ->required()
                    ->default('collector'),

                TextInput::make('name')
                    ->required(),

                TextInput::make('phone')
                    ->tel(),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
