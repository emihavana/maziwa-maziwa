<?php

namespace App\Filament\Pages;

use App\Models\Route as RouteModel;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            // button for Farmer Report Action
            Action::make('downloadFarmerReport')
                ->label('Download farmer report')
                ->icon('heroicon-o-arrow-down-tray')
                ->modalHeading('Farmer report (PDF)')
                ->modalSubmitActionLabel('Download')
                ->form([
                    DatePicker::make('from')
                        ->label('From')
                        ->required()
                        ->default(now()->startOfMonth())
                        ->maxDate(now()),
                    DatePicker::make('to')
                        ->label('To')
                        ->required()
                        ->default(now())
                        ->afterOrEqual('from')
                        ->maxDate(now()),
                    Select::make('routes')
                        ->label('Routes')
                        ->helperText('Leave empty to include all routes.')
                        ->multiple()
                        ->options(fn() => RouteModel::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),
                    Select::make('sort_by')
                        ->label('Sort by')
                        ->options([
                            'name'      => 'Farmer name',
                            'route'     => 'Route',
                            'total_qty' => 'Total litres',
                            'debt'      => 'Debt',
                            'net'       => 'Net pay',
                        ])
                        ->default('name')
                        ->required(),
                    Select::make('direction')
                        ->label('Order')
                        ->options([
                            'asc'  => 'Ascending',
                            'desc' => 'Descending',
                        ])
                        ->default('asc')
                        ->required(),
                ])
                ->action(function (array $data, $livewire) {
                    $livewire->redirect(route('reports.farmers', $data));
                }),

            // button for Sales Report Action
            Action::make('downloadSalesOverview')
                ->label('Download sales report')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->modalHeading('Sales Overview Report (PDF)')
                ->modalSubmitActionLabel('Download PDF')
                ->form([
                    DatePicker::make('from')
                        ->label('From')
                        ->required()
                        ->default(now()->startOfMonth())
                        ->maxDate(now()),
                    DatePicker::make('to')
                        ->label('To')
                        ->required()
                        ->default(now())
                        ->afterOrEqual('from')
                        ->maxDate(now()),
                ])
                ->action(function (array $data, $livewire) {
                    $livewire->redirect(route('reports.sales', $data));
                            }),
            Action::make('executeMpesaPayouts')
                    ->label('Run B2C Payouts')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Execute End-Month M-Pesa Payouts')
                    ->modalDescription('This will compute net balances for the previous month and dispatch live M-Pesa B2C transfers to eligible farmers. Proceed?')
                    ->modalSubmitActionLabel('Yes, Send Payouts')
                    ->action(function ($livewire) {
                        return redirect()->to(route('payouts.execute'));
                    }),
            // --- NEW: BUTTON FOR M-PESA PAYOUT REPORT ACTION ---
            Action::make('downloadMpesaPayoutReport')
                ->label('M-Pesa Payout Report')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->modalHeading('M-Pesa Payout Report (PDF)')
                ->modalSubmitActionLabel('Download Payout Report')
                ->form([
                    DatePicker::make('from')
                        ->label('From Date')
                        ->required()
                        ->default(now()->startOfMonth())
                        ->maxDate(now()),
                    DatePicker::make('to')
                        ->label('To Date')
                        ->required()
                        ->default(now())
                        ->afterOrEqual('from')
                        ->maxDate(now()),
                    Select::make('status')
                        ->label('Transaction Status')
                        ->helperText('Filter by success or failure.')
                        ->options([
                            ''        => 'All Transactions',
                            'success' => 'Successful Only',
                            'failed'  => 'Failed Only',
                            'pending' => 'Pending Only',
                        ])
                        ->default(''),
                ])
                ->action(function (array $data, $livewire) {
                    $livewire->redirect(route('reports.mpesa.payouts', $data));
                }),
        ];
    }
}