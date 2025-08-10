<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class CommissionStatement extends Model
{
    /** @use HasFactory<\Database\Factories\CommissionStatementFactory> */
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'statement_date',
        'pay_period_end_date',
        'total_amount',
        'status',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'pay_period_end_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
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
