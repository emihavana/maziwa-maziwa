<?php

namespace App\Filament\Resources\Routes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RouteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'A route with this name already exists.',
                    ]),
            ]);
    }
}
