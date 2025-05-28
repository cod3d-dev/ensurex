<?php

namespace App\Filament\Widgets;

use App\Models\Policy;
use App\Models\Quote;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuotesOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;
        $userId = $this->filters['user_id'] ?? null;

        // Build quote query with date filters
        $quoteQuery = Quote::query();
        $policyQuery = Policy::query();

        if ($startDate) {
            $quoteQuery->whereDate('created_at', '>=', $startDate);
            $policyQuery->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $quoteQuery->whereDate('created_at', '<=', $endDate);
            $policyQuery->whereDate('created_at', '<=', $endDate);
        }

        if ($userId) {
            $quoteQuery->where('user_id', $userId);
            $policyQuery->where('user_id', $userId);
        }

        // If no date filters are applied, default to current month
        if (! $startDate && ! $endDate) {
            $quoteQuery->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
            $policyQuery->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        }

        // Get counts with explicit zero handling
        $newQuotesCount = (clone $quoteQuery)->whereIn('status', [
            \App\Enums\QuoteStatus::Pending->value,
            \App\Enums\QuoteStatus::Sent->value,
            \App\Enums\QuoteStatus::Accepted->value,
        ])->count() ?: 0;
        $rejectedQuotesCount = (clone $quoteQuery)->where('status', \App\Enums\QuoteStatus::Rejected->value)->count() ?: 0;
        $newPoliciesCount = (clone $policyQuery)->whereIn('status', ['pending', 'active'])->count() ?: 0;
        $cancelledPoliciesCount = (clone $policyQuery)->whereIn('status', ['inactive', 'cancelled'])->count() ?: 0;

        return [
            Stat::make('Nuevas Cotizaciones', $newQuotesCount),
            Stat::make('Cotizaciones Rechazadas', $rejectedQuotesCount),
            Stat::make('Nuevas Polizas', $newPoliciesCount),
            Stat::make('Polizas Canceladas', $cancelledPoliciesCount),
        ];
    }
}
