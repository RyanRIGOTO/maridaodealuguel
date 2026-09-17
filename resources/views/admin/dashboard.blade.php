<x-app-layout title="Painel Administrativo" subtitle="Controle e monitoramento geral da plataforma">
    {{-- Cartões de Resumo / Estatísticas (Componentes Reutilizáveis) --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-5 mb-10">
        <x-stat-card
            label="Clientes Ativos"
            :value="number_format($totalClientes, 0, ',', '.')"
            icon="ti-users"
            color="brand"
        />

        <x-stat-card
            label="Prestadores Ativos"
            :value="number_format($totalPrestadores, 0, ',', '.')"
            icon="ti-briefcase"
            color="brand"
        />

        <x-stat-card
            label="Faturamento Total"
            value="R$ {{ number_format($faturamento, 2, ',', '.') }}"
            icon="ti-currency-dollar"
            color="brand"
        />

        <x-stat-card
            label="Serviços Realizados"
            :value="number_format($servicosRealizados, 0, ',', '.')"
            icon="ti-calendar-check"
            color="emerald"
        />
    </div>

    {{-- Seção de Alertas e Acesso Rápido --}}
    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Painel de Alertas --}}
        <div>
            <h2 class="text-lg font-bold text-ink-900 mb-4 flex items-center gap-2">
                <i class="ti ti-bell text-brand-600"></i>
                <span>Alertas e Moderação</span>
            </h2>

            <div class="card divide-y divide-ink-100 bg-white overflow-hidden shadow-sm">
                @forelse ($prestadoresEmRevisao as $prestador)
                    <div class="p-4 bg-amber-50/60 flex items-start gap-3">
                        <i class="ti ti-alert-triangle text-amber-700 text-lg shrink-0 mt-0.5"></i>
                        <p class="text-xs sm:text-sm text-amber-900">
                            Prestador <strong>{{ $prestador->name }}</strong> entrou em revisão
                            (nota média {{ number_format($prestador->prestadorProfile->reputacao_media, 1, ',', '.') }}).
                        </p>
                    </div>
                @empty
                @endforelse

                @forelse ($reclamacoesRecentes as $avaliacao)
                    <div class="p-4 bg-rose-50/60 flex items-start gap-3">
                        <i class="ti ti-star-off text-rose-700 text-lg shrink-0 mt-0.5"></i>
                        <p class="text-xs sm:text-sm text-rose-900">
                            Avaliação nota {{ $avaliacao->nota }} para <strong>{{ $avaliacao->prestador->name }}</strong>
                            enviada por {{ $avaliacao->cliente->name }}.
                        </p>
                    </div>
                @empty
                @endforelse

                @if ($prestadoresEmRevisao->isEmpty() && $reclamacoesRecentes->isEmpty())
                    <div class="p-8 text-center text-sm text-ink-600">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2">
                            <i class="ti ti-check text-xl"></i>
                        </div>
                        <p class="font-medium text-ink-800">Nenhum alerta pendente no sistema.</p>
                        <p class="text-xs text-ink-500 mt-0.5">Todos os prestadores e avaliações estão em conformidade.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Atalhos de Gestão --}}
        <div>
            <h2 class="text-lg font-bold text-ink-900 mb-4 flex items-center gap-2">
                <i class="ti ti-apps text-brand-600"></i>
                <span>Gestão Rápida</span>
            </h2>

            <div class="grid grid-cols-2 gap-4">
                <a href="{{ route('admin.usuarios') }}" class="card p-5 hover:border-brand-500 hover:shadow-md transition-all group bg-white">
                    <div class="w-10 h-10 rounded-full bg-brand-100 group-hover:bg-brand-500 flex items-center justify-center mb-3 transition-colors">
                        <i class="ti ti-users text-brand-600 group-hover:text-white text-xl transition-colors"></i>
                    </div>
                    <p class="font-bold text-ink-900 text-sm group-hover:text-brand-600 transition-colors">Gerenciar Usuários</p>
                    <p class="text-xs text-ink-600 mt-1">Clientes e prestadores</p>
                </a>

                <a href="{{ route('admin.categorias') }}" class="card p-5 hover:border-brand-500 hover:shadow-md transition-all group bg-white">
                    <div class="w-10 h-10 rounded-full bg-brand-100 group-hover:bg-brand-500 flex items-center justify-center mb-3 transition-colors">
                        <i class="ti ti-tag text-brand-600 group-hover:text-white text-xl transition-colors"></i>
                    </div>
                    <p class="font-bold text-ink-900 text-sm group-hover:text-brand-600 transition-colors">Categorias</p>
                    <p class="text-xs text-ink-600 mt-1">Adicionar e organizar</p>
                </a>

                <a href="{{ route('admin.relatorios') }}" class="card p-5 hover:border-brand-500 hover:shadow-md transition-all group bg-white col-span-2">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-brand-100 group-hover:bg-brand-500 flex items-center justify-center transition-colors shrink-0">
                            <i class="ti ti-chart-bar text-brand-600 group-hover:text-white text-xl transition-colors"></i>
                        </div>
                        <div>
                            <p class="font-bold text-ink-900 text-sm group-hover:text-brand-600 transition-colors">Relatórios Gerenciais</p>
                            <p class="text-xs text-ink-600 mt-0.5">Finanças, agendamentos, avaliações e relatórios auditáveis</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
