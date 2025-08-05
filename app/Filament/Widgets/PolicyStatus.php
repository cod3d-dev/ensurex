<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class PolicyStatus extends ChartWidget
{
    protected static ?string $heading = 'Polizas';

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Polizas',
                    'data' => [10, 20, 30, 40],
                    'backgroundColor' => [
                        '#4BC0C0',
                        '#36A2EB',
                        '#FF6384',
                        '#FFCE56',
                        '#FFCE56',
                    ],
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => ['Creadas', 'Activas', 'Canceladas', 'Pendientes'],
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