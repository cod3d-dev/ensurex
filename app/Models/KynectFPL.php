<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KynectFPL extends Model
{
    /** @use HasFactory<\Database\Factories\KynectKPLFactory> */
    use HasFactory;

    protected $table = 'kynect_fpl';

    protected $fillable = [
        'year',
        'members_1',
        'members_2',
        'members_3',
        'members_4',
        'members_5',
        'members_6',
        'members_7',
        'members_8',
        'additional_member',
    ];

    protected $casts = [
        'year' => 'integer',
        'members_1' => 'decimal:2',
        'members_2' => 'decimal:2',
        'members_3' => 'decimal:2',
        'members_4' => 'decimal:2',
        'members_5' => 'decimal:2',
        'members_6' => 'decimal:2',
        'members_7' => 'decimal:2',
        'members_8' => 'decimal:2',
        'additional_member' => 'decimal:2',
    ];

    /**
     * Get the monthly income threshold for a specific household size and year
     */
    public static function getThreshold(int $householdSize, ?int $year = null): ?float
    {
        $year = $year ?? date('Y');
        $record = self::where('year', $year)->first();

        if (!$record) {
            return null;
        }

        if ($householdSize <= 8) {
            return $record->{"members_" . $householdSize};
        }

        // For households > 8, calculate base + additional
        $baseAmount = $record->members_8;
        $extraMembers = $householdSize - 8;

        return $baseAmount + ($record->additional_member * $extraMembers);
    }

    public static function getCurrentThreshold(int $householdSize): ?float
    {
        $record = self::latest()->first();

        if (!$record) {
            return null;
        }

        if ($householdSize <= 8) {
            return $record->{"members_" . $householdSize};
        }

        // For households > 8, calculate base + additional
        $baseAmount = $record->members_8;
        $extraMembers = $householdSize - 8;

        return $baseAmount + ($record->additional_member * $extraMembers);
    }

    /**
     * Check if a policy meets the KynectFPL requirement
     * 
     * @param \App\Models\Policy|int $policyOrHouseholdSize The policy object or household size
     * @param float|null $monthlyIncome Monthly income (only needed if first param is household size)
     * @return bool Whether the policy meets the KynectFPL requirement
     */
    public static function meetsRequirement($policyOrHouseholdSize, ?float $monthlyIncome = null): bool
    {
        // If a Policy object is passed
        if ($policyOrHouseholdSize instanceof \App\Models\Policy) {
            $policy = $policyOrHouseholdSize;
            $householdSize = $policy->total_family_members;
            
            // Get monthly income from main applicant
            if (isset($policy->main_applicant->monthly_income)) {
                $monthlyIncome = $policy->main_applicant->monthly_income;
            } elseif (isset($policy->main_applicant->yearly_income)) {
                // Convert yearly to monthly if monthly not available
                $monthlyIncome = $policy->main_applicant->yearly_income / 12;
            } else {
                // Can't determine income
                return false;
            }
        } else {
            // If household size is passed directly
            $householdSize = (int) $policyOrHouseholdSize;
            
            // Monthly income must be provided
            if ($monthlyIncome === null) {
                return false;
            }
        }
        
        // Get the threshold for this household size
        $threshold = self::getCurrentThreshold($householdSize);
        
        if ($threshold === null) {
            return false;
        }
        
        // Check if the monthly income is less than or equal to the threshold
        return $monthlyIncome <= $threshold;
    }

    /**
     * Get both monthly and yearly thresholds for a household size
     */
    public static function getThresholds(int $householdSize, ?int $year = null): ?array
    {
        $monthlyIncome = self::getThreshold($householdSize, $year);

        if ($monthlyIncome === null) {
            return null;
        }

        return [
            'monthly_income' => $monthlyIncome,
            'yearly_income' => $monthlyIncome * 12
        ];
    }

    /**
     * Convenient method to get formatted threshold
     */
    public static function threshold(int $year, int $householdSize): float
    {
        $amount = self::getThreshold($householdSize, $year);
        return $amount ?? 0;
    }

    /**
     * Get the latest year available in the KynectFPL table
     */
    public static function getLatestYear(): int
    {
        $latestRecord = self::orderBy('year', 'desc')->first();
        return $latestRecord ? $latestRecord->year : date('Y');
    }
}
