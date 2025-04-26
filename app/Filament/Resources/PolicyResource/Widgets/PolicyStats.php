<?php

namespace App\Filament\Resources\PolicyResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Filament\Resources\PolicyResource\Pages\ListPolicies;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\Widget;

class PolicyStats extends BaseWidget
{
    use InteractsWithPageTable;

    protected static ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListPolicies::class;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Polizas', $this->getPageTableQuery()->count()),
            Stat::make('Total Aplicantes', $this->getPageTableQuery()->sum('total_applicants') - $this->getPageTableQuery()->sum('total_applicants_with_medicaid')),
            Stat::make('Aplicantes con Medicaid', $this->getPageTableQuery()->sum('total_applicants_with_medicaid')),
        ];
    }
}
