<x-app-layout title="Histórico de Agendamentos" subtitle="Consulte e gerencie todos os seus pedidos de serviço">
    {{-- Filtro de Status --}}
    <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="flex-1 relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-ink-400">
                <i class="ti ti-search text-base"></i>
            </div>
            <input type="text" class="form-input !pl-10" placeholder="Filtrar ou pesquisar agendamentos..." disabled>
        </div>
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
                            <span>Prestador: <strong>{{ $agendamento->prestador->name }}</strong></span>
                        </p>
                        <div class="flex flex-wrap items-center gap-4 text-xs text-ink-600 mt-2.5">
                            <span class="inline-flex items-center gap-1"><i class="ti ti-calendar text-xs text-brand-600"></i> {{ $agendamento->data_hora->format('d/m/Y') }}</span>
                            <span class="inline-flex items-center gap-1"><i class="ti ti-clock text-xs text-brand-600"></i> {{ $agendamento->data_hora->format('H:i') }}</span>
                            <span class="inline-flex items-center gap-1 truncate max-w-xs" title="{{ $agendamento->endereco_servico }}">
                                <i class="ti ti-map-pin text-xs text-brand-600"></i>
                                <span>{{ $agendamento->endereco_servico }}</span>
                            </span>
                        </div>
                    </div>

                    {{-- Valor e Ações --}}
                    <div class="flex sm:flex-col items-center sm:items-end justify-between gap-3 shrink-0 pt-2 sm:pt-0 border-t sm:border-0 border-ink-100">
                        <p class="font-extrabold text-brand-600 text-lg">R$ {{ number_format($agendamento->preco_acordado, 2, ',', '.') }}</p>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('chat.index', ['agendamento' => $agendamento->id]) }}" class="btn-secondary btn-sm" title="Conversar com o prestador">
                                <i class="ti ti-message-circle text-sm"></i>
                                <span>Mensagens</span>
                            </a>

                            @if ($agendamento->status === 'concluido' && $agendamento->avaliacao)
                                <x-star-rating :value="$agendamento->avaliacao->nota" />
                            @elseif (in_array($agendamento->status, ['pendente', 'confirmado']))
                                <form method="POST" action="{{ route('cliente.agendamentos.cancelar', $agendamento) }}"
                                      onsubmit="return confirm('Tem certeza que deseja cancelar este agendamento?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-danger btn-sm">
                                        <i class="ti ti-x text-xs"></i>
                                        <span>Cancelar</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card p-12 text-center text-ink-600 bg-white shadow-sm">
                <div class="w-12 h-12 rounded-full bg-ink-100 flex items-center justify-center mx-auto mb-3">
                    <i class="ti ti-calendar-search text-ink-500 text-2xl"></i>
                </div>
                <p class="font-bold text-ink-800 text-base">Nenhum agendamento encontrado.</p>
                <p class="text-xs text-ink-500 mt-1">Experimente mudar os filtros ou agende um novo serviço.</p>
                <a href="{{ route('cliente.agendar') }}" class="btn-primary btn-sm mt-4 inline-flex">
                    <i class="ti ti-plus text-xs"></i>
                    <span>Agendar serviço</span>
                </a>
            </div>
        @endforelse
    </div>

    {{-- Paginação --}}
    <div class="mt-8">
        {{ $agendamentos->links() }}
    </div>
</x-app-layout>

