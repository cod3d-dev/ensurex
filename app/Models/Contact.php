<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\Gender;
use App\Enums\MaritialStatus;
use App\Enums\UsState;
use Illuminate\Support\Carbon;

class Contact extends Model
{
    /** @use HasFactory<\Database\Factories\ContactFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'gender' => Gender::class,
        'marital_status' => MaritialStatus::class,
        'is_lead' => 'boolean',
        'is_eligible_for_coverage' => 'boolean',
        'is_tobacco_user' => 'boolean',
        'is_pregnant' => 'boolean',
        'last_contact_date' => 'datetime',
        'next_follow_up_date' => 'datetime',
        'preferred_contact_time' => 'datetime',
        'ssn_issue_date' => 'date',
        'green_card_expiration_date' => 'date',
        'work_permit_expiration_date' => 'date',
        'driver_license_expiration_date' => 'date',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
        'state_province' => UsState::class,
        'driver_license_emissions_state' => UsState::class,
        'annual_income_1' => 'decimal:2',
        'annual_income_2' => 'decimal:2',
        'annual_income_3' => 'decimal:2',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Contact $contact) {
            // Only generate a code if one isn't already set or is null
            if (empty($contact->code) || is_null($contact->code)) {
                // Find the highest contact number
                $highestContact = self::orderByRaw('CAST(SUBSTRING(code, 2) AS UNSIGNED) DESC')
                    ->where('code', 'like', 'C%')
                    ->first();
                
                $nextNumber = 1;
                if ($highestContact) {
                    // Extract the number part and increment
                    $currentNumber = (int) substr($highestContact->code, 1);
                    $nextNumber = $currentNumber + 1;
                }
                
                // Format the contact number with leading zeros (5 digits)
                $contactNumber = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
                $contact->code = 'C' . $contactNumber;
            }
        });
    }

    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ucwords($value),
            set: fn (string $value) => ucwords($value),
        );
    }

    public function age(): Attribute
    {
        return Attribute::make(
            get: fn () => Carbon::parse($this->date_of_birth)->age,
        );
    }

    public function getFullAddressLinesAttribute(): string
    {
        $address = [];

        if ($this->address_line_1) {
            $address[] = $this->address_line_1;
        }

        if ($this->address_line_2) {
            $address[] = $this->address_line_2;
        }

        return implode(', ', $address);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assigned_to(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function updated_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ContactNote::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ContactDocument::class);
    }

    /**
     * Get all policies owned by this contact
     */
    public function ownedPolicies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }

    /**
     * Get all policies where this contact is an applicant
     */
    public function policiesAsApplicant(): BelongsToMany
    {
        return $this->belongsToMany(Policy::class, 'policy_applicants')
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
                'self_employed_yearly_income'
            ])
            ->withTimestamps();
    }
}
