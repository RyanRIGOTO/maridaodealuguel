<x-app-layout title="Relatório dos Meus Serviços" subtitle="Acompanhe atendimentos, repasses e avaliações por serviço">
    <div class="no-print mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="date" name="data_inicio" value="{{ $periodo['data_inicio'] ?? '' }}" class="form-input !py-1.5 text-xs sm:text-sm !w-auto" aria-label="Data inicial">
            <span class="text-ink-600 text-xs sm:text-sm">até</span>
            <input type="date" name="data_fim" value="{{ $periodo['data_fim'] ?? '' }}" class="form-input !py-1.5 text-xs sm:text-sm !w-auto" aria-label="Data final">
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

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-5 mb-8">
        <x-stat-card label="Serviços cadastrados" :value="$resumo['servicos']" icon="ti-briefcase" color="brand" />
        <x-stat-card label="Atendimentos concluídos" :value="$resumo['concluidos']" icon="ti-circle-check" color="emerald" />
        <x-stat-card label="Repasse líquido previsto" value="R$ {{ number_format($resumo['valor_liquido'], 2, ',', '.') }}" icon="ti-cash" color="brand" />
        <x-stat-card label="Avaliação média" :value="$resumo['media_avaliacoes'] !== null ? number_format($resumo['media_avaliacoes'], 1, ',', '.') : '—'" icon="ti-star-filled" color="amber" />
    </div>

    <div class="card p-5 sm:p-7 print-area bg-white border border-ink-300 overflow-x-auto">
        <div class="border-b border-ink-200 pb-4 mb-5 flex flex-col sm:flex-row sm:items-start justify-between gap-3">
            <div>
                <p class="font-bold text-ink-900 text-base">Desempenho por serviço</p>
                <p class="text-xs text-ink-600 mt-1">Os valores consideram somente atendimentos concluídos no período.</p>
            </div>
            @if (!empty($periodo['data_inicio']) || !empty($periodo['data_fim']))
                <p class="text-xs text-ink-600 font-medium">Período: {{ $periodo['data_inicio'] ?? 'início' }} até {{ $periodo['data_fim'] ?? 'hoje' }}</p>
            @endif
        </div>

        <table class="table-app min-w-[760px]">
            <thead>
                <tr>
                    <th>Serviço</th>
                    <th class="text-center">Agendamentos</th>
                    <th class="text-center">Concluídos</th>
                    <th class="text-center">Cancelados</th>
                    <th class="text-right">Valor bruto</th>
                    <th class="text-right">Repasse líquido</th>
                    <th class="text-right">Avaliação</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($porServico as $linha)
                    <tr>
                        <td>
                            <p class="font-bold text-ink-900">{{ $linha->servico->nome }}</p>
                            <p class="text-xs text-ink-600 mt-0.5">{{ $linha->servico->categoria?->nome_categoria ?? 'Sem categoria' }} · {{ $linha->servico->status === 'ativo' ? 'Ativo' : 'Inativo' }}</p>
                        </td>
                        <td class="text-center font-semibold">{{ $linha->agendamentos }}</td>
                        <td class="text-center text-emerald-700 font-bold">{{ $linha->concluidos }}</td>
                        <td class="text-center text-rose-700 font-bold">{{ $linha->cancelados }}</td>
                        <td class="text-right font-semibold">R$ {{ number_format($linha->valor_bruto, 2, ',', '.') }}</td>
                        <td class="text-right text-emerald-700 font-bold">R$ {{ number_format($linha->valor_liquido, 2, ',', '.') }}</td>
                        <td class="text-right">
                            @if ($linha->media_avaliacoes !== null)
                                <span class="font-bold text-amber-600">★ {{ number_format($linha->media_avaliacoes, 1, ',', '.') }}</span>
                                <span class="text-xs text-ink-500">({{ $linha->avaliacoes }})</span>
                            @else
                                <span class="text-ink-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-ink-500 py-10">Você ainda não possui serviços cadastrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
