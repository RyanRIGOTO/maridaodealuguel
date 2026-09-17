<x-app-layout title="Relatórios Gerenciais" subtitle="Módulos de análise, métricas e relatórios auditáveis">
    {{-- Módulos de Relatórios --}}
    <div class="grid sm:grid-cols-2 gap-4 mb-8">
        <a href="{{ route('admin.relatorios.agendamentos') }}" class="card p-5 hover:border-brand-500 hover:shadow-md transition-all group bg-white">
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl bg-brand-100 group-hover:bg-brand-500 flex items-center justify-center transition-colors shrink-0">
                    <i class="ti ti-clipboard-list text-brand-600 group-hover:text-white text-xl transition-colors"></i>
                </div>
                <div>
                    <p class="font-bold text-ink-900 text-base group-hover:text-brand-600 transition-colors">Serviços por Cliente / Prestador</p>
                    <p class="text-xs sm:text-sm text-ink-600 mt-1 leading-relaxed">Agendamentos filtrados por período, status, cliente e prestador com exportação e impressão.</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.relatorios.financeiro') }}" class="card p-5 hover:border-brand-500 hover:shadow-md transition-all group bg-white">
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl bg-brand-100 group-hover:bg-brand-500 flex items-center justify-center transition-colors shrink-0">
                    <i class="ti ti-cash text-brand-600 group-hover:text-white text-xl transition-colors"></i>
                </div>
                <div>
                    <p class="font-bold text-ink-900 text-base group-hover:text-brand-600 transition-colors">Movimentações Financeiras</p>
                    <p class="text-xs sm:text-sm text-ink-600 mt-1 leading-relaxed">Faturamento bruto da plataforma, comissão de 10% e repasse líquido para prestadores.</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.relatorios.avaliacoes') }}" class="card p-5 hover:border-brand-500 hover:shadow-md transition-all group bg-white">
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl bg-brand-100 group-hover:bg-brand-500 flex items-center justify-center transition-colors shrink-0">
                    <i class="ti ti-star text-brand-600 group-hover:text-white text-xl transition-colors"></i>
                </div>
                <div>
                    <p class="font-bold text-ink-900 text-base group-hover:text-brand-600 transition-colors">Avaliações e Reputação</p>
                    <p class="text-xs sm:text-sm text-ink-600 mt-1 leading-relaxed">Ranking de qualidade, notas médias e monitoramento de prestadores em risco de revisão.</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.relatorios.usuarios') }}" class="card p-5 hover:border-brand-500 hover:shadow-md transition-all group bg-white">
            <div class="flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl bg-brand-100 group-hover:bg-brand-500 flex items-center justify-center transition-colors shrink-0">
                    <i class="ti ti-users text-brand-600 group-hover:text-white text-xl transition-colors"></i>
                </div>
                <div>
                    <p class="font-bold text-ink-900 text-base group-hover:text-brand-600 transition-colors">Cadastros e Usuários</p>
                    <p class="text-xs sm:text-sm text-ink-600 mt-1 leading-relaxed">Listagem completa e estatísticas de clientes, prestadores e status cadastral.</p>
                </div>
            </div>
        </a>
    </div>

    {{-- Resumo Global --}}
    <h3 class="text-sm font-bold text-ink-700 uppercase tracking-wider mb-3">Indicadores Rápidos</h3>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach ($resumo as $label => $valor)
            <div class="stat-card">
                <p class="text-xs font-semibold text-ink-600 uppercase tracking-wider">{{ $label }}</p>
                <p class="text-xl sm:text-2xl font-bold text-ink-900 mt-1.5">{{ $valor }}</p>
            </div>
        @endforeach
    </div>
</x-app-layout>

