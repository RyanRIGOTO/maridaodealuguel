@props(['titulo', 'periodo' => []])

<x-app-layout :title="$titulo">
    {{-- Ações Superiores (Filtro de Data e Impressão) --}}
    <div class="no-print mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <a href="{{ route('admin.relatorios') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-600 hover:text-brand-600 transition-colors">
            <i class="ti ti-arrow-left text-base"></i>
            <span>Voltar aos relatórios</span>
        </a>

        <div class="flex flex-wrap items-center gap-2.5">
            <form method="GET" class="flex items-center gap-2">
                <input type="date" name="data_inicio" value="{{ $periodo['data_inicio'] ?? '' }}" class="form-input !py-1.5 text-xs sm:text-sm !w-auto">
                <span class="text-ink-600 text-xs sm:text-sm">até</span>
                <input type="date" name="data_fim" value="{{ $periodo['data_fim'] ?? '' }}" class="form-input !py-1.5 text-xs sm:text-sm !w-auto">
                <button type="submit" class="btn-secondary btn-sm">
                    <i class="ti ti-filter text-sm"></i>
                    <span>Filtrar</span>
                </button>
            </form>
            <button type="button" onclick="window.print()" class="btn-primary btn-sm">
                <i class="ti ti-printer text-sm"></i>
                <span>Imprimir / PDF</span>
            </button>
        </div>
    </div>

    {{-- Área de Impressão e Conteúdo do Relatório --}}
    <div class="card p-6 sm:p-8 print-area bg-white border border-ink-300">
        {{-- Cabeçalho Padrão do Relatório --}}
        <div class="border-b-2 border-ink-900 pb-4 mb-6 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <x-logo variant="mark" height="h-10" :href="null" />
                <div>
                    <p class="font-bold text-ink-900 tracking-wide text-sm sm:text-base uppercase">Maridão de Aluguel — Marketplace de Serviços</p>
                    <p class="text-xs sm:text-sm font-semibold text-brand-600 uppercase">Relatório: {{ $titulo }}</p>
                </div>
            </div>
            <div class="sm:text-right text-xs text-ink-600 space-y-0.5 font-medium">
                <p>Emissão: {{ now()->format('d/m/Y') }} às {{ now()->format('H:i') }}</p>
                <p>Emitido por: {{ auth()->user()->name }}</p>
                @if (!empty($periodo['data_inicio']) || !empty($periodo['data_fim']))
                    <p>Período: {{ $periodo['data_inicio'] ?? '...' }} até {{ $periodo['data_fim'] ?? '...' }}</p>
                @endif
            </div>
        </div>

        {{-- Tabela e Dados do Relatório --}}
        {{ $slot }}

        {{-- Rodapé Padrão do Relatório --}}
        <div class="border-t border-ink-300 mt-8 pt-4 text-center text-xs text-ink-600">
            Sistema Maridão de Aluguel — Gerado eletronicamente em {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</x-app-layout>
