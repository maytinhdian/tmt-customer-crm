<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Employees\EmployeeIndex;
use App\Livewire\Attendance\AttendanceLogIndex;
use App\Livewire\Shifts\ShiftIndex;
use App\Livewire\Shifts\ShiftOverrideIndex;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/employees', EmployeeIndex::class)->name('employees.index');
Route::get('/attendance-logs', AttendanceLogIndex::class)
    ->name('attendance.logs.index');

Route::get('/shifts', ShiftIndex::class)->name('shifts.index');
Route::get('/shift-overrides', ShiftOverrideIndex::class)->name('shifts.overrides');
