<div class="p-6 bg-white rounded-xl shadow text-center space-y-4">
    <h1 class="text-2xl font-bold text-slate-800">
        Livewire Counter
    </h1>

    <div class="text-4xl text-cyan-400 font-mono">
        {{ $count }}
    </div>

    <button
        wire:click="increment"
        class="px-4 py-2 rounded-lg bg-emerald-500 text-white font-semibold hover:bg-emerald-600"
    >
        + Tăng
    </button>
</div>
