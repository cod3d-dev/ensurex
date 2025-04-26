<?php

namespace App\Models;

use App\Enums\FamilyRelationship;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PolicyApplicant extends Pivot
{
    public $incrementing = true;

    public $table = 'policy_applicants';

    protected $guarded = [];

    protected $casts = [
        'relationship_with_policy_owner' => FamilyRelationship::class,
        'is_covered_by_policy' => 'boolean',
        'medicaid_client' => 'boolean',
        'is_self_employed' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (PolicyApplicant $applicant) {
            // Only set sort_order if it's not already set
            if ($applicant->sort_order === null) {
                // Find the highest sort_order for this policy
                $maxSortOrder = static::where('policy_id', $applicant->policy_id)
                    ->max('sort_order');
                
                // Set the sort_order to one more than the highest
                $applicant->sort_order = is_null($maxSortOrder) ? 0 : $maxSortOrder + 1;
            }
        });
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
