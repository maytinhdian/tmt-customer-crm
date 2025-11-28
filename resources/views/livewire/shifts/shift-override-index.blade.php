<x-layouts.app :title="'Gán ca tạm cho nhân viên'">
    <div class="flex flex-col lg:flex-row gap-6">

        {{-- Bảng override --}}
        <div class="flex-1">
            <div class="bg-white rounded-xl shadow p-4 space-y-4">

                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-semibold text-slate-800">
                            Gán ca tạm cho nhân viên
                        </h1>
                        <p class="text-sm text-slate-500">
                            Dùng khi nhân viên được điều đi ca khác trong 1 hoặc nhiều ngày.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <div>
                            <label class="block text-xs text-slate-600 mb-1">
                                Lọc theo nhân viên
                            </label>
                            <select wire:model="filter_employee_id"
                                class="px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                <option value="">-- Tất cả --</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}">
                                        {{ $emp->employee_code }} - {{ $emp->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs text-slate-600 mb-1">
                                Ngày nằm trong khoảng
                            </label>
                            <input type="date" wire:model="filter_date"
                                class="px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                        </div>
                    </div>
                </div>

                @if (session()->has('success'))
                    <div class="px-3 py-2 rounded-lg bg-emerald-50 text-emerald-700 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr
                                class="bg-slate-100 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2">Nhân viên</th>
                                <th class="px-3 py-2">Khoảng ngày</th>
                                <th class="px-3 py-2">Ca</th>
                                <th class="px-3 py-2">Ghi chú</th>
                                <th class="px-3 py-2 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($overrides as $o)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-3 py-2">
                                        @if ($o->employee)
                                            <div class="font-medium text-slate-800">
                                                {{ $o->employee->full_name }}
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                {{ $o->employee->employee_code }}
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400 italic">N/A</span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2 text-sm whitespace-nowrap">
                                        {{ $o->date_from?->format('Y-m-d') }}
                                        @if ($o->date_to && $o->date_to->ne($o->date_from))
                                            → {{ $o->date_to->format('Y-m-d') }}
                                        @endif
                                    </td>

                                    <td class="px-3 py-2 text-sm">
                                        @if ($o->shift)
                                            <div class="font-mono text-xs">
                                                {{ $o->shift->code }}
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                {{ $o->shift->name }}
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400 italic">N/A</span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2 text-xs">
                                        {{ $o->note ?? '-' }}
                                    </td>

                                    <td class="px-3 py-2 text-right text-xs">
                                        <button wire:click="edit({{ $o->id }})"
                                            class="px-2 py-1 rounded border border-slate-300 hover:bg-slate-100 mr-1">
                                            Sửa
                                        </button>
                                        <button wire:click="delete({{ $o->id }})"
                                            onclick="return confirm('Xóa gán ca tạm này?')"
                                            class="px-2 py-1 rounded border border-red-300 text-red-600 hover:bg-red-50">
                                            Xóa
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-4 text-center text-sm text-slate-500">
                                        Chưa có gán ca tạm nào.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    {{ $overrides->links() }}
                </div>
            </div>
        </div>

        {{-- Form gán ca tạm --}}
        <div class="w-full lg:w-80">
            <div class="bg-white rounded-xl shadow p-4 space-y-4">
                <h2 class="text-lg font-semibold text-slate-800">
                    {{ $editingId ? 'Sửa gán ca tạm' : 'Gán ca tạm mới' }}
                </h2>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Nhân viên
                        </label>
                        <select wire:model.defer="employee_id"
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">-- Chọn nhân viên --</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">
                                    {{ $emp->employee_code }} - {{ $emp->full_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <div class="text-xs text-red-600 mt-0.5">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Ca làm việc
                        </label>
                        <select wire:model.defer="shift_id"
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">-- Chọn ca --</option>
                            @foreach ($shifts as $s)
                                <option value="{{ $s->id }}">
                                    {{ $s->code }} - {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('shift_id')
                            <div class="text-xs text-red-600 mt-0.5">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                Từ ngày
                            </label>
                            <input type="date" wire:model.defer="date_from"
                                class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                            @error('date_from')
                                <div class="text-xs text-red-600 mt-0.5">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                Đến ngày
                            </label>
                            <input type="date" wire:model.defer="date_to"
                                class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                            @error('date_to')
                                <div class="text-xs text-red-600 mt-0.5">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Ghi chú
                        </label>
                        <textarea wire:model.defer="note" rows="3"
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                        @error('note')
                            <div class="text-xs text-red-600 mt-0.5">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button wire:click="resetForm" type="button"
                        class="text-xs px-2 py-1 rounded border border-slate-300 text-slate-600 hover:bg-slate-50">
                        Làm mới
                    </button>

                    <button wire:click="save" type="button"
                        class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                        {{ $editingId ? 'Lưu thay đổi' : 'Gán ca' }}
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-layouts.app>
