<?php

namespace App\Filament\Resources\PolicyResource\Widgets;

use App\Filament\Resources\PolicyResource\Pages\ListPolicies;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

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
        // Count unique applicants across all policies by getting distinct contact_ids from policy_applicants table
        // Only include applicants who are covered by the policy (is_covered_by_policy = true)
        $uniqueApplicantsCount = \App\Models\PolicyApplicant::whereIn(
            'policy_id',
            $this->getPageTableQuery()->pluck('id')
        )
            ->where('is_covered_by_policy', true)
            ->distinct('contact_id')
            ->count('contact_id');

        // Count unique medicaid applicants across all policies
        // Note: Medicaid clients typically have is_covered_by_policy = false since they're covered by Medicaid
        $uniqueMedicaidApplicantsCount = \App\Models\PolicyApplicant::whereIn(
            'policy_id',
            $this->getPageTableQuery()->pluck('id')
        )
            ->where('medicaid_client', true)
            ->distinct('contact_id')
            ->count('contact_id');

        return [
            Stat::make('Total Polizas', $this->getPageTableQuery()->count()),
            Stat::make('Total Aplicantes', $uniqueApplicantsCount),
            Stat::make('Total Medicaid', $uniqueMedicaidApplicantsCount),
        ];
    }
}
