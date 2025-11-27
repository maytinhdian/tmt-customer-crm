<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Attendance System' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-slate-100 text-slate-900">
    <div class="min-h-screen flex flex-col">
        <header class="bg-slate-900 text-white">
            <div class="max-w-6xl mx-auto px-4 py-3 flex justify-between items-center">
                <div class="font-bold">
                    Attendance System
                </div>
                <nav class="space-x-4 text-sm">
                    <a href="{{ url('/') }}" class="hover:underline">Home</a>
                    <a href="{{ route('employees.index') }}" class="hover:underline font-semibold">
                        Nhân viên
                    </a>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <div class="max-w-6xl mx-auto px-4 py-6">
                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
</body>

</html>
