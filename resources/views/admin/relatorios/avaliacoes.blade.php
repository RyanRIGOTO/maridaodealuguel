<x-report-layout titulo="AVALIAÇÃO DOS PRESTADORES" :periodo="$periodo">
    <table class="table-app">
        <thead>
            <tr>
                <th>Prestador</th>
                <th>Data Avaliação</th>
                <th>Nota</th>
                <th>Comentário</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($avaliacoes as $avaliacao)
                <tr>
                    <td class="font-bold text-ink-900">{{ $avaliacao->prestador->name }}</td>
                    <td class="text-ink-600 font-mono text-xs">{{ $avaliacao->data_avaliacao->format('d/m/Y') }}</td>
                    <td>
                        <div class="flex items-center gap-1.5">
                            <x-star-rating :value="$avaliacao->nota" />
                            <span class="text-xs font-bold text-ink-800">({{ $avaliacao->nota }}.0)</span>
                        </div>
                    </td>
                    <td class="max-w-xs truncate text-ink-600 text-xs italic">{{ $avaliacao->comentario ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-ink-400 py-8">Nenhuma avaliação encontrada no período selecionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($melhor || $pior)
        <div class="grid sm:grid-cols-2 gap-3 mt-6 pt-4 border-t border-ink-200 text-xs sm:text-sm font-bold bg-ink-50 p-4 rounded-lg">
            @if ($melhor)
                <p class="text-ink-800 uppercase tracking-wider">Maior Média: <span class="text-emerald-700 font-extrabold">{{ $melhor['prestador']->name }} (★ {{ number_format($melhor['media'], 1, ',', '.') }})</span></p>
            @endif
            @if ($pior)
                <p class="text-ink-800 uppercase tracking-wider">Menor Média: <span class="text-amber-800 font-extrabold">{{ $pior['prestador']->name }} (★ {{ number_format($pior['media'], 1, ',', '.') }})</span></p>
            @endif
        </div>
    @endif

    {{-- Resumo individual por prestador --}}
    @if ($porPrestador->isNotEmpty())
        <h3 class="font-bold text-ink-900 text-sm uppercase tracking-wider mt-8 mb-3">Resumo Individual por Prestador</h3>
        <table class="table-app">
            <thead>
                <tr>
                    <th>Prestador</th>
                    <th class="text-right">Total de Avaliações</th>
                    <th class="text-right">Média Geral</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($porPrestador as $linha)
                    <tr>
                        <td class="font-bold text-ink-900">{{ $linha['prestador']->name }}</td>
                        <td class="text-right text-ink-700">{{ $linha['total'] }}</td>
                        <td class="text-right font-bold text-amber-600">★ {{ number_format($linha['media'], 1, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-report-layout>

