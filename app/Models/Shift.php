<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'code',
        'name',
        'start_time',
        'end_time',
        'break_minutes',
        'is_overnight'
    ];

    protected $casts = [
        'is_overnight' => 'boolean',
    ];
}
