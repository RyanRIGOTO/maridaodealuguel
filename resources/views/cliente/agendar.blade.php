<x-app-layout title="Agendar Serviço" subtitle="Siga as 4 etapas para concluir sua solicitação">
    <div class="max-w-2xl mx-auto">
        @if ($categorias->isEmpty())
            <div class="card p-10 text-center text-ink-600 bg-white">
                <i class="ti ti-tools-off text-ink-500 text-2xl"></i>
                <p class="font-semibold text-ink-900 text-base mt-3">Nenhum serviço disponível no momento.</p>
                <p class="text-xs text-ink-600 mt-1">Por favor, volte mais tarde ou tente outra busca.</p>
            </div>
        @else
            <noscript><p class="form-error mb-4">Ative o JavaScript do navegador para utilizar as etapas do agendamento.</p></noscript>
            {{-- O Blade monta o HTML; agendamento.js controla a seleção e as etapas. --}}
            <form data-agendamento method="POST" action="{{ route('cliente.agendar.store') }}" novalidate>
                @csrf
                <input type="hidden" name="servico_id" value="{{ old('servico_id') }}">
                <input type="hidden" name="hora" value="{{ old('hora') }}">

                <ol class="flex items-center justify-between gap-2 mb-8 px-4" aria-label="Etapas do agendamento">
                    @foreach (['Serviço', 'Prestador', 'Data e local', 'Confirmação'] as $titulo)
                        <li class="flex flex-col items-center gap-1 text-xs text-ink-600">
                            <span data-indicador-etapa="{{ $loop->iteration }}" class="wizard-step-dot"
                                  @if ($loop->first) aria-current="step" @endif>{{ $loop->iteration }}</span>
                            <span>{{ $titulo }}</span>
                        </li>
                    @endforeach
                </ol>

                {{-- Etapa 1: categoria e serviço. Os dados pessoais do prestador não são enviados ao HTML. --}}
                <section data-etapa="1" class="card p-6 bg-white shadow-sm">
                    <h2 tabindex="-1" class="font-bold text-ink-900 text-lg mb-4">1. Selecione a Categoria e o Serviço</h2>
                    <p class="form-label">Categorias</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-6">
                        @foreach ($categorias as $categoria)
                            <button type="button" data-categoria="{{ $categoria->id }}" aria-pressed="false"
                                    class="opcao-agendamento px-3 py-2.5 text-xs sm:text-sm text-center">
                                {{ $categoria->nome_categoria }}
                            </button>
                        @endforeach
                    </div>

                    @foreach ($categorias as $categoria)
                        <div data-lista-servicos="{{ $categoria->id }}" hidden>
                            <p class="form-label">Serviços Disponíveis</p>
                            <div class="space-y-2.5">
                                @foreach ($categoria->servicos as $servico)
                                    <button type="button" data-servico="{{ $servico->id }}"
                                            data-nome="{{ $servico->nome }}"
                                            data-preco="{{ $servico->preco_sugerido }}"
                                            data-profissional="{{ $servico->prestador->name }}"
                                            data-reputacao="{{ $servico->prestador->prestadorProfile?->reputacao_media ?? 5 }}"
                                            data-area="{{ $servico->prestador->prestadorProfile?->area_atuacao }}"
                                            data-descricao="{{ $servico->descricao }}"
                                            aria-pressed="false"
                                            class="opcao-agendamento w-full flex items-center justify-between gap-3 px-4 py-3 text-left">
                                        <span class="min-w-0">
                                            <span class="block font-bold text-sm">{{ $servico->nome }}</span>
                                            <span class="block text-xs mt-0.5">Profissional: {{ $servico->prestador->name }}</span>
                                        </span>
                                        <span class="font-bold text-base whitespace-nowrap">R$ {{ number_format($servico->preco_sugerido, 2, ',', '.') }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="mt-8 flex justify-end">
                        <button type="button" data-ir-etapa="2" class="btn-primary" disabled>Avançar <i class="ti ti-arrow-right"></i></button>
                    </div>
                </section>

                {{-- Etapa 2: os detalhes são copiados do serviço selecionado. --}}
                <section data-etapa="2" hidden class="card p-6 bg-white shadow-sm">
                    <h2 tabindex="-1" class="font-bold text-ink-900 text-lg mb-4">2. Detalhes do Prestador</h2>
                    <div class="border border-ink-200 rounded-xl p-5 bg-ink-50/40">
                        <div class="flex items-center gap-3.5 pb-4 border-b border-ink-200">
                            <div data-resumo="inicial" class="w-12 h-12 rounded-xl bg-brand-500 text-white flex items-center justify-center font-bold text-lg"></div>
                            <div>
                                <p data-resumo="profissional" class="font-bold text-ink-900 text-base"></p>
                                <p class="text-xs font-semibold text-amber-600 mt-0.5"><i class="ti ti-star-filled"></i> <span data-resumo="reputacao"></span> (Avaliação média)</p>
                            </div>
                        </div>
                        <dl class="grid grid-cols-2 gap-4 mt-4 text-sm">
                            <div><dt class="text-ink-500">Serviço Selecionado</dt><dd data-resumo="nome" class="font-bold mt-0.5"></dd></div>
                            <div><dt class="text-ink-500">Valor Sugerido</dt><dd data-resumo="preco" class="text-brand-600 font-bold mt-0.5"></dd></div>
                            <div data-regiao class="col-span-2"><dt class="text-ink-500">Região de Atuação</dt><dd data-resumo="area" class="font-medium mt-0.5"></dd></div>
                        </dl>
                        <p data-resumo="descricao" class="text-xs text-ink-600 mt-3 pt-3 border-t border-ink-200"></p>
                    </div>
                    <div class="mt-8 flex justify-between">
                        <button type="button" data-ir-etapa="1" class="btn-secondary"><i class="ti ti-arrow-left"></i> Voltar</button>
                        <button type="button" data-ir-etapa="3" class="btn-primary">Avançar <i class="ti ti-arrow-right"></i></button>
                    </div>
                </section>

                {{-- Etapa 3: campos HTML comuns, enviados pelo formulário. --}}
                <section data-etapa="3" hidden class="card p-6 bg-white shadow-sm">
                    <h2 tabindex="-1" class="font-bold text-ink-900 text-lg mb-4">3. Escolha a Data, Horário e Local</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="form-label" for="agendamento-data">Data do Atendimento</label>
                            <input id="agendamento-data" name="data" type="date" required value="{{ old('data') }}"
                                   min="{{ now()->format('Y-m-d') }}" class="form-input">
                        </div>
                        <div>
                            <p class="form-label">Horário do Atendimento</p>
                            <div class="grid grid-cols-4 gap-2">
                                @foreach (['08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'] as $hora)
                                    <button type="button" data-horario="{{ $hora }}" aria-pressed="false"
                                            class="opcao-agendamento px-2.5 py-2 text-xs sm:text-sm">{{ $hora }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="form-label" for="agendamento-endereco">Endereço do Local de Atendimento</label>
                            <textarea id="agendamento-endereco" name="endereco_servico" rows="2" maxlength="255" required class="form-input"
                                      placeholder="Rua, número, complemento, bairro, cidade - UF">{{ old('endereco_servico') }}</textarea>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-between">
                        <button type="button" data-ir-etapa="2" class="btn-secondary"><i class="ti ti-arrow-left"></i> Voltar</button>
                        <button type="button" data-ir-etapa="4" class="btn-primary" disabled>Avançar <i class="ti ti-arrow-right"></i></button>
                    </div>
                </section>

                {{-- Etapa 4: revisão e envio para a validação do Laravel. --}}
                <section data-etapa="4" hidden class="card p-6 bg-white shadow-sm">
                    <h2 tabindex="-1" class="font-bold text-ink-900 text-lg mb-4">4. Confirmar e Concluir</h2>
                    <dl class="divide-y divide-ink-200 text-sm border border-ink-200 rounded-xl p-5 bg-ink-50/40">
                        <div class="py-2.5 flex justify-between gap-4"><dt>Serviço</dt><dd data-resumo="nome" class="font-bold text-right"></dd></div>
                        <div class="py-2.5 flex justify-between gap-4"><dt>Profissional</dt><dd data-resumo="profissional" class="font-bold text-right"></dd></div>
                        <div class="py-2.5 flex justify-between gap-4"><dt>Data e Hora</dt><dd data-resumo="dataHora" class="font-bold text-right"></dd></div>
                        <div class="py-2.5 flex justify-between gap-4"><dt>Local</dt><dd data-resumo="endereco" class="font-semibold text-right"></dd></div>
                        <div class="py-3 flex justify-between items-center gap-4"><dt class="font-bold">Valor Total Estimado</dt><dd data-resumo="preco" class="font-extrabold text-brand-600 text-xl"></dd></div>
                    </dl>
                    <p class="mt-4 p-3 bg-brand-50 border border-brand-200 rounded-lg text-xs text-brand-800">Após confirmar, o pedido ficará como <strong>"Pendente"</strong> até a confirmação do prestador de serviços.</p>
                    <div class="mt-8 flex justify-between">
                        <button type="button" data-ir-etapa="3" class="btn-secondary"><i class="ti ti-arrow-left"></i> Voltar</button>
                        <button type="submit" class="btn-primary"><i class="ti ti-check"></i> Confirmar Agendamento</button>
                    </div>
                </section>
            </form>
        @endif
    </div>
</x-app-layout>
