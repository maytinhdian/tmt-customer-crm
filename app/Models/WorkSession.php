<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkSession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'work_date',
        'shift_code',
        'check_in_log_id',
        'check_out_log_id',
        'check_in_at',
        'check_out_at',
        'total_minutes',
        'status',
        'notes',
    ];

    protected $casts = [
        'work_date'   => 'date',
        'check_in_at' => 'datetime',
        'check_out_at'=> 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function checkInLog()
    {
        return $this->belongsTo(AttendanceLog::class, 'check_in_log_id');
    }

    public function checkOutLog()
    {
        return $this->belongsTo(AttendanceLog::class, 'check_out_log_id');
    }

    public function segments()
    {
        return $this->hasMany(WorkSegment::class, 'session_id');
    }
}
