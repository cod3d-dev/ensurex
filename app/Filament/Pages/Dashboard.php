<?php

namespace App\Filament\Pages;

use App\Enums\UserRoles;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('user_id')
                            ->label('Asistente')
                            ->options(User::all()->pluck('name', 'id'))
                            ->default(function () {
                                $user = auth()->user();
                                // Only set default filter for non-admin users
                                if ($user->role !== UserRoles::Admin) {
                                    return $user->id;
                                }

                                return null;
                            }),
                        DatePicker::make('startDate')
                            ->label('Fecha Inicial')
                            ->default(now()->startOfMonth())
                            ->maxDate(fn (Get $get) => $get('endDate') ?: now()),
                        DatePicker::make('endDate')
                            ->label('Fecha Final')
                            ->default(now())
                            ->minDate(fn (Get $get) => $get('startDate') ?: now())
                            ->maxDate(now()),
                    ])
                    ->columns(3),
            ]);
    }
}
