<x-report-layout titulo="AGENDAMENTOS / SERVIÇOS PRESTADOS" :periodo="$periodo">
    <table class="table-app">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Prestador</th>
                <th>Serviço</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Status</th>
                <th class="text-right">Valor Acordado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($agendamentos as $agendamento)
                <tr>
                    <td class="font-bold text-ink-900">{{ $agendamento->cliente->name }}</td>
                    <td class="text-ink-800">{{ $agendamento->prestador->name }}</td>
                    <td class="text-ink-700">{{ $agendamento->servico->nome ?? '—' }}</td>
                    <td class="text-ink-600 font-mono text-xs">{{ $agendamento->data_hora->format('d/m/Y') }}</td>
                    <td class="text-ink-600 font-mono text-xs">{{ $agendamento->data_hora->format('H:i') }}</td>
                    <td><x-status-badge :status="$agendamento->status" /></td>
                    <td class="text-right font-bold text-ink-900">R$ {{ number_format($agendamento->preco_acordado, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-ink-400 py-8">Nenhum registro encontrado no período selecionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="flex flex-col sm:flex-row justify-between items-center gap-2 mt-6 pt-4 border-t border-ink-200 text-xs sm:text-sm font-bold text-ink-800 bg-ink-50 p-4 rounded-lg">
        <p class="uppercase tracking-wider">Total de Serviços no Período: <span class="text-brand-600 font-extrabold">{{ $total }}</span></p>
        <p class="uppercase tracking-wider">Valor Total Movimentado: <span class="text-brand-600 font-extrabold">R$ {{ number_format($valorTotal, 2, ',', '.') }}</span></p>
    </div>
</x-report-layout>

