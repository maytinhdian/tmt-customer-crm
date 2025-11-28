<?php

namespace App\Livewire\Shifts;

use App\Models\Employee;
use App\Models\EmployeeShiftOverride;
use App\Models\Shift;
use Livewire\Component;
use Livewire\WithPagination;

class ShiftOverrideIndex extends Component
{
    use WithPagination;

    // Filter
    public ?int $filter_employee_id = null;
    public ?string $filter_date = null; // yyyy-mm-dd

    // Form fields
    public ?int $employee_id = null;
    public ?int $shift_id = null;
    public ?string $date_from = null;
    public ?string $date_to = null;
    public ?string $note = null;

    public ?int $editingId = null;

    protected $rules = [
        'employee_id' => 'required|exists:employees,id',
        'shift_id'    => 'required|exists:shifts,id',
        'date_from'   => 'required|date',
        'date_to'     => 'required|date|after_or_equal:date_from',
        'note'        => 'nullable|string|max:500',
    ];

    protected $validationAttributes = [
        'employee_id' => 'nhân viên',
        'shift_id'    => 'ca làm việc',
        'date_from'   => 'từ ngày',
        'date_to'     => 'đến ngày',
    ];

    public function mount(): void
    {
        $today = now()->toDateString();
        $this->date_from = $today;
        $this->date_to   = $today;
        $this->filter_date = $today;
    }

    public function updatingFilterEmployeeId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDate(): void
    {
        $this->resetPage();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->employee_id = null;
        $this->shift_id = null;
        $this->note = null;
        $today = now()->toDateString();
        $this->date_from = $today;
        $this->date_to   = $today;
    }

    public function edit(int $id): void
    {
        $o = EmployeeShiftOverride::findOrFail($id);
        $this->editingId  = $o->id;
        $this->employee_id = $o->employee_id;
        $this->shift_id    = $o->shift_id;
        $this->date_from   = optional($o->date_from)->toDateString();
        $this->date_to     = optional($o->date_to)->toDateString();
        $this->note        = $o->note;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'employee_id' => $this->employee_id,
            'shift_id'    => $this->shift_id,
            'date_from'   => $this->date_from,
            'date_to'     => $this->date_to,
            'note'        => $this->note,
        ];

        if ($this->editingId) {
            EmployeeShiftOverride::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Đã cập nhật gán ca tạm.');
        } else {
            EmployeeShiftOverride::create($data);
            session()->flash('success', 'Đã gán ca tạm.');
        }

        $this->resetForm();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        EmployeeShiftOverride::findOrFail($id)->delete();
        session()->flash('success', 'Đã xóa gán ca tạm.');
        $this->resetPage();
    }

    public function render()
    {
        $employees = Employee::orderBy('employee_code')->get();
        $shifts    = Shift::orderBy('code')->get();

        $query = EmployeeShiftOverride::query()
            ->with(['employee', 'shift'])
            ->orderByDesc('date_from')
            ->orderBy('employee_id');

        if ($this->filter_employee_id) {
            $query->where('employee_id', $this->filter_employee_id);
        }

        if ($this->filter_date) {
            $query->where('date_from', '<=', $this->filter_date)
                ->where('date_to', '>=', $this->filter_date);
        }

        $overrides = $query->paginate(10);

        return view('livewire.shifts.shift-override-index', [
            'overrides' => $overrides,
            'employees' => $employees,
            'shifts'    => $shifts,
        ]);
    }
}
