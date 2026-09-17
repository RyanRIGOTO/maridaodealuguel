<x-guest-layout title="Maridão de Aluguel — Encontre profissionais de confiança para sua casa">
    {{-- Seção Hero / Apresentação Principal --}}
    <section class="bg-white border-b border-ink-300 relative overflow-hidden">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 text-center">
            {{-- Tagline de Destaque --}}
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-brand-50 border border-brand-200 text-brand-700 text-xs font-semibold uppercase tracking-wider mb-6">
                <i class="ti ti-sparkles text-sm"></i>
                <span>Marketplace de Serviços Residenciais</span>
            </div>

            <h1 class="text-3xl sm:text-5xl font-extrabold text-ink-900 max-w-3xl mx-auto tracking-tight leading-tight">
                Encontre profissionais de confiança para cuidar da sua casa
            </h1>
            <p class="text-ink-600 mt-5 max-w-2xl mx-auto text-base sm:text-lg leading-relaxed">
                Conectamos você com rapidez e segurança aos melhores prestadores de serviços de manutenção, elétrica, hidráulica e reparos gerais.
            </p>

            {{-- Botões de Ação Principal --}}
            <div class="mt-9 flex flex-col sm:flex-row items-center justify-center gap-3.5">
                @auth
                    <a href="{{ route(auth()->user()->role.'.dashboard') }}" class="btn-primary !px-6 !py-3 text-base shadow-md hover:shadow-lg">
                        <i class="ti ti-layout-dashboard text-lg"></i>
                        <span>Ir para o meu painel</span>
                    </a>
                @else
                    <a href="{{ route('register.cliente') }}" class="btn-primary !px-6 !py-3 text-base shadow-md hover:shadow-lg">
                        <i class="ti ti-calendar-plus text-lg"></i>
                        <span>Contratar um Serviço</span>
                    </a>
                    <a href="{{ route('register.prestador') }}" class="btn-secondary !px-6 !py-3 text-base">
                        <i class="ti ti-briefcase text-lg"></i>
                        <span>Quero Trabalhar</span>
                    </a>
                @endauth
            </div>

            {{-- 3 Pilares / Diferenciais --}}
            <div class="grid sm:grid-cols-3 gap-5 mt-16 max-w-4xl mx-auto text-left">
                <div class="card p-6 hover:border-brand-300 transition-all">
                    <div class="w-12 h-12 rounded-full bg-brand-100 flex items-center justify-center mb-4">
                        <i class="ti ti-shield-check text-brand-600 text-2xl" aria-hidden="true"></i>
                    </div>
                    <p class="font-bold text-ink-900 text-base">Profissionais Verificados</p>
                    <p class="text-sm text-ink-600 mt-1.5 leading-relaxed">Todos os prestadores passam por validação cadastral rigorosa.</p>
                </div>

                <div class="card p-6 hover:border-brand-300 transition-all">
                    <div class="w-12 h-12 rounded-full bg-brand-100 flex items-center justify-center mb-4">
                        <i class="ti ti-calendar-plus text-brand-600 text-2xl" aria-hidden="true"></i>
                    </div>
                    <p class="font-bold text-ink-900 text-base">Agendamento Fácil</p>
                    <p class="text-sm text-ink-600 mt-1.5 leading-relaxed">Escolha data, horário e receba confirmação direta pelo sistema.</p>
                </div>

                <div class="card p-6 hover:border-brand-300 transition-all">
                    <div class="w-12 h-12 rounded-full bg-brand-100 flex items-center justify-center mb-4">
                        <i class="ti ti-star text-brand-600 text-2xl" aria-hidden="true"></i>
                    </div>
                    <p class="font-bold text-ink-900 text-base">Avaliações Transparentes</p>
                    <p class="text-sm text-ink-600 mt-1.5 leading-relaxed">Veja notas e opiniões reais de clientes antes de fechar o serviço.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Seção de Categorias de Serviços --}}
    @if ($categorias->isNotEmpty())
        <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-ink-900 tracking-tight">Categorias Populares</h2>
                    <p class="text-sm text-ink-600 mt-1">Selecione uma especialidade para encontrar prestadores disponíveis</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ($categorias as $categoria)
                    <div class="card p-5 text-center hover:border-brand-500 hover:shadow-md transition-all group">
                        <div class="w-12 h-12 rounded-full bg-brand-100 group-hover:bg-brand-500 flex items-center justify-center mx-auto mb-3 transition-colors">
                            <i class="ti ti-tag text-brand-600 group-hover:text-white text-xl transition-colors"></i>
                        </div>
                        <p class="font-bold text-ink-900 text-sm group-hover:text-brand-600 transition-colors">{{ $categoria->nome_categoria }}</p>
                        <p class="text-xs text-ink-600 mt-1">{{ $categoria->servicos_count }} serviço(s) ativo(s)</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Seção de Serviços em Destaque --}}
    @if ($servicos->isNotEmpty())
        <section class="bg-white border-y border-ink-300 py-16">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-8 gap-2">
                    <div>
                        <h2 class="text-2xl font-bold text-ink-900 tracking-tight">Serviços em Destaque</h2>
                        <p class="text-sm text-ink-600 mt-1">Confira alguns dos serviços disponíveis em sua região</p>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach ($servicos as $servico)
                        <div class="card p-5 flex flex-col justify-between hover:border-brand-300 hover:shadow-md transition-all">
                            <div>
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-bold text-ink-900 text-base leading-tight">{{ $servico->nome }}</p>
                                        <span class="inline-block px-2.5 py-0.5 mt-1 rounded bg-ink-100 text-ink-600 text-xs font-medium">
                                            {{ $servico->categoria->nome_categoria }}
                                        </span>
                                    </div>
                                    <span class="text-brand-600 font-bold text-lg whitespace-nowrap">
                                        R$ {{ number_format($servico->preco_sugerido, 2, ',', '.') }}
                                    </span>
                                </div>
                                <p class="text-sm text-ink-600 mt-3 line-clamp-2 leading-relaxed">{{ $servico->descricao }}</p>
                            </div>

                            <div class="flex items-center gap-3 mt-5 pt-4 border-t border-ink-100">
                                <div class="w-9 h-9 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($servico->prestador->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-ink-800 truncate">{{ $servico->prestador->name }}</p>
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <x-star-rating :value="$servico->prestador->prestadorProfile->reputacao_media ?? 5" />
                                        <span class="text-xs text-ink-600 font-medium">({{ number_format($servico->prestador->prestadorProfile->reputacao_media ?? 5, 1) }})</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="text-center mt-12">
                    @auth
                        <a href="{{ route(auth()->user()->role.'.dashboard') }}" class="btn-primary !px-6 !py-3">
                            <i class="ti ti-calendar-plus text-lg"></i>
                            <span>Agendar no Meu Painel</span>
                        </a>
                    @else
                        <a href="{{ route('register.cliente') }}" class="btn-primary !px-6 !py-3 shadow-md hover:shadow-lg">
                            <i class="ti ti-user-plus text-lg"></i>
                            <span>Criar Conta e Agendar Agora</span>
                        </a>
                    @endauth
                </div>
            </div>
        </section>
    @endif
</x-guest-layout>

