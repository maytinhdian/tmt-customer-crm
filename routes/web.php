<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Employees\EmployeeIndex;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/employees', EmployeeIndex::class)->name('employees.index');
