<x-app-layout title="Histórico de Agendamentos" subtitle="Consulte os atendimentos concluídos e cancelados">
    {{-- Filtro por Status --}}
    <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="sm:w-56">
            <select name="status" onchange="this.form.submit()" class="form-select">
                <option value="todos" {{ request('status', 'todos') === 'todos' ? 'selected' : '' }}>Todos os status</option>
                <option value="concluido" {{ request('status') === 'concluido' ? 'selected' : '' }}>Concluído</option>
                <option value="cancelado" {{ request('status') === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
            </select>
        </div>
    </form>

    {{-- Lista de Atendimentos Históricos --}}
    <div class="space-y-3.5">
        @forelse ($agendamentos as $agendamento)
            <div class="card p-4 sm:p-5 bg-white hover:border-brand-300 transition-all shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <p class="font-bold text-ink-900 text-base">{{ $agendamento->servico->nome ?? 'Serviço sob demanda' }}</p>
                            <x-status-badge :status="$agendamento->status" />
                        </div>
                        <p class="text-xs sm:text-sm text-ink-600 mt-1 flex items-center gap-1.5">
                            <i class="ti ti-user text-xs"></i>
                            <span>Cliente: <strong>{{ $agendamento->cliente->name }}</strong></span>
                        </p>
                        @if ($agendamento->status === 'cancelado' && $agendamento->cancelamento?->multa)
                            <p class="text-xs text-emerald-700 mt-1 flex items-center gap-1.5">
                                <i class="ti ti-receipt text-xs"></i>
                                <span>Cancelado pelo cliente com menos de 24h: multa de R$ {{ number_format($agendamento->cancelamento->multa, 2, ',', '.') }} a seu favor
                                    ({{ $agendamento->pagamentos->contains(fn ($p) => $p->tipo === 'multa' && $p->status === 'aprovado') ? 'paga pelo cliente' : 'aguardando pagamento do cliente' }})</span>
                            </p>
                        @endif
                        <div class="flex flex-wrap items-center gap-3 text-xs text-ink-600 mt-2">
                            <span class="inline-flex items-center gap-1"><i class="ti ti-calendar text-xs text-brand-600"></i> {{ $agendamento->data_hora->format('d/m/Y') }}</span>
                            <span class="inline-flex items-center gap-1"><i class="ti ti-clock text-xs text-brand-600"></i> {{ $agendamento->data_hora->format('H:i') }}</span>
                        </div>
                    </div>
                    <div class="text-left sm:text-right shrink-0 pt-2 sm:pt-0 border-t sm:border-0 border-ink-100">
                        <p class="font-extrabold text-brand-600 text-lg"><x-preco :valor="$agendamento->preco_acordado" /></p>
                        @if ($agendamento->avaliacao)
                            <div class="mt-1 flex items-center sm:justify-end gap-1">
                                <x-star-rating :value="$agendamento->avaliacao->nota" />
                                <span class="text-xs text-ink-600 font-semibold">({{ $agendamento->avaliacao->nota }}.0)</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="card p-12 text-center text-ink-600 bg-white shadow-sm">
                <div class="w-12 h-12 rounded-full bg-ink-100 flex items-center justify-center mx-auto mb-3">
                    <i class="ti ti-history-off text-ink-500 text-2xl"></i>
                </div>
                <p class="font-bold text-ink-800 text-base">Nenhum atendimento histórico encontrado.</p>
                <p class="text-xs text-ink-500 mt-1">Conclua seus agendamentos para visualizá-los aqui.</p>
            </div>
        @endforelse
    </div>

    {{-- Paginação --}}
    <div class="mt-8">
        {{ $agendamentos->links() }}
    </div>
</x-app-layout>

