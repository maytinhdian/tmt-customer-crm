<?php

namespace App\Livewire\Attendance;

use App\Models\AttendanceLog;
use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceLogIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $employeeFilter = null;

    // Form fields
    public ?int $employee_id = null;
    public ?string $direction = 'IN';
    public ?string $checked_at = null; // datetime-local string
    public ?string $device_id = null;
    public ?string $source = 'manual';

    protected $rules = [
        'employee_id' => 'required|exists:employees,id',
        'direction'   => 'nullable|string|max:10',
        'checked_at'  => 'required|date',
        'device_id'   => 'nullable|string|max:100',
        'source'      => 'nullable|string|max:50',
    ];

    protected $validationAttributes = [
        'employee_id' => 'nhân viên',
        'checked_at'  => 'thời điểm chấm',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEmployeeFilter(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        // default: now (cho nhanh tay test)
        $this->checked_at = now()->format('Y-m-d\TH:i');
    }

    public function resetForm(): void
    {
        $this->employee_id = null;
        $this->direction   = 'IN';
        $this->device_id   = null;
        $this->source      = 'manual';
        $this->checked_at  = now()->format('Y-m-d\TH:i');
    }

    public function save(): void
    {
        $this->validate();

        AttendanceLog::create([
            'employee_id' => $this->employee_id,
            'device_id'   => $this->device_id,
            'source'      => $this->source,
            'direction'   => $this->direction,
            'checked_at'  => $this->checked_at,
            'status'      => 'valid',
        ]);

        session()->flash('success', 'Đã thêm log chấm công.');

        $this->resetForm();
        $this->resetPage();
    }

    public function render()
    {
        $query = AttendanceLog::query()
            ->with('employee')
            ->orderByDesc('checked_at');

        if ($this->employeeFilter) {
            $query->where('employee_id', $this->employeeFilter);
        }

        if ($this->search !== '') {
            $query->whereHas('employee', function ($q) {
                $q->where('employee_code', 'like', '%' . $this->search . '%')
                    ->orWhere('full_name', 'like', '%' . $this->search . '%');
            });
        }

        $logs = $query->paginate(15);

        $employees = Employee::orderBy('employee_code')->get();

        return view('livewire.attendance.attendance-log-index', [
            'logs'      => $logs,
            'employees' => $employees,
        ]);
    }
}
