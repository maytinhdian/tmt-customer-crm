<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $department = '';

    // Form fields
    public ?int $editingId = null;
    public string $employee_code = '';
    public string $full_name = '';
    public ?string $department_field = null;
    public ?string $position = null;
    public ?string $phone = null;
    public ?string $email = null;
    public ?string $default_shift_code = null;
    public string $status_field = 'active';

    protected $rules = [
        'employee_code'       => 'required|string|max:50',
        'full_name'           => 'required|string|max:150',
        'department_field'    => 'nullable|string|max:100',
        'position'            => 'nullable|string|max:100',
        'phone'               => 'nullable|string|max:20',
        'email'               => 'nullable|email|max:150',
        'default_shift_code'  => 'nullable|string|max:50',
        'status_field'        => 'required|string|in:active,inactive,resigned',
    ];

    protected $validationAttributes = [
        'employee_code'      => 'mã nhân viên',
        'full_name'          => 'họ tên',
        'department_field'   => 'bộ phận',
        'position'           => 'chức danh',
        'status_field'       => 'trạng thái',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingDepartment(): void
    {
        $this->resetPage();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId',
            'employee_code',
            'full_name',
            'department_field',
            'position',
            'phone',
            'email',
            'default_shift_code',
            'status_field',
        ]);
        $this->status_field = 'active';
    }

    public function edit(int $id): void
    {
        $employee = Employee::findOrFail($id);
        $this->editingId        = $employee->id;
        $this->employee_code    = $employee->employee_code;
        $this->full_name        = $employee->full_name;
        $this->department_field = $employee->department;
        $this->position         = $employee->position;
        $this->phone            = $employee->phone;
        $this->email            = $employee->email;
        $this->default_shift_code = $employee->default_shift_code;
        $this->status_field     = $employee->status ?? 'active';
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'employee_code'      => $this->employee_code,
            'full_name'          => $this->full_name,
            'department'         => $this->department_field,
            'position'           => $this->position,
            'phone'              => $this->phone,
            'email'              => $this->email,
            'default_shift_code' => $this->default_shift_code,
            'status'             => $this->status_field,
        ];

        if ($this->editingId) {
            Employee::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Cập nhật nhân viên thành công.');
        } else {
            Employee::create($data);
            session()->flash('success', 'Thêm nhân viên mới thành công.');
        }

        $this->resetForm();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        session()->flash('success', 'Đã xóa (soft-delete) nhân viên.');
        $this->resetPage();
    }

    public function render()
    {
        $query = Employee::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('employee_code', 'like', '%' . $this->search . '%')
                    ->orWhere('full_name', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->department !== '') {
            $query->where('department', $this->department);
        }

        $employees = $query
            ->orderBy('employee_code')
            ->paginate(10);

        $departments = Employee::select('department')
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->toArray();

        return view('livewire.employees.employee-index', [
            'employees'   => $employees,
            'departments' => $departments,
        ]);
    }
}
