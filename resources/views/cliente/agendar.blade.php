<x-app-layout title="Agendar Serviço" subtitle="Siga as 4 etapas para concluir sua solicitação">
    {{-- Assistente de Agendamento em 4 Etapas (Alpine.js State Machine) --}}
    <div
        x-data="agendamentoWizard(@js($dadosWizard))"
        x-cloak
        class="max-w-2xl mx-auto"
    >
        {{-- Indicador de Progresso Superior --}}
        <div class="flex items-center justify-center gap-2 mb-8 px-4">
            <template x-for="n in 4" :key="n">
                <div class="flex items-center" :class="n < 4 ? 'flex-1' : ''">
                    <div class="wizard-step-dot font-bold shadow-sm"
                         :class="step >= n ? 'bg-brand-500 border-brand-500 text-white' : 'bg-white border-ink-300 text-ink-500'"
                         x-text="n"></div>
                    <template x-if="n < 4">
                        <div class="flex-1 h-1 mx-2 rounded-full transition-colors" :class="step > n ? 'bg-brand-500' : 'bg-ink-200'"></div>
                    </template>
                </div>
            </template>
        </div>

        @if ($categorias->isEmpty())
            <div class="card p-10 text-center text-ink-600 bg-white">
                <div class="w-12 h-12 rounded-full bg-ink-100 flex items-center justify-center mx-auto mb-3">
                    <i class="ti ti-tools-off text-ink-500 text-2xl"></i>
                </div>
                <p class="font-semibold text-ink-900 text-base">Nenhum serviço disponível no momento.</p>
                <p class="text-xs text-ink-600 mt-1">Por favor, volte mais tarde ou tente outra busca.</p>
            </div>
        @else
            <form method="POST" action="{{ route('cliente.agendar.store') }}" @submit="if (step !== 4) $event.preventDefault()">
                @csrf
                <input type="hidden" name="servico_id" :value="servicoId">
                <input type="hidden" name="data" :value="data">
                <input type="hidden" name="hora" :value="hora">
                <input type="hidden" name="endereco_servico" :value="endereco">

                {{-- ETAPA 1: Seleção de Categoria e Serviço --}}
                <div x-show="step === 1" class="card p-6 bg-white shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="ti ti-tag text-brand-600 text-xl"></i>
                        <h2 class="font-bold text-ink-900 text-lg">1. Selecione a Categoria e o Serviço</h2>
                    </div>

                    <p class="form-label text-xs uppercase tracking-wider text-ink-600 font-semibold mb-2">Categorias</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-6">
                        <template x-for="categoria in categorias" :key="categoria.id">
                            <button type="button" @click="selecionarCategoria(categoria.id)"
                                    class="px-3 py-2.5 rounded-lg border text-xs sm:text-sm font-semibold text-center transition-all duration-150"
                                    :class="categoriaId === categoria.id ? 'bg-brand-500 border-brand-500 text-white shadow-sm' : 'bg-white border-ink-300 text-ink-700 hover:border-brand-400 hover:bg-brand-50/50'">
                                <span x-text="categoria.nome"></span>
                            </button>
                        </template>
                    </div>

                    <template x-if="categoriaId">
                        <div>
                            <p class="form-label text-xs uppercase tracking-wider text-ink-600 font-semibold mb-2">Serviços Disponíveis</p>
                            <div class="space-y-2.5">
                                <template x-for="servico in servicosDaCategoria" :key="servico.id">
                                    <button type="button" @click="selecionarServico(servico.id)"
                                            class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-lg border text-left transition-all duration-150"
                                            :class="servicoId === servico.id ? 'border-brand-500 bg-brand-50/80 shadow-sm ring-1 ring-brand-500' : 'border-ink-200 bg-white hover:border-brand-300 hover:bg-ink-50/50'">
                                        <div class="min-w-0">
                                            <span class="block font-bold text-ink-900 text-sm" x-text="servico.nome"></span>
                                            <span class="block text-xs text-ink-600 mt-0.5" x-text="'Profissional: ' + servico.prestador.nome"></span>
                                        </div>
                                        <span class="font-bold text-brand-600 text-base whitespace-nowrap" x-text="'R$ ' + servico.preco.toFixed(2).replace('.', ',')"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div class="mt-8 flex justify-end">
                        <button type="button" class="btn-primary !px-5" :disabled="!servicoId" @click="step = 2">
                            <span>Avançar</span>
                            <i class="ti ti-arrow-right text-base"></i>
                        </button>
                    </div>
                </div>

                {{-- ETAPA 2: Confirmação do Prestador --}}
                <div x-show="step === 2" class="card p-6 bg-white shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="ti ti-user-check text-brand-600 text-xl"></i>
                        <h2 class="font-bold text-ink-900 text-lg">2. Detalhes do Prestador</h2>
                    </div>

                    <template x-if="servicoSelecionado">
                        <div class="border border-ink-200 rounded-xl p-5 bg-ink-50/40">
                            <div class="flex items-center gap-3.5 pb-4 border-b border-ink-200">
                                <div class="w-12 h-12 rounded-xl bg-brand-500 text-white flex items-center justify-center font-bold text-lg shrink-0 shadow-sm"
                                     x-text="servicoSelecionado.prestador.nome.charAt(0).toUpperCase()"></div>
                                <div class="min-w-0">
                                    <p class="font-bold text-ink-900 text-base" x-text="servicoSelecionado.prestador.nome"></p>
                                    <p class="text-xs font-semibold text-amber-600 flex items-center gap-1 mt-0.5">
                                        <i class="ti ti-star-filled text-amber-500"></i>
                                        <span x-text="servicoSelecionado.prestador.reputacao.toFixed(1) + ' (Avaliação média)'"></span>
                                    </p>
                                </div>
                            </div>
                            <dl class="grid grid-cols-2 gap-4 mt-4 text-sm">
                                <div>
                                    <dt class="text-xs text-ink-500 uppercase tracking-wider font-semibold">Serviço Selecionado</dt>
                                    <dd class="text-ink-900 font-bold mt-0.5" x-text="servicoSelecionado.nome"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-ink-500 uppercase tracking-wider font-semibold">Valor Sugerido</dt>
                                    <dd class="text-brand-600 font-bold text-base mt-0.5" x-text="'R$ ' + servicoSelecionado.preco.toFixed(2).replace('.', ',')"></dd>
                                </div>
                                <div class="col-span-2" x-show="servicoSelecionado.prestador.area_atuacao">
                                    <dt class="text-xs text-ink-500 uppercase tracking-wider font-semibold">Região de Atuação</dt>
                                    <dd class="text-ink-800 font-medium mt-0.5" x-text="servicoSelecionado.prestador.area_atuacao"></dd>
                                </div>
                            </dl>
                            <p class="text-xs text-ink-600 mt-3 pt-3 border-t border-ink-200 leading-relaxed" x-show="servicoSelecionado.descricao" x-text="servicoSelecionado.descricao"></p>
                        </div>
                    </template>

                    <div class="mt-8 flex justify-between">
                        <button type="button" class="btn-secondary !px-5" @click="step = 1">
                            <i class="ti ti-arrow-left text-base"></i>
                            <span>Voltar</span>
                        </button>
                        <button type="button" class="btn-primary !px-5" @click="step = 3">
                            <span>Avançar</span>
                            <i class="ti ti-arrow-right text-base"></i>
                        </button>
                    </div>
                </div>

                {{-- ETAPA 3: Data, Horário e Endereço --}}
                <div x-show="step === 3" class="card p-6 bg-white shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="ti ti-calendar text-brand-600 text-xl"></i>
                        <h2 class="font-bold text-ink-900 text-lg">3. Escolha a Data, Horário e Local</h2>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="form-label font-semibold text-ink-800" for="wizard-data">Data do Atendimento</label>
                            <input id="wizard-data" type="date" x-model="data" min="{{ now()->format('Y-m-d') }}" class="form-input">
                        </div>

                        <div>
                            <p class="form-label font-semibold text-ink-800">Horário Disponível</p>
                            <div class="grid grid-cols-4 gap-2">
                                <template x-for="slot in horarios" :key="slot">
                                    <button type="button" @click="hora = slot"
                                            class="px-2.5 py-2 rounded-lg border text-xs sm:text-sm font-semibold transition-all duration-150"
                                            :class="hora === slot ? 'bg-brand-500 border-brand-500 text-white shadow-sm' : 'bg-white border-ink-300 text-ink-700 hover:border-brand-400 hover:bg-brand-50/40'"
                                            x-text="slot"></button>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label class="form-label font-semibold text-ink-800" for="wizard-endereco">Endereço do Local de Atendimento</label>
                            <textarea id="wizard-endereco" x-model="endereco" rows="2" maxlength="255" class="form-input"
                                      placeholder="Rua, número, complemento, bairro, cidade - UF"></textarea>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between">
                        <button type="button" class="btn-secondary !px-5" @click="step = 2">
                            <i class="ti ti-arrow-left text-base"></i>
                            <span>Voltar</span>
                        </button>
                        <button type="button" class="btn-primary !px-5" :disabled="!data || !hora || !endereco" @click="step = 4">
                            <span>Avançar</span>
                            <i class="ti ti-arrow-right text-base"></i>
                        </button>
                    </div>
                </div>

                {{-- ETAPA 4: Confirmação e Envio --}}
                <div x-show="step === 4" class="card p-6 bg-white shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="ti ti-circle-check text-brand-600 text-xl"></i>
                        <h2 class="font-bold text-ink-900 text-lg">4. Confirmar e Concluir</h2>
                    </div>

                    <template x-if="servicoSelecionado">
                        <div class="border border-ink-200 rounded-xl p-5 bg-ink-50/40">
                            <dl class="divide-y divide-ink-200 text-sm">
                                <div class="py-2.5 flex justify-between"><dt class="text-ink-500 font-medium">Serviço</dt><dd class="font-bold text-ink-900" x-text="servicoSelecionado.nome"></dd></div>
                                <div class="py-2.5 flex justify-between"><dt class="text-ink-500 font-medium">Profissional</dt><dd class="font-bold text-ink-900" x-text="servicoSelecionado.prestador.nome"></dd></div>
                                <div class="py-2.5 flex justify-between"><dt class="text-ink-500 font-medium">Data e Hora</dt><dd class="font-bold text-ink-900" x-text="dataFormatada + ' às ' + hora"></dd></div>
                                <div class="py-2.5 flex justify-between gap-4"><dt class="text-ink-500 font-medium shrink-0">Local</dt><dd class="font-semibold text-ink-900 text-right" x-text="endereco"></dd></div>
                                <div class="py-3 flex justify-between items-center"><dt class="text-ink-700 font-bold text-base">Valor Total Estimado</dt><dd class="font-extrabold text-brand-600 text-xl" x-text="'R$ ' + servicoSelecionado.preco.toFixed(2).replace('.', ',')"></dd></div>
                            </dl>
                        </div>
                    </template>

                    <div class="mt-4 p-3 bg-brand-50 border border-brand-200 rounded-lg flex items-start gap-2.5">
                        <i class="ti ti-info-circle text-brand-600 text-base shrink-0 mt-0.5"></i>
                        <p class="text-xs text-brand-800">Após confirmar, o pedido ficará como <strong>"Pendente"</strong> até a confirmação do prestador de serviços.</p>
                    </div>

                    <div class="mt-8 flex justify-between">
                        <button type="button" class="btn-secondary !px-5" @click="step = 3">
                            <i class="ti ti-arrow-left text-base"></i>
                            <span>Voltar</span>
                        </button>
                        <button type="submit" class="btn-primary !px-6 !py-2.5 text-base shadow-md">
                            <i class="ti ti-check text-lg"></i>
                            <span>Confirmar Agendamento</span>
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    {{-- Script do Wizard com Alpine.js --}}
    <script>
        /**
         * Controla o fluxo do assistente de agendamento em 4 fases no cliente.
         */
        function agendamentoWizard(categorias) {
            return {
                step: 1,
                categorias,
                categoriaId: null,
                servicoId: null,
                data: '',
                hora: '',
                endereco: '',
                horarios: ['08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'],

                get categoriaAtual() {
                    return this.categorias.find(c => c.id === this.categoriaId) || null;
                },
                get servicosDaCategoria() {
                    return this.categoriaAtual ? this.categoriaAtual.servicos : [];
                },
                get servicoSelecionado() {
                    for (const categoria of this.categorias) {
                        const encontrado = categoria.servicos.find(s => s.id === this.servicoId);
                        if (encontrado) return encontrado;
                    }
                    return null;
                },
                get dataFormatada() {
                    if (!this.data) return '';
                    const [ano, mes, dia] = this.data.split('-');
                    return `${dia}/${mes}/${ano}`;
                },
                selecionarCategoria(id) {
                    this.categoriaId = id;
                    this.servicoId = null;
                },
                selecionarServico(id) {
                    this.servicoId = id;
                },
            };
        }
    </script>
</x-app-layout>

