<?php

namespace App\Filament\Widgets;

use App\Models\Quote;
use App\Models\Policy;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class QuotesStats extends BaseWidget
{
    use InteractsWithPageFilters;
    protected function getStats(): array
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

        // Get dates from previous period
        $previousEndDate = $startDate->copy()->subDay()->endOfDay();
        $periodLength = $startDate->diffInDays($endDate) + 1;
        $previousStartDate = $previousEndDate->copy()->subDays($periodLength - 1)->startOfDay();

        $previousQuotes = Quote::query()
            ->when($user_id, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->where(function($query) {
                $query->where('status', 'pending')
                      ->orWhere('status', 'accepted')
                      ->orWhere('status', 'sent');
            })
            ->count();
        $currentQuotes = Quote::query()
            ->when($user_id, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where(function($query) {
                $query->where('status', 'pending')
                      ->orWhere('status', 'accepted')
                      ->orWhere('status', 'sent');
            })
            ->count();

        $quotesDiff = $currentQuotes - $previousQuotes;

        $quotesDiffPercentage = $previousQuotes > 0 ? ($quotesDiff / $previousQuotes) * 100 : 0;

        
        // Generate chart data for previous 6 periods
        $quotesChartData = [];
        for ($i = 6; $i >= 1; $i--) {
            $periodEnd = $startDate->copy()->subDays($i * $periodLength)->subDay()->endOfDay();
            $periodStart = $periodEnd->copy()->subDays($periodLength - 1)->startOfDay();
            $count = Quote::query()
                ->when($user_id, fn($q) => $q->where('user_id', $user_id))
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->where(function($query) {
                    $query->where('status', 'pending')
                          ->orWhere('status', 'accepted')
                          ->orWhere('status', 'sent');
                })
                ->count();
            $quotesChartData[] = $count;
        }
        // Add current period as the last point
        $quotesChartData[] = $currentQuotes;
        // Policies
        $currentPolicies = Policy::query()
            ->when($user_id, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $previousPolicies = Policy::query()
            ->when($user_id, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->count();

        $policiesDiff = $currentPolicies - $previousPolicies;
        $policiesDiffPercentage = $previousPolicies > 0 ? ($policiesDiff / $previousPolicies) * 100 : 0;
        $policiesChartData = [];
        for ($i = 6; $i >= 1; $i--) {
            $periodEnd = $startDate->copy()->subDays($i * $periodLength)->subDay()->endOfDay();
            $periodStart = $periodEnd->copy()->subDays($periodLength - 1)->startOfDay();
            $count = Policy::query()
                ->when($user_id, fn($q) => $q->where('user_id', $user_id))
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->count();
            $policiesChartData[] = $count;
        }
        $policiesChartData[] = $currentPolicies;

        // Policies per client
        $currentUniqueContacts = Policy::query()
            ->when($user_id, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$startDate, $endDate])->distinct('contact_id')->count();
        $currentPoliciesPerClient = $currentUniqueContacts > 0 ? $currentPolicies / $currentUniqueContacts : 0;

        $previousUniqueContacts = Policy::query()
            ->when($user_id, fn($q) => $q->where('user_id', $user_id))
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])->distinct('contact_id')->count();
        $previousPoliciesPerClient = $previousUniqueContacts > 0 ? $previousPolicies / $previousUniqueContacts : 0;

        $policiesPerClientDiff = $currentPoliciesPerClient - $previousPoliciesPerClient;
        $policiesPerClientDiffPercentage = $previousPoliciesPerClient > 0 ? ($policiesPerClientDiff / $previousPoliciesPerClient) * 100 : 0;

        $policiesPerClientChartData = [];
        for ($i = 6; $i >= 1; $i--) {
            $periodEnd = $startDate->copy()->subDays($i * $periodLength)->subDay()->endOfDay();
            $periodStart = $periodEnd->copy()->subDays($periodLength - 1)->startOfDay();
            $policiesInPeriod = Policy::query()
                ->when($user_id, fn($q) => $q->where('user_id', $user_id))
                ->whereBetween('created_at', [$periodStart, $periodEnd])->count();
            $uniqueContactsInPeriod = Policy::query()
                ->when($user_id, fn($q) => $q->where('user_id', $user_id))
                ->whereBetween('created_at', [$periodStart, $periodEnd])->distinct('contact_id')->count();
            $ratio = $uniqueContactsInPeriod > 0 ? $policiesInPeriod / $uniqueContactsInPeriod : 0;
            $policiesPerClientChartData[] = $ratio;
        }
        $policiesPerClientChartData[] = $currentPoliciesPerClient;

        return [
            // Number of quotes Pending, Accepted or Sent
            Stat::make('Nuevas Cotizaciones', Quote::query()
                ->when($user_id, fn($q) => $q->where('user_id', $user_id))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where(function($query) {
                    $query->where('status', 'pending')
                          ->orWhere('status', 'accepted')
                          ->orWhere('status', 'sent');
                })
                ->count())
                ->description($quotesDiffPercentage . '%')
                ->descriptionIcon($quotesDiff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($quotesChartData)
                ->color($quotesDiff >= 0 ? 'success' : 'danger'),
            Stat::make('Nuevas Polizas', $currentPolicies)
                ->description(round($policiesDiffPercentage, 2) . '%')
                ->descriptionIcon($policiesDiff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($policiesChartData)
                ->color($policiesDiff >= 0 ? 'success' : 'danger'),
                // Stat Policies per client. Total number of policies divided by the unique number of contact_id in the policies
            Stat::make('Polizas por Cliente', number_format($currentPoliciesPerClient, 2))
                ->description(round($policiesPerClientDiffPercentage, 2) . '%')
                ->descriptionIcon($policiesPerClientDiff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($policiesPerClientChartData)
                ->color($policiesPerClientDiff >= 0 ? 'success' : 'danger'),
        ];
    }
}
