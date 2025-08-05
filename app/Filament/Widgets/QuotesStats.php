<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuotesStats extends BaseWidget
{
    
    protected function getStats(): array
    {
        return [
            Stat::make('Nuevas Cotizaciones', '100')
                ->description('10%')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            Stat::make('Nuevas Polizas', 12)
                ->description('25%')
                ->icon('heroicon-o-document-text')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            Stat::make('Polizas por Cliente', '1.8')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->description('5%')
                ->icon('heroicon-o-document-text')
                ->color('success'),
        ];
    }
}
