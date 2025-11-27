<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkDay extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'work_date',
        'shift_code',
        'total_work_minutes',
        'paid_minutes',
        'overtime_minutes',
        'late_minutes',
        'early_leave_minutes',
        'status',
        'first_check_in_at',
        'last_check_out_at',
    ];

    protected $casts = [
        'work_date'          => 'date',
        'first_check_in_at'  => 'datetime',
        'last_check_out_at'  => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
