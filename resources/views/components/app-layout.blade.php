@props(['title' => null, 'subtitle' => null])
@php($user = auth()->user())

<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title . ' — Maridão de Aluguel' : 'Painel — Maridão de Aluguel' }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Tipografia e Ícones Oficiais --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

    {{-- Estilos e Scripts Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-ink-50 text-ink-900 font-sans antialiased flex flex-col" x-data="{ sidebarOpen: false }">

    <div class="lg:flex flex-1">
        {{-- Backdrop do menu mobile --}}
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 bg-ink-900/40 backdrop-blur-sm z-30 lg:hidden" @click="sidebarOpen = false"></div>

        {{-- Barra Lateral de Navegação (Sidebar) --}}
        <x-sidebar :user="$user" />

        {{-- Área de Conteúdo Principal --}}
        <div class="flex-1 min-w-0 flex flex-col">
            {{-- Topo / Barra Superior --}}
            <x-topbar :user="$user" :subtitle="$subtitle ?? null" />

            {{-- Corpo da Página --}}
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                @isset($title)
                    <h1 class="no-print text-2xl font-bold text-ink-900 mb-5 tracking-tight">{{ $title }}</h1>
                @endisset

                {{-- Mensagens Flash Globais --}}
                <x-flash />

                {{-- Conteúdo Injetado do Blade --}}
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
