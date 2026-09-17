<x-app-layout title="Painel do Prestador" subtitle="Visão geral de seus atendimentos e faturamento">
    {{-- Cartões de Resumo / Estatísticas (Componentes Reutilizáveis) --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-5 mb-10">
        <x-stat-card
            label="Faturamento (Mês)"
            value="R$ {{ number_format($faturamentoMes, 2, ',', '.') }}"
            icon="ti-currency-dollar"
            color="brand"
        />

        <x-stat-card
            label="Agendamentos"
            :value="$totalAgendamentos"
            icon="ti-calendar-check"
            color="brand"
        />

        <x-stat-card
            label="Avaliação Média"
            :value="number_format($avaliacaoMedia, 1, ',', '.')"
            icon="ti-star-filled"
            color="amber"
        />

        <x-stat-card
            label="Concluídos"
            :value="$servicosConcluidos"
            icon="ti-circle-check"
            color="emerald"
        />
    </div>

    {{-- Próximos Atendimentos --}}
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-ink-900 tracking-tight flex items-center gap-2">
                <i class="ti ti-calendar-event text-brand-600"></i>
                <span>Próximos Agendamentos</span>
            </h2>
            <a href="{{ route('prestador.agendamentos') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 inline-flex items-center gap-1">
                <span>Ver todos</span>
                <i class="ti ti-arrow-right text-xs"></i>
            </a>
        </div>

        <div class="card divide-y divide-ink-100 bg-white shadow-sm">
            @forelse ($proximosAgendamentos as $agendamento)
                <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-ink-50/50 transition-colors">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-bold text-ink-900 text-sm sm:text-base">{{ $agendamento->servico->nome ?? 'Serviço' }}</p>
                            <x-status-badge :status="$agendamento->status" />
                        </div>
                        <p class="text-xs sm:text-sm text-ink-600 mt-1 flex items-center gap-1.5">
                            <i class="ti ti-user text-xs"></i>
                            <span>Cliente: <strong>{{ $agendamento->cliente->name }}</strong></span>
                        </p>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-ink-600 mt-2">
                            <span class="inline-flex items-center gap-1"><i class="ti ti-calendar text-xs text-brand-600"></i> {{ $agendamento->data_hora->format('d/m/Y') }}</span>
                            <span class="inline-flex items-center gap-1"><i class="ti ti-clock text-xs text-brand-600"></i> {{ $agendamento->data_hora->format('H:i') }}</span>
                            <span class="inline-flex items-center gap-1 font-semibold text-brand-700"><i class="ti ti-cash text-xs"></i> R$ {{ number_format($agendamento->preco_acordado, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    {{-- Ações Rápidas --}}
                    <div class="flex items-center gap-2 shrink-0">
                        @if ($agendamento->status === 'pendente')
                            <form method="POST" action="{{ route('prestador.agendamentos.confirmar', $agendamento) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn-success btn-sm">
                                    <i class="ti ti-check text-xs"></i>
                                    <span>Confirmar</span>
                                </button>
                            </form>
                        @elseif ($agendamento->status === 'confirmado')
                            <form method="POST" action="{{ route('prestador.agendamentos.concluir', $agendamento) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn-primary btn-sm">
                                    <i class="ti ti-circle-check text-xs"></i>
                                    <span>Concluir</span>
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('chat.index', ['agendamento' => $agendamento->id]) }}" class="btn-secondary btn-sm" title="Conversar com o cliente">
                            <i class="ti ti-message-circle text-sm"></i>
                        </a>
                    </div>
                </div>
            @empty
                <x-empty-state
                    icon="ti-calendar-check"
                    title="Nenhum agendamento pendente ou futuro no momento"
                    description="Quando clientes contratarem seus serviços, as solicitações aparecerão aqui."
                />
            @endforelse
        </div>
    </div>
</x-app-layout>
