<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->searchable(),
                //TextColumn::make('email')
                //->label('Email address')
                //->searchable(),
                TextColumn::make('role')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->boolean(),
                //TextColumn::make('failed_attempts')
                //->numeric()
                // ->sortable(),
                //TextColumn::make('last_login')
                //->dateTime()
                // ->sortable(),
                //  TextColumn::make('failed_login_attempts')
                //   ->numeric()
                //   ->sortable(),
                // TextColumn::make('last_login_attempt')
                //    ->dateTime()
                //     ->sortable(),
                //TextColumn::make('account_locked_until')
                // ->dateTime()
                //->sortable(),
                // TextColumn::make('email_verified_at')
                // ->dateTime()
                //->sortable(),
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