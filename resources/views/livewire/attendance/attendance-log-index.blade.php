<x-layouts.app :title="'Log chấm công'">
    <div class="flex flex-col gap-6 lg:flex-row">
        {{-- Bảng log --}}
        <div class="flex-1">
            <div class="bg-white rounded-xl shadow p-4 space-y-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-semibold text-slate-800">
                            Log chấm công
                        </h1>
                        <p class="text-sm text-slate-500">
                            Dữ liệu log raw dùng để tính công (giả lập từ tay trước).
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <input type="text" wire:model.debounce.500ms="search" placeholder="Tìm theo mã / tên NV..."
                            class="px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500" />

                        <select wire:model="employeeFilter"
                            class="px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">-- Tất cả nhân viên --</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">
                                    {{ $emp->employee_code }} - {{ $emp->full_name }}
                                </option>
                            @endforeach
                        </select>
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
                                <th class="px-3 py-2">Thời điểm</th>
                                <th class="px-3 py-2">Nhân viên</th>
                                <th class="px-3 py-2">IN/OUT</th>
                                <th class="px-3 py-2">Thiết bị</th>
                                <th class="px-3 py-2">Nguồn</th>
                                <th class="px-3 py-2">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($logs as $log)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-3 py-2 whitespace-nowrap text-xs font-mono">
                                        {{ $log->checked_at?->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="px-3 py-2">
                                        @if ($log->employee)
                                            <div class="font-medium text-slate-800">
                                                {{ $log->employee->full_name }}
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                {{ $log->employee->employee_code }}
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400 italic">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        @php
                                            $direction = $log->direction ?? 'UNKNOWN';
                                            $color =
                                                $direction === 'IN'
                                                    ? 'bg-emerald-100 text-emerald-700'
                                                    : ($direction === 'OUT'
                                                        ? 'bg-sky-100 text-sky-700'
                                                        : 'bg-slate-100 text-slate-600');
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                                            {{ $direction }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-xs">
                                        {{ $log->device_id ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-xs">
                                        {{ $log->source ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $log->status === 'valid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                            {{ $log->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-4 text-center text-sm text-slate-500">
                                        Chưa có log nào.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    {{ $logs->links() }}
                </div>
            </div>
        </div>

        {{-- Form thêm log --}}
        <div class="w-full lg:w-80">
            <div class="bg-white rounded-xl shadow p-4 space-y-4">
                <h2 class="text-lg font-semibold text-slate-800">
                    Thêm log chấm công
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
                            Thời điểm chấm
                        </label>
                        <input type="datetime-local" wire:model.defer="checked_at"
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                        @error('checked_at')
                            <div class="text-xs text-red-600 mt-0.5">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                IN / OUT
                            </label>
                            <select wire:model.defer="direction"
                                class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                <option value="IN">IN</option>
                                <option value="OUT">OUT</option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                Nguồn
                            </label>
                            <input type="text" wire:model.defer="source"
                                class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Thiết bị
                        </label>
                        <input type="text" wire:model.defer="device_id" placeholder="VD: ZK-01, HIK-DOOR-1..."
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button wire:click="resetForm" type="button"
                        class="text-xs px-2 py-1 rounded border border-slate-300 text-slate-600 hover:bg-slate-50">
                        Làm mới
                    </button>

                    <button wire:click="save" type="button"
                        class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                        Thêm log
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
