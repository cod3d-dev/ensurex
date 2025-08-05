<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class QuoteStatus extends ChartWidget
{
    protected static ?string $heading = 'Cotizaciones';

    protected static ?array $options = [
        'scales' => [
            'x' => ['display' => false],
            'y' => ['display' => false]
        ],
    ];

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Cotizaciones',
                    'data' => [10, 20, 30, 40],
                    'backgroundColor' => [
                        '#FFCE56',
                        '#36A2EB',
                        '#FF6384',
                        '#4BC0C0',
                    ],
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => ['Pendientes', 'Aceptadas', 'Rechazadas', 'Convertidas'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
