<?php

namespace App\Filament\Resources\Farmers\Pages;

use App\Filament\Resources\Farmers\FarmerResource;
use App\Models\Farmer_debt_transaction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewFarmer extends ViewRecord
{
    protected static string $resource = FarmerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addDebt')
                ->label('Add Debt')
                ->icon('heroicon-o-plus-circle')
                ->color('danger')
                ->schema([
                    TextInput::make('amount')
                        ->numeric()
                        ->required()
                        ->prefix('KES'),
                    TextInput::make('reason')
                        ->required(),
                    DatePicker::make('transaction_date')
                        ->required()
                        ->default(now()),
                ])
                ->action(function (array $data) {
                    Farmer_debt_transaction::create([
                        'farmer_id' => $this->record->farmer_id,
                        'amount' => $data['amount'],
                        'transaction_type' => 'debt',
                        'reason' => $data['reason'],
                        'transaction_date' => $data['transaction_date'],
                    ]);

                    Notification::make()
                        ->title('Debt added successfully')
                        ->success()
                        ->send();
                }),

            EditAction::make(),
        ];
    }
}
