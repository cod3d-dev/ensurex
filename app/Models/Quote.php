<?php

namespace App\Models;

use App\Casts\ApplicantCast;
use App\Casts\ApplicantCollectionCast;
use App\Enums\DocumentStatus;
use App\Enums\QuoteStatus;
use App\Enums\PolicyType;
use App\Enums\UsState;
use App\ValueObjects\ApplicantCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quote extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'main_applicant' => ApplicantCast::class,
        'additional_applicants' => ApplicantCollectionCast::class,
        'prescription_drugs' => 'array',
        'contact_information' => 'array',
        'premium_amount' => 'decimal:2',
        'coverage_amount' => 'decimal:2',
        'estimated_household_income' => 'decimal:2',
        'total_family_members' => 'integer',
        'total_applicants' => 'integer',
        'document_status' => DocumentStatus::class,
        'status' => QuoteStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'valid_until' => 'date',
        'policy_types' => 'array',
        'applicants' => 'array',
        'state_province' => UsState::class,
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }


    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function policy(): HasOne
    {
        return $this->hasOne(Policy::class);
    }

    public function quoteDocuments(): HasMany
    {
        return $this->hasMany(QuoteDocument::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function mainApplicant()
    {
        return $this->applicants?->mainApplicant();
    }

    public function additionalApplicants()
    {
        return $this->applicants?->additionalApplicants();
    }
    
    /**
     * Get the main applicant from the applicants JSON field
     * 
     * @return array|null
     */
    public function getMainApplicantFromJson()
    {
        if (!$this->applicants || !is_array($this->applicants)) {
            return null;
        }
        
        foreach ($this->applicants as $applicant) {
            if (isset($applicant['is_primary']) && $applicant['is_primary']) {
                return $applicant;
            }
        }
        
        return $this->applicants[0] ?? null;
    }
    
    /**
     * Get additional applicants from the applicants JSON field
     * 
     * @return array
     */
    public function getAdditionalApplicantsFromJson()
    {
        if (!$this->applicants || !is_array($this->applicants)) {
            return [];
        }
        
        $additionalApplicants = [];
        
        foreach ($this->applicants as $index => $applicant) {
            if (!isset($applicant['is_primary']) || !$applicant['is_primary']) {
                $additionalApplicants[] = $applicant;
            }
        }
        
        return $additionalApplicants;
    }
}
