<?php

namespace App\Models;

use App\Casts\ApplicantCast;
use App\Casts\ApplicantCollectionCast;
use App\Enums\DocumentStatus;
use App\Enums\PolicyInscriptionType;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\RenewalStatus;
use App\Enums\UsState;
use App\Models\Agent;
use App\Models\Contact;
use App\Models\InsuranceCompany;
use App\Models\Issue;
use App\Models\KynectFPL;
use App\Models\PolicyApplicant;
use App\Models\PolicyDocument;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Policy extends Model
{
    use HasFactory;

    protected $guarded = [];

    // protected $hidden = [
    //     'payment_card_number',
    //     'payment_card_cvv',
    //     'payment_bank_account_number',
    // ];

    protected $casts = [
        'main_applicant' => ApplicantCast::class,
        'additional_applicants' => ApplicantCollectionCast::class,
        // 'family_members' => 'array',
        'prescription_drugs' => 'array',
        'life_insurance' => 'json',
        'contact_information' => 'array',
        'premium_amount' => 'decimal:2',
        'coverage_amount' => 'decimal:2',
        'estimated_household_income' => 'decimal:2',
        'policy_total_cost' => 'decimal:2',
        'policy_total_subsidy' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'valid_until' => 'date',
        'next_document_expiration_date' => 'date',
        'total_family_members' => 'integer',
        'total_applicants' => 'integer',
        'recurring_payment' => 'boolean',
        'initial_paid' => 'boolean',
        'autopay' => 'boolean',
        'aca' => 'boolean',
        'life_offered' => 'boolean',
        'client_notified' => 'boolean',
        'document_status' => DocumentStatus::class,
        'status' => PolicyStatus::class,
        'renewal_status' => RenewalStatus::class,
        'policy_type' => PolicyType::class,
        'state' => UsState::class,
        'billing_address_state' => UsState::class,
        'quote_policy_types' => 'array',
        'completed_pages' => 'array',
        'policy_inscription_type' => PolicyInscriptionType::class,
    ];

    protected function casts(): array
    {
        return [
            'payment_card_number' => 'encrypted',
            'payment_card_cvv' => 'encrypted',
            'payment_card_holder' => 'encrypted',
            'payment_bank_account_number' => 'encrypted',
            'payment_bank_account_holder' => 'encrypted',
            'billing_address_1' => 'encrypted',
            'billing_address_2' => 'encrypted',
            'payment_card_exp_month' => 'encrypted',
            'payment_card_exp_year' => 'encrypted',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($policy) {
            // Only generate a code if one isn't already set or is null
            if (empty($policy->code) || is_null($policy->code)) {
                // Get the policy type prefix
                $typePrefix = match ($policy->policy_type?->value) {
                    'health' => 'H',
                    'accident' => 'A',
                    'vision' => 'V',
                    'dental' => 'D',
                    'life' => 'L',
                    default => 'H'
                };

                // Find the highest policy number with this prefix
                $highestPolicy = self::where('code', 'like', $typePrefix.'%')
                    ->orderByRaw('CAST(SUBSTRING(code, 2) AS UNSIGNED) DESC')
                    ->first();

                $nextNumber = 1;
                if ($highestPolicy) {
                    // Extract the number part and increment
                    $currentNumber = (int) substr($highestPolicy->code, 1);
                    $nextNumber = $currentNumber + 1;
                }

                // Format the policy number with leading zeros (5 digits)
                $policyNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
                $policy->code = $typePrefix.$policyNumber;

                Log::info('Generated policy code', [
                    'policy_type' => $policy->policy_type?->value,
                    'code' => $policy->code,
                ]);
            }
        });

        static::saving(function ($policy) {
            Log::info('Saving policy...', [
                'id' => $policy->id,
                'attributes' => $policy->getDirty(),
            ]);
        });

        static::saved(function ($policy) {
            Log::info('Policy saved successfully', [
                'id' => $policy->id,
                'changes' => $policy->getChanges(),
            ]);
        });
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PolicyDocument::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function applicants(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'policy_applicants')
            ->withPivot([
                'sort_order',
                'relationship_with_policy_owner',
                'is_covered_by_policy',
                'medicaid_client',
                'employer_1_name',
                'employer_1_role',
                'employer_1_phone',
                'employer_1_address',
                'income_per_hour',
                'hours_per_week',
                'income_per_extra_hour',
                'extra_hours_per_week',
                'weeks_per_year',
                'yearly_income',
                'is_self_employed',
                'self_employed_profession',
                'self_employed_yearly_income',
            ])
            ->withTimestamps();
    }

    /**
     * Get additional applicants for this policy (excluding the owner)
     */
    public function additionalApplicants()
    {
        return $this->applicants()
            ->wherePivot('contact_id', '!=', $this->contact_id)
            ->orderBy('policy_applicants.sort_order')
            ->get();
    }

    public function isOwnerAnApplicant(): bool
    {
        return $this->applicants()
            ->wherePivot('contact_id', $this->contact_id)
            ->exists();
    }

    public function policyApplicants(): HasMany
    {
        return $this->hasMany(PolicyApplicant::class);
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    /**
     * Mark a specific policy page as completed
     *
     * @param  string  $pageName  The name of the page that was completed
     */
    public function markPageCompleted(string $pageName): void
    {
        $completedPages = $this->completed_pages ?? [];

        if (! in_array($pageName, $completedPages)) {
            $completedPages[] = $pageName;
            $this->completed_pages = $completedPages;
            $this->save();
        }
    }

    /**
     * Check if a specific page has been completed
     *
     * @param  string  $pageName  The name of the page to check
     */
    public function isPageCompleted(string $pageName): bool
    {
        return in_array($pageName, $this->completed_pages ?? []);
    }

    /**
     * Check if all required pages have been completed
     */
    public function areRequiredPagesCompleted(): bool
    {
        $requiredPages = [
            'edit_policy',
            'edit_policy_contact',
            // 'edit_policy_applicants',
            'edit_policy_applicants_data',
            // 'edit_policy_income',
            'edit_policy_payments',
        ];

        if ($this->policy_type !== PolicyType::Life) {
            $requiredPages[] = 'edit_policy_applicants';
            $requiredPages[] = 'edit_policy_income';
        }

        $completedPages = $this->completed_pages ?? [];

        return empty(array_diff($requiredPages, $completedPages));
    }

    /**
     * Get a list of incomplete required pages
     */
    public function getIncompletePages(): array
    {
        $requiredPages = [
            'edit_policy',
            'edit_policy_contact',
            'edit_policy_applicants',
            'edit_policy_applicants_data',
            'edit_policy_income',
            'edit_policy_payments',
        ];

        $completedPages = $this->completed_pages ?? [];

        return array_diff($requiredPages, $completedPages);
    }

    public function initialVerificationPerformedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initial_verification_performed_by');
    }

    public function previousYearPolicyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'previous_year_policy_user_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    // Renewal relationships
    public function renewedFromPolicy(): BelongsTo
    {
        return $this->belongsTo(Policy::class, 'renewed_from_policy_id');
    }

    public function renewedToPolicy(): BelongsTo
    {
        return $this->belongsTo(Policy::class, 'renewed_to_policy_id');
    }

    public function renewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renewed_by');
    }

    // Helper methods for renewal
    public function isRenewable(): bool
    {
        return ! $this->renewed_to_policy_id &&
            $this->end_date &&
            $this->end_date->isFuture() &&
            $this->end_date->subMonths(3)->isPast();
    }

    public function getRenewalPeriod(): array
    {
        $startDate = $this->end_date?->addDay();

        return [
            'start_date' => $startDate,
            'end_date' => $startDate?->addYear()->subDay(),
        ];
    }

    /**
     * Check if this policy meets the KynectFPL requirement
     *
     * @return bool Whether the policy meets the KynectFPL requirement
     */
    public function getMeetsKynectFPLRequirementAttribute(): bool
    {
        // Get monthly income from main applicant
        $annualIncome = null;

        if (isset($this->estimated_household_income)) {
            // Convert estimated_household_income to float
            $annualIncome = (float) $this->estimated_household_income;
        } else {
            // Can't determine income
            return false;
        }
        // Get the household size
        $householdSize = $this->total_family_members;

        // Get the threshold for this household size
        $threshold = KynectFPL::getCurrentThreshold($householdSize);

        if ($threshold === null) {
            return false;
        }

        // Check if the monthly income is less than or equal to the threshold
        return $annualIncome >= $threshold * 12;
    }

    public function getTotalMembersAttribute(): int
    {
        return $this->total_family_members;
    }
}
