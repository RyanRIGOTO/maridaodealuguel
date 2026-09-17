<x-app-layout title="Avaliações de Serviços" subtitle="Avalie serviços concluídos e consulte suas avaliações anteriores">
    {{-- Serviços Aguardando Avaliação --}}
    <div class="mb-10">
        <h2 class="text-lg font-bold text-ink-900 tracking-tight flex items-center gap-2 mb-4">
            <i class="ti ti-star text-brand-600"></i>
            <span>Serviços Aguardando sua Avaliação</span>
        </h2>

        @forelse ($pendentes as $agendamento)
            <div class="card p-5 mb-4 bg-white shadow-sm border border-brand-300 ring-1 ring-brand-500/10" x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
                <div class="flex items-center justify-between gap-3 cursor-pointer" @click="open = !open">
                    <div class="min-w-0">
                        <p class="font-bold text-ink-900 text-base">{{ $agendamento->servico->nome ?? 'Serviço prestado' }}</p>
                        <p class="text-xs sm:text-sm text-ink-600 mt-0.5 flex items-center gap-1.5">
                            <i class="ti ti-user text-xs text-brand-600"></i>
                            <span>Prestador: <strong>{{ $agendamento->prestador->name }}</strong> · Concluído em {{ $agendamento->data_hora->format('d/m/Y') }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2 text-brand-600 font-semibold text-xs shrink-0">
                        <span x-text="open ? 'Ocultar formulário' : 'Avaliar agora'"></span>
                        <i class="ti" :class="open ? 'ti-chevron-up' : 'ti-chevron-down'"></i>
                    </div>
                </div>

                <div x-show="open" x-cloak class="mt-5 pt-5 border-t border-ink-100">
                    <form method="POST" action="{{ route('cliente.avaliacoes.store', $agendamento) }}" class="space-y-4">
                        @csrf
                        <div>
                            <p class="form-label font-semibold text-ink-800">Como você avalia este atendimento?</p>
                            <x-rating-input name="nota" />
                        </div>
                        <div>
                            <label class="form-label font-semibold text-ink-800" for="comentario-{{ $agendamento->id }}">Comentário (opcional)</label>
                            <textarea id="comentario-{{ $agendamento->id }}" name="comentario" rows="3" maxlength="2000"
                                      class="form-input" placeholder="Conte como foi a pontualidade, qualidade e atendimento do prestador..."></textarea>
                        </div>
                        <button type="submit" class="btn-primary !px-5">
                            <i class="ti ti-send text-sm"></i>
                            <span>Enviar Avaliação</span>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="card p-8 text-center text-ink-600 bg-white shadow-sm">
                <div class="w-10 h-10 rounded-full bg-[#EAF3DE] flex items-center justify-center mx-auto mb-2 text-[#27500A]">
                    <i class="ti ti-check text-xl"></i>
                </div>
                <p class="font-bold text-ink-800">Tudo em dia!</p>
                <p class="text-xs text-ink-500 mt-0.5">Você não possui nenhum serviço pendente de avaliação.</p>
            </div>
        @endforelse
    </div>

    {{-- Histórico de Avaliações Enviadas --}}
    <div>
        <h2 class="text-lg font-bold text-ink-900 tracking-tight flex items-center gap-2 mb-4">
            <i class="ti ti-history text-brand-600"></i>
            <span>Minhas Avaliações Enviadas</span>
        </h2>

        <div class="card divide-y divide-ink-100 bg-white shadow-sm">
            @forelse ($avaliadas as $agendamento)
                <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-ink-900 text-base">{{ $agendamento->servico->nome ?? 'Serviço prestado' }}</p>
                        <p class="text-xs text-ink-600 mt-0.5 flex items-center gap-1.5">
                            <i class="ti ti-user text-xs"></i>
                            <span>Profissional: <strong>{{ $agendamento->prestador->name }}</strong> · Avaliado em {{ optional($agendamento->avaliacao)->data_avaliacao?->format('d/m/Y') }}</span>
                        </p>
                        @if ($agendamento->avaliacao?->comentario)
                            <div class="mt-2.5 p-3 rounded-lg bg-ink-50 border border-ink-200 text-xs sm:text-sm text-ink-800 italic">
                                "{{ $agendamento->avaliacao->comentario }}"
                            </div>
                        @endif
                    </div>
                    <div class="shrink-0">
                        <x-star-rating :value="optional($agendamento->avaliacao)->nota ?? 0" />
                    </div>
                </div>
            @empty
                <div class="p-8 text-sm text-ink-500 text-center">Você ainda não avaliou nenhum serviço.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>

