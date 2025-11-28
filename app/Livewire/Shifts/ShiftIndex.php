<?php

namespace App\Livewire\Shifts;

use App\Models\Shift;
use Livewire\Component;
use Livewire\WithPagination;

class ShiftIndex extends Component
{
    use WithPagination;

    public $code, $name, $start_time, $end_time, $break_minutes = 60, $is_overnight = false;
    public $editingId = null;

    protected $rules = [
        'code'          => 'required|string|max:20',
        'name'          => 'required|string|max:100',
        'start_time'    => 'required',
        'end_time'      => 'required',
        'break_minutes' => 'required|integer|min:0|max:300',
        'is_overnight'  => 'boolean',
    ];

    public function resetForm()
    {
        $this->reset([
            'code',
            'name',
            'start_time',
            'end_time',
            'break_minutes',
            'is_overnight',
            'editingId'
        ]);
        $this->break_minutes = 60;
    }

    public function edit($id)
    {
        $shift = Shift::findOrFail($id);
        $this->editingId = $shift->id;

        $this->code          = $shift->code;
        $this->name          = $shift->name;
        $this->start_time    = $shift->start_time;
        $this->end_time      = $shift->end_time;
        $this->break_minutes = $shift->break_minutes;
        $this->is_overnight  = $shift->is_overnight;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'code'          => $this->code,
            'name'          => $this->name,
            'start_time'    => $this->start_time,
            'end_time'      => $this->end_time,
            'break_minutes' => $this->break_minutes,
            'is_overnight'  => $this->is_overnight,
        ];

        if ($this->editingId) {
            Shift::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Đã cập nhật ca.');
        } else {
            Shift::create($data);
            session()->flash('success', 'Đã thêm ca mới.');
        }

        $this->resetForm();
    }

    public function delete($id)
    {
        Shift::findOrFail($id)->delete();
        session()->flash('success', 'Đã xóa ca.');
    }

    public function render()
    {
        return view('livewire.shifts.shift-index', [
            'shifts' => Shift::orderBy('code')->paginate(10),
        ]);
    }
}
