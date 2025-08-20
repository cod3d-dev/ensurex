<?php

namespace App\Models;

use App\Enums\CommissionStatementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionStatement extends Model
{
    /** @use HasFactory<\Database\Factories\CommissionStatementFactory> */
    use HasFactory;

    protected $fillable = [
        'asistant_id',
        'statement_date',
        'start_date',
        'end_date',
        'month',
        'year',
        'total_commission',
        'status',
        'created_by',
        'notes',
        'health_policy_amount',
        'accident_policy_amount',
        'vision_policy_amount',
        'dental_policy_amount',
        'life_policy_amount',
        'bonus_amount',
        'bonus_notes',
        'total_amount',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'month' => 'integer',
        'year' => 'integer',
        'total_commission' => 'decimal:2',
        'health_policy_amount' => 'decimal:2',
        'accident_policy_amount' => 'decimal:2',
        'vision_policy_amount' => 'decimal:2',
        'dental_policy_amount' => 'decimal:2',
        'life_policy_amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => CommissionStatementStatus::class,
    ];

    public function asistant()
    {
        return $this->belongsTo(User::class, 'asistant_id');
    }

    public function policies()
    {
        return $this->hasMany(Policy::class, 'commission_statement_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
