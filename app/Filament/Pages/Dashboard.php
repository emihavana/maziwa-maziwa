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
        ];
    }
}
