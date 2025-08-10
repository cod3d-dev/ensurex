<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Carbon\Carbon;
use App\Models\Policy;
use App\Enums\PolicyStatus as Status;

class PolicyStatus extends ChartWidget
{
    use InteractsWithPageFilters;
    protected static ?string $heading = 'Polizas';

    protected function getData(): array
    {
      
        $user_id = ! is_null($this->filters['user_id'] ?? null) ?
            $this->filters['user_id'] :
            null;

        $startDate = ! is_null($this->filters['startDate'] ?? null) ?
            Carbon::parse($this->filters['startDate']) :
            null;

        $endDate = ! is_null($this->filters['endDate'] ?? null) ?
            Carbon::parse($this->filters['endDate'])->endOfDay() :
            now()->endOfDay();

        // Count quotes by status category
        $draftCount = Policy::query()
            ->when($user_id !== null, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where(function($query) {
                $query->where('status', Status::Draft);
            })
            ->count();

        $createdCount = Policy::query()
            ->when($user_id !== null, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where(function($query) {
                $query->where('status', Status::Created);
            })
            ->count();
        
        $pendingCount = Policy::query()
            ->when($user_id !== null, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where(function($query) {
                $query->where('status', Status::Pending);
            })
            ->count();
            
        $rejectedCount = Policy::query()
            ->when($user_id !== null, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Status::Rejected)
            ->count();
            
        $activeCount = Policy::query()
            ->when($user_id !== null, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Status::Active)
            ->count();
            
        $inactiveCount = Policy::query()
            ->when($user_id !== null, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Status::Inactive)
            ->count();
            
        $cancelledCount = Policy::query()
            ->when($user_id !== null, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Status::Cancelled)
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Polizas',
                    'data' => [$draftCount, $createdCount + $pendingCount, $activeCount, $rejectedCount + $cancelledCount ],
                    'backgroundColor' => [
                        '#d9ad6d', // yellow for pending  
                        '#7fbfeb', // light blue for pending
                        '#9ec991', // green for active
                        '#ef8283', // light red for rejected
                    ],
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => ['Borrador', 'Creadas', 'Activas', 'Canceladas', 'Pendientes'],
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