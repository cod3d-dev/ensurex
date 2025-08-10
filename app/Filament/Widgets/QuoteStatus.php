<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Carbon\Carbon;
use App\Models\Quote;

class QuoteStatus extends ChartWidget
{
    use InteractsWithPageFilters;
    protected static ?string $heading = 'Cotizaciones';

    
    protected static ?array $options = [
        'scales' => [
            'x' => ['display' => false],
            'y' => ['display' => false]
        ],
    ];

    protected function getData(): array
    {
        $startDate = ! is_null($this->filters['startDate'] ?? null) ?
            Carbon::parse($this->filters['startDate']) :
            null;

        $endDate = ! is_null($this->filters['endDate'] ?? null) ?
            Carbon::parse($this->filters['endDate'])->endOfDay() :
            now()->endOfDay();

        $user_id = ! is_null($this->filters['user_id'] ?? null) ?
            $this->filters['user_id'] :
            null;


        // Count quotes by status category
        $pendingCount = Quote::query()
            ->when($user_id !== null, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where(function($query) {
                $query->where('status', 'pending');
            })
            ->count();
        
        $sentCount = Quote::query()
            ->when($user_id !== null, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where(function($query) {
                $query->where('status', 'sent');
            })
            ->count();
            
        $acceptedCount = Quote::query()
            ->when($user_id !== null, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'accepted')
            ->count();
            
        $rejectedCount = Quote::query()
            ->when($user_id !== null, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'rejected')
            ->count();
            
        $convertedCount = Quote::query()
            ->when($user_id !== null, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'converted')
            ->count();

        return [
            'labels' => ['Pendientes', 'Enviadas', 'Aceptadas', 'Rechazadas', 'Convertidas'],
            'datasets' => [
                [
                    'label' => 'Cotizaciones',
                    'data' => [$pendingCount, $sentCount, $acceptedCount, $rejectedCount, $convertedCount],
                    'backgroundColor' => [
                        '#d9ad6d', // yellow for pending  
                        '#7fbfeb', // light blue for sent
                        '#59a0cf', // blue for accepted
                        '#ef8283', // light red for rejected 
                        '#9ec991', // green for converted
                    ],
                    'hoverOffset' => 4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
