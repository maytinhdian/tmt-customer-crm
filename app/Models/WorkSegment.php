<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkSegment extends Model
{
    protected $fillable = [
        'session_id',
        'employee_id',
        'type',
        'started_at',
        'ended_at',
        'minutes',
        'pay_rate_multiplier',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
        'pay_rate_multiplier' => 'decimal:2',
    ];

    public function session()
    {
        return $this->belongsTo(WorkSession::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
