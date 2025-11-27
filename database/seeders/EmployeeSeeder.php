<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        Employee::create([
            'employee_code'       => 'NV001',
            'full_name'           => 'Nguyễn Văn A',
            'first_name'          => 'Văn A',
            'last_name'           => 'Nguyễn',
            'gender'              => 'male',
            'department'          => 'Sản xuất',
            'position'            => 'Công nhân',
            'hired_date'          => '2021-01-05',
            'default_shift_code'  => 'HC',
            'phone'               => '0123456789',
            'status'              => 'active',
        ]);
    }
}
