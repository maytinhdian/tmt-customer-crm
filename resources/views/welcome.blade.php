<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Tailwind Test</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-900 text-white flex items-center justify-center">

    {{-- <h1 class="text-5xl font-bold text-emerald-400">
        Tailwind OK! ✅
    </h1> --}}
    <livewire:counter />

    @livewireScripts
</body>

</html>
