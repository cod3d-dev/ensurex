<?php

namespace App\Filament\Widgets;

use App\Models\Policy;
use App\Models\Quote;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuotesOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Nuevas Cotizaciones', Quote::query()->whereIn('status', ['pending'])->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count()),
            Stat::make('Cotizaciones Rechazadas', Quote::query()->where('status', 'rejected')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count()),
            Stat::make('Nuevas Polizas', Policy::query()->where('status', ['pending', 'active'])->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count()),
            Stat::make('Polizas Canceladas', Policy::query()->where('status', ['inactive', 'cancelled'])->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count()),
        ];
    }
}
