<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Maridão de Aluguel — Marketplace de Serviços' }}</title>
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
<body class="min-h-screen flex flex-col bg-ink-50 text-ink-900 font-sans antialiased" x-data="{ mobileMenu: false }">
    {{-- Cabeçalho Principal (Navbar Reutilizável) --}}
    <x-navbar />

    {{-- Conteúdo Principal --}}
    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- Rodapé Reutilizável --}}
    <x-footer />
</body>
</html>
