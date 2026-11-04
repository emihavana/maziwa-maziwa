<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('username'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('role'),
                TextEntry::make('name'),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime(),
                IconEntry::make('is_active')
                    ->boolean()
                    ->placeholder('-'),
                // TextEntry::make('failed_attempts')
                //     ->numeric()
                //     ->placeholder('-'),
                // TextEntry::make('last_login')
                //     ->dateTime()
                //     ->placeholder('-'),
                // TextEntry::make('failed_login_attempts')
                //     ->numeric()
                //     ->placeholder('-'),
                // TextEntry::make('last_login_attempt')
                //     ->dateTime()
                //     ->placeholder('-'),
                // TextEntry::make('account_locked_until')
                //     ->dateTime()
                //     ->placeholder('-'),
                // TextEntry::make('email_verified_at')
                //     ->dateTime()
                //     ->placeholder('-'),
            ]);
    }
}
