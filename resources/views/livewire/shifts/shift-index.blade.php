<x-layouts.app :title="'Quản lý ca làm việc'">

    <div class="flex flex-col lg:flex-row gap-6">

        {{-- Bảng danh sách ca --}}
        <div class="flex-1">
            <div class="bg-white rounded-xl shadow p-4 space-y-4">

                <h1 class="text-xl font-semibold">Danh sách ca làm việc</h1>

                @if (session()->has('success'))
                    <div class="bg-emerald-100 text-emerald-700 px-3 py-2 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                <table class="w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="px-3 py-2">Mã</th>
                            <th class="px-3 py-2">Tên</th>
                            <th class="px-3 py-2">Giờ</th>
                            <th class="px-3 py-2">Nghỉ</th>
                            <th class="px-3 py-2">Qua đêm</th>
                            <th class="px-3 py-2 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($shifts as $shift)
                            <tr>
                                <td class="px-3 py-2 font-mono">{{ $shift->code }}</td>
                                <td class="px-3 py-2">{{ $shift->name }}</td>
                                <td class="px-3 py-2">
                                    {{ $shift->start_time }} → {{ $shift->end_time }}
                                </td>
                                <td class="px-3 py-2">{{ $shift->break_minutes }} phút</td>
                                <td class="px-3 py-2">
                                    {{ $shift->is_overnight ? 'Có' : 'Không' }}
                                </td>

                                <td class="px-3 py-2 text-right">
                                    <button wire:click="edit({{ $shift->id }})"
                                        class="px-2 py-1 border rounded mr-2">
                                        Sửa
                                    </button>
                                    <button wire:click="delete({{ $shift->id }})"
                                        onclick="return confirm('Xóa ca này?')"
                                        class="px-2 py-1 border border-red-300 text-red-600 rounded">
                                        Xóa
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{ $shifts->links() }}

            </div>
        </div>

        {{-- Form thêm/sửa --}}
        <div class="w-full lg:w-80">
            <div class="bg-white rounded-xl shadow p-4 space-y-4">

                <h2 class="text-lg font-semibold">
                    {{ $editingId ? 'Sửa ca' : 'Thêm ca mới' }}
                </h2>

                <div class="space-y-3">

                    <div>
                        <label class="text-xs">Mã ca</label>
                        <input type="text" wire:model.defer="code" class="w-full px-3 py-1.5 border rounded">
                    </div>

                    <div>
                        <label class="text-xs">Tên ca</label>
                        <input type="text" wire:model.defer="name" class="w-full px-3 py-1.5 border rounded">
                    </div>

                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="text-xs">Giờ vào</label>
                            <input type="time" wire:model.defer="start_time"
                                class="w-full px-3 py-1.5 border rounded">
                        </div>
                        <div class="flex-1">
                            <label class="text-xs">Giờ ra</label>
                            <input type="time" wire:model.defer="end_time" class="w-full px-3 py-1.5 border rounded">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs">Nghỉ giữa ca (phút)</label>
                        <input type="number" wire:model.defer="break_minutes"
                            class="w-full px-3 py-1.5 border rounded">
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model.defer="is_overnight">
                        <label>Ca qua đêm?</label>
                    </div>

                </div>

                <div class="flex justify-between">
                    <button wire:click="resetForm" class="px-3 py-1 border rounded">Làm mới</button>

                    <button wire:click="save" class="px-3 py-1 bg-emerald-600 text-white rounded">
                        {{ $editingId ? 'Lưu thay đổi' : 'Thêm ca' }}
                    </button>
                </div>

            </div>
        </div>

    </div>

</x-layouts.app>
