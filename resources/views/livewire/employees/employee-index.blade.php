<x-layouts.app :title="'Quản lý nhân viên'">
    <div class="flex flex-col gap-6 lg:flex-row">
        {{-- Bảng danh sách nhân viên --}}
        <div class="flex-1">
            <div class="bg-white rounded-xl shadow p-4 space-y-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-semibold text-slate-800">
                            Danh sách nhân viên
                        </h1>
                        <p class="text-sm text-slate-500">
                            Quản lý nhân viên để dùng cho chấm công.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <input type="text" wire:model.debounce.500ms="search"
                            placeholder="Tìm mã / tên / SĐT / email..."
                            class="px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500" />

                        <select wire:model="status"
                            class="px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">-- Trạng thái --</option>
                            <option value="active">Đang làm</option>
                            <option value="inactive">Tạm nghỉ</option>
                            <option value="resigned">Nghỉ việc</option>
                        </select>

                        <select wire:model="department"
                            class="px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">-- Bộ phận --</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept }}">{{ $dept }}</option>
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
                                <th class="px-3 py-2">Mã NV</th>
                                <th class="px-3 py-2">Họ tên</th>
                                <th class="px-3 py-2">Bộ phận</th>
                                <th class="px-3 py-2">Chức danh</th>
                                <th class="px-3 py-2">Liên hệ</th>
                                <th class="px-3 py-2">Ca mặc định</th>
                                <th class="px-3 py-2">Trạng thái</th>
                                <th class="px-3 py-2 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($employees as $employee)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-3 py-2 whitespace-nowrap font-mono text-xs">
                                        {{ $employee->employee_code }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="font-medium text-slate-800">
                                            {{ $employee->full_name }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            ID: {{ $employee->id }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-sm">
                                        {{ $employee->department ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-sm">
                                        {{ $employee->position ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-xs">
                                        @if ($employee->phone)
                                            <div>{{ $employee->phone }}</div>
                                        @endif
                                        @if ($employee->email)
                                            <div class="text-slate-500">{{ $employee->email }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm">
                                        {{ $employee->default_shift_code ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2">
                                        @php
                                            $statusColor = match ($employee->status) {
                                                'active' => 'bg-emerald-100 text-emerald-700',
                                                'inactive' => 'bg-amber-100 text-amber-700',
                                                'resigned' => 'bg-slate-200 text-slate-700',
                                                default => 'bg-slate-100 text-slate-600',
                                            };
                                            $statusLabel = match ($employee->status) {
                                                'active' => 'Đang làm',
                                                'inactive' => 'Tạm nghỉ',
                                                'resigned' => 'Nghỉ việc',
                                                default => $employee->status,
                                            };
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right text-xs">
                                        <button wire:click="edit({{ $employee->id }})"
                                            class="px-2 py-1 rounded border border-slate-300 hover:bg-slate-100 mr-1">
                                            Sửa
                                        </button>
                                        <button wire:click="delete({{ $employee->id }})"
                                            onclick="return confirm('Xác nhận xóa (soft-delete) nhân viên này?')"
                                            class="px-2 py-1 rounded border border-red-300 text-red-600 hover:bg-red-50">
                                            Xóa
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-3 py-4 text-center text-sm text-slate-500">
                                        Chưa có nhân viên nào.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    {{ $employees->links() }}
                </div>
            </div>
        </div>

        {{-- Form thêm / sửa nhân viên --}}
        <div class="w-full lg:w-80">
            <div class="bg-white rounded-xl shadow p-4 space-y-4">
                <h2 class="text-lg font-semibold text-slate-800">
                    {{ $editingId ? 'Sửa nhân viên' : 'Thêm nhân viên' }}
                </h2>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Mã nhân viên
                        </label>
                        <input type="text" wire:model.defer="employee_code"
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                        @error('employee_code')
                            <div class="text-xs text-red-600 mt-0.5">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Họ tên
                        </label>
                        <input type="text" wire:model.defer="full_name"
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                        @error('full_name')
                            <div class="text-xs text-red-600 mt-0.5">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Bộ phận
                        </label>
                        <input type="text" wire:model.defer="department_field"
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                        @error('department_field')
                            <div class="text-xs text-red-600 mt-0.5">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Chức danh
                        </label>
                        <input type="text" wire:model.defer="position"
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                        @error('position')
                            <div class="text-xs text-red-600 mt-0.5">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                SĐT
                            </label>
                            <input type="text" wire:model.defer="phone"
                                class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                Ca mặc định
                            </label>
                            <input type="text" wire:model.defer="default_shift_code" placeholder="VD: HC, C1…"
                                class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Email
                        </label>
                        <input type="email" wire:model.defer="email"
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                        @error('email')
                            <div class="text-xs text-red-600 mt-0.5">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Trạng thái
                        </label>
                        <select wire:model.defer="status_field"
                            class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="active">Đang làm</option>
                            <option value="inactive">Tạm nghỉ</option>
                            <option value="resigned">Nghỉ việc</option>
                        </select>
                        @error('status_field')
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
                        {{ $editingId ? 'Lưu thay đổi' : 'Thêm nhân viên' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
