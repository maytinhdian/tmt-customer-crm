<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timesheet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'period_start_date',
        'period_end_date',
        'total_work_minutes',
        'total_overtime_minutes',
        'total_paid_minutes',
        'status',
        'locked_at',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date'   => 'date',
        'locked_at'         => 'datetime',
        'approved_at'       => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
