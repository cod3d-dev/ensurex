<?php

namespace App\Filament\Widgets;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class QuoteTimes extends ChartWidget
{
    use InteractsWithPageFilters;
    protected static ?string $heading = 'Cotizaciones Pendientes por Tiempo';
    
    protected function getData(): array
    {
        $now = Carbon::now();

        $user_id = ! is_null($this->filters['user_id'] ?? null) ?
            $this->filters['user_id'] :
            null;
        
        // Get pending and sent quotes
        $quotes = Quote::whereIn('status', [QuoteStatus::Pending->value, QuoteStatus::Sent->value])
            ->when($user_id !== null, fn($q) => $q->where('user_id', $user_id))
            ->get();
        
        // Initialize counters for each time range
        $days0to7 = 0;
        $days8to14 = 0;
        $days15to30 = 0;
        $daysOver30 = 0;
        
        foreach ($quotes as $quote) {
            $ageInDays = Carbon::parse($quote->created_at)->diffInDays($now);
            
            if ($ageInDays <= 7) {
                $days0to7++;
            } elseif ($ageInDays <= 14) {
                $days8to14++;
            } elseif ($ageInDays <= 30) {
                $days15to30++;
            } else {
                $daysOver30++;
            }
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Cotizaciones',
                    'data' => [$days0to7, $days8to14, $days15to30, $daysOver30],
                    'backgroundColor' => [
                        '#9ec991', // green for 0-7 days
                        '#7fbfeb', // light blue for 8-14 days
                        '#d9ad6d', // yellow for 15-30 days 
                        '#ef8283', // light red for +30 days
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