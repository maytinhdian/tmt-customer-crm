<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_code',
        'full_name',
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'department',
        'position',
        'hired_date',
        'default_shift_code',
        'phone',
        'email',
        'address',
        'status',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hired_date' => 'date',
    ];

    // Quan hệ
    public function logs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function sessions()
    {
        return $this->hasMany(WorkSession::class);
    }

    public function workDays()
    {
        return $this->hasMany(WorkDay::class);
    }

    public function timesheets()
    {
        return $this->hasMany(Timesheet::class);
    }
}
