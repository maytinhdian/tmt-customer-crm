<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = [
        'employee_id',
        'device_id',
        'source',
        'direction',
        'checked_at',
        'work_code',
        'area',
        'raw_payload',
        'status',
    ];

    protected $casts = [
        'checked_at'  => 'datetime',
        'raw_payload' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function sessionIn()
    {
        return $this->hasOne(WorkSession::class, 'check_in_log_id');
    }

    public function sessionOut()
    {
        return $this->hasOne(WorkSession::class, 'check_out_log_id');
    }
}
