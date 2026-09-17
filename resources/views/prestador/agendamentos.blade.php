<x-app-layout title="Todos os Agendamentos" subtitle="Gerencie solicitações, confirmações e finalizações de serviços">
    {{-- Filtro por Status --}}
    <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="sm:w-56">
            <select name="status" onchange="this.form.submit()" class="form-select">
                <option value="todos" {{ request('status', 'todos') === 'todos' ? 'selected' : '' }}>Todos os status</option>
                <option value="pendente" {{ request('status') === 'pendente' ? 'selected' : '' }}>Pendente</option>
                <option value="confirmado" {{ request('status') === 'confirmado' ? 'selected' : '' }}>Confirmado</option>
                <option value="concluido" {{ request('status') === 'concluido' ? 'selected' : '' }}>Concluído</option>
                <option value="cancelado" {{ request('status') === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
            </select>
        </div>
    </form>

    {{-- Lista de Agendamentos --}}
    <div class="card divide-y divide-ink-100 bg-white shadow-sm">
        @forelse ($agendamentos as $agendamento)
            <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-ink-50/50 transition-colors">
                <div class="min-w-0">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <p class="font-bold text-ink-900 text-base">{{ $agendamento->servico->nome ?? 'Serviço sob demanda' }}</p>
                        <x-status-badge :status="$agendamento->status" />
                    </div>
                    <p class="text-xs sm:text-sm text-ink-600 mt-1 flex items-center gap-1.5">
                        <i class="ti ti-user text-xs"></i>
                        <span>Cliente: <strong>{{ $agendamento->cliente->name }}</strong></span>
                    </p>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-ink-600 mt-2">
                        <span class="inline-flex items-center gap-1"><i class="ti ti-calendar text-xs text-brand-600"></i> {{ $agendamento->data_hora->format('d/m/Y') }}</span>
                        <span class="inline-flex items-center gap-1"><i class="ti ti-clock text-xs text-brand-600"></i> {{ $agendamento->data_hora->format('H:i') }}</span>
                        <span class="inline-flex items-center gap-1 font-bold text-brand-600"><i class="ti ti-cash text-xs"></i> R$ {{ number_format($agendamento->preco_acordado, 2, ',', '.') }}</span>
                        <span class="inline-flex items-center gap-1 truncate max-w-xs" title="{{ $agendamento->endereco_servico }}">
                            <i class="ti ti-map-pin text-xs text-brand-600"></i>
                            <span>{{ $agendamento->endereco_servico }}</span>
                        </span>
                    </div>
                </div>

                {{-- Ações do Prestador --}}
                <div class="flex items-center gap-2 shrink-0 pt-2 sm:pt-0 border-t sm:border-0 border-ink-100">
                    @if ($agendamento->status === 'pendente')
                        <form method="POST" action="{{ route('prestador.agendamentos.confirmar', $agendamento) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-success btn-sm">
                                <i class="ti ti-check text-xs"></i>
                                <span>Confirmar</span>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('prestador.agendamentos.cancelar', $agendamento) }}"
                              onsubmit="return confirm('Tem certeza que deseja recusar este agendamento?');">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-danger btn-sm">
                                <i class="ti ti-x text-xs"></i>
                                <span>Recusar</span>
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
                        <form method="POST" action="{{ route('prestador.agendamentos.cancelar', $agendamento) }}"
                              onsubmit="return confirm('Cancelar este agendamento confirmado?');">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-danger btn-sm">
                                <i class="ti ti-x text-xs"></i>
                                <span>Cancelar</span>
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('chat.index', ['agendamento' => $agendamento->id]) }}" class="btn-secondary btn-sm" title="Conversar com o cliente">
                        <i class="ti ti-message-circle text-sm"></i>
                    </a>
                </div>
            </div>
        @empty
            <div class="p-12 text-center text-ink-600">
                <div class="w-12 h-12 rounded-full bg-ink-100 flex items-center justify-center mx-auto mb-3">
                    <i class="ti ti-calendar-search text-ink-500 text-2xl"></i>
                </div>
                <p class="font-bold text-ink-800 text-base">Nenhum agendamento encontrado.</p>
                <p class="text-xs text-ink-500 mt-1">Aguarde novos clientes solicitarem seus serviços.</p>
            </div>
        @endforelse
    </div>

    {{-- Paginação --}}
    <div class="mt-8">
        {{ $agendamentos->links() }}
    </div>
</x-app-layout>

