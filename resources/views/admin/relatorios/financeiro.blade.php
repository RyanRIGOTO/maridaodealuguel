<x-report-layout titulo="FINANCEIRO DA PLATAFORMA" :periodo="$periodo">
    <table class="table-app">
        <thead>
            <tr>
                <th>Data</th>
                <th>Serviço</th>
                <th>Tipo</th>
                <th>Prestador</th>
                <th class="text-right">Valor Total</th>
                <th class="text-right">Taxa Plataforma</th>
                <th class="text-right">Repasse Prestador</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recebimentos as $recebimento)
                <tr>
                    <td class="text-ink-600 font-mono text-xs">{{ $recebimento->created_at->format('d/m/Y') }}</td>
                    <td class="font-bold text-ink-900">{{ $recebimento->agendamento->servico->nome ?? '—' }}</td>
                    <td class="text-xs">{{ $recebimento->tipo === 'multa' ? 'Multa de cancelamento' : 'Serviço' }}</td>
                    <td class="text-ink-800">{{ $recebimento->agendamento->prestador->name ?? '—' }}</td>
                    <td class="text-right font-bold text-ink-900">R$ {{ number_format($recebimento->valor_total, 2, ',', '.') }}</td>
                    <td class="text-right font-semibold text-brand-600">
                        R$ {{ number_format($recebimento->taxa_admin, 2, ',', '.') }}
                        @if ($recebimento->percentual_taxa !== null)
                            <span class="block text-[10px] font-normal text-ink-500">{{ number_format($recebimento->percentual_taxa, 2, ',', '.') }}%</span>
                        @endif
                    </td>
                    <td class="text-right font-semibold text-emerald-700">R$ {{ number_format($recebimento->valor_liquido_prestador, 2, ',', '.') }}</td>
                    <td><x-status-badge :status="$recebimento->status_recebimento" /></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-ink-400 py-8">Nenhum registro financeiro encontrado no período selecionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-6 pt-4 border-t border-ink-200 text-xs sm:text-sm font-bold bg-ink-50 p-4 rounded-lg">
        <p class="text-ink-800 uppercase tracking-wider">Total Bruto: <span class="text-ink-900 font-extrabold">R$ {{ number_format($totalBruto, 2, ',', '.') }}</span></p>
        <p class="text-ink-800 uppercase tracking-wider">Comissão Plataforma: <span class="text-brand-600 font-extrabold">R$ {{ number_format($totalTaxa, 2, ',', '.') }}</span></p>
        <p class="text-ink-800 uppercase tracking-wider">Repasse aos Prestadores: <span class="text-emerald-700 font-extrabold">R$ {{ number_format($totalLiquido, 2, ',', '.') }}</span></p>
    </div>
</x-report-layout>

