<x-app-layout title="Painel do Cliente" subtitle="Acompanhe seus serviços e agendamentos">
    {{-- Barra de Busca Rápida --}}
    <a href="{{ route('cliente.agendar') }}" class="card p-3.5 mb-8 flex items-center gap-3 text-ink-600 hover:border-brand-500 hover:text-brand-600 transition-all group shadow-sm bg-white">
        <div class="w-8 h-8 rounded-lg bg-brand-100 group-hover:bg-brand-500 flex items-center justify-center transition-colors shrink-0">
            <i class="ti ti-search text-brand-600 group-hover:text-white text-base transition-colors"></i>
        </div>
        <span class="text-sm font-medium">Buscar serviços, categorias ou profissionais disponíveis para agendamento...</span>
    </a>

    {{-- Cartões de Resumo / Estatísticas (Componentes Reutilizáveis) --}}
    <div class="grid sm:grid-cols-3 gap-5 mb-10">
        <x-stat-card
            label="Serviços Contratados"
            :value="$servicosContratados"
            icon="ti-briefcase"
            color="brand"
        />

        <x-stat-card
            label="Agendamentos Pendentes"
            :value="$agendamentosPendentes"
            icon="ti-clock"
            color="amber"
        />

        <x-stat-card
            label="Avaliações Pendentes"
            :value="$avaliacoesFaltando"
            icon="ti-star"
            color="brand"
            :hint="$avaliacoesFaltando > 0 ? 'Clique em Avaliações no menu para avaliar' : null"
        />
    </div>

    {{-- Categorias Populares --}}
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-ink-900 tracking-tight flex items-center gap-2">
                <i class="ti ti-tag text-brand-600"></i>
                <span>Categorias em Destaque</span>
            </h2>
            <a href="{{ route('cliente.agendar') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 inline-flex items-center gap-1">
                <span>Ver todas</span>
                <i class="ti ti-arrow-right text-xs"></i>
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @forelse ($servicosPopulares as $categoria)
                <a href="{{ route('cliente.agendar') }}" class="card p-4 text-center hover:border-brand-500 hover:shadow-md transition-all group bg-white">
                    <div class="w-10 h-10 rounded-full bg-brand-100 group-hover:bg-brand-500 flex items-center justify-center mx-auto mb-2 transition-colors">
                        <i class="ti ti-tools text-brand-600 group-hover:text-white text-lg transition-colors"></i>
                    </div>
                    <p class="font-bold text-ink-900 text-sm group-hover:text-brand-600 transition-colors">{{ $categoria->nome_categoria }}</p>
                    <p class="text-xs text-ink-600 mt-0.5">{{ $categoria->servicos_count }} serviço(s)</p>
                </a>
            @empty
                <p class="text-sm text-ink-600 col-span-full py-4 text-center">Nenhuma categoria disponível no momento.</p>
            @endforelse
        </div>
    </div>

    {{-- Agendamentos Recentes --}}
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-ink-900 tracking-tight flex items-center gap-2">
                <i class="ti ti-calendar-check text-brand-600"></i>
                <span>Agendamentos Recentes</span>
            </h2>
            <a href="{{ route('cliente.historico') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 inline-flex items-center gap-1">
                <span>Ver histórico completo</span>
                <i class="ti ti-arrow-right text-xs"></i>
            </a>
        </div>

        <div class="card divide-y divide-ink-100 bg-white">
            @forelse ($agendamentosRecentes as $agendamento)
                <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-ink-50/50 transition-colors">
                    <div class="min-w-0">
                        <p class="font-bold text-ink-900 text-sm sm:text-base">{{ $agendamento->servico->nome ?? 'Serviço sob demanda' }}</p>
                        <p class="text-xs sm:text-sm text-ink-600 mt-0.5 flex items-center gap-1.5">
                            <i class="ti ti-user text-xs"></i>
                            <span>Prestador: <strong>{{ $agendamento->prestador->name }}</strong></span>
                        </p>
                    </div>
                    <div class="flex items-center sm:flex-col sm:items-end justify-between gap-2 shrink-0">
                        <span class="text-xs text-ink-600 font-medium flex items-center gap-1">
                            <i class="ti ti-calendar text-xs"></i>
                            {{ $agendamento->data_hora->format('d/m/Y H:i') }}
                        </span>
                        <x-status-badge :status="$agendamento->status" />
                    </div>
                </div>
            @empty
                <x-empty-state
                    icon="ti-calendar-x"
                    title="Você ainda não tem agendamentos"
                    description="Encontre os melhores profissionais para pequenos reparos, manutenção e serviços gerais."
                >
                    <x-slot:action>
                        <a href="{{ route('cliente.agendar') }}" class="btn-primary btn-sm inline-flex">
                            <i class="ti ti-plus text-xs"></i>
                            <span>Agendar primeiro serviço</span>
                        </a>
                    </x-slot:action>
                </x-empty-state>
            @endforelse
        </div>
    </div>
</x-app-layout>
