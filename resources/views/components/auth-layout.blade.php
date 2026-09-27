<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Maridão de Aluguel' }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Tipografia e Ícones Oficiais --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

    {{-- Estilos e Scripts Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-100 flex items-center justify-center px-4 py-12 font-sans antialiased">
    <div class="w-full {{ ($wide ?? false) ? 'max-w-xl' : 'max-w-md' }}">
        {{-- Logotipo Superior Padronizado SVG --}}
        <div class="flex justify-center mb-8">
            <x-logo variant="lockup" height="h-11 sm:h-14" />
        </div>

        {{-- Container do Formulário --}}
        <div class="card p-6 sm:p-8 bg-white border border-ink-300 shadow-md">
            {{ $slot }}
        </div>

        <p class="text-center text-xs text-ink-600 mt-6">
            &copy; {{ date('Y') }} Maridão de Aluguel — Marketplace de Serviços
        </p>
    </div>
</body>
</html>
