<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($data['password'] ?? null)) {
            unset($data['password']);

            return $data;
        }

        $data['password'] = Hash::make($data['password']);

        return $data;
    }
}
