<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class QuoteTimes extends ChartWidget
{
    protected static ?string $heading = 'Cotizaciones Pendientes';

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Tiempo',
                    'data' => [10, 20, 30, 40],
                    'backgroundColor' => [
                        '#4BC0C0',
                        '#36A2EB',
                        '#FFCE56',
                        '#FF6384',
                    ],
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => ['0-7 días', '8-14 días', '15-30 días', '+30 días'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected static ?array $options = [
        'scales' => [
            'x' => ['display' => false],
            'y' => ['display' => false]
        ],
    ];
}