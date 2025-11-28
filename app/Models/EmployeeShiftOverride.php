<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeShiftOverride extends Model
{
    protected $fillable = [
        'employee_id',
        'shift_id',
        'date_from',
        'date_to',
        'note',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to'   => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
