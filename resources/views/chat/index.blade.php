<x-app-layout title="Mensagens" subtitle="Comunicação direta em tempo real sobre seus agendamentos">
    <div class="card overflow-hidden bg-white shadow-sm border border-ink-200" style="height: calc(100vh - 240px); min-height: 480px;">
        <div class="flex h-full">
            {{-- Painel Lateral: Lista de conversas --}}
            <div class="w-full sm:w-80 border-r border-ink-200 flex flex-col {{ $agendamentoAtivo ? 'hidden sm:flex' : 'flex' }} bg-ink-50/30">
                <div class="p-3 border-b border-ink-200 bg-white">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-ink-400">
                            <i class="ti ti-search text-xs"></i>
                        </div>
                        <input type="text" class="form-input !py-1.5 !pl-8 text-xs" placeholder="Buscar conversas..." disabled>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-ink-100">
                    @forelse ($conversas as $conversa)
                        <a href="{{ route('chat.index', ['agendamento' => $conversa->agendamento_id]) }}"
                           class="flex items-center gap-3 p-3.5 hover:bg-ink-100/60 transition-colors {{ $agendamentoAtivo && $agendamentoAtivo->id === $conversa->agendamento_id ? 'bg-brand-50 border-r-2 border-brand-500' : '' }}">
                            <div class="w-10 h-10 rounded-full {{ $agendamentoAtivo && $agendamentoAtivo->id === $conversa->agendamento_id ? 'bg-brand-500 text-white' : 'bg-brand-100 text-brand-700' }} flex items-center justify-center font-bold text-sm shrink-0">
                                {{ strtoupper(substr($conversa->outro_usuario->name ?? '?', 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-bold text-ink-900 text-xs sm:text-sm truncate">{{ $conversa->outro_usuario->name ?? 'Usuário' }}</p>
                                    @if ($conversa->nao_lidas > 0)
                                        <span class="w-5 h-5 rounded-full bg-brand-500 text-white text-[10px] font-bold flex items-center justify-center shrink-0">{{ $conversa->nao_lidas }}</span>
                                    @endif
                                </div>
                                <p class="text-xs text-ink-600 truncate mt-0.5">{{ $conversa->ultima_mensagem ?? $conversa->servico_nome }}</p>
                            </div>
                        </a>
                    @empty
                        <div class="p-8 text-center text-xs text-ink-600">
                            <i class="ti ti-messages-off text-2xl text-ink-400 block mb-2"></i>
                            Nenhuma conversa ativa no momento.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Painel Principal: Thread da Conversa Ativa --}}
            <div class="flex-1 flex flex-col {{ $agendamentoAtivo ? 'flex' : 'hidden sm:flex' }} bg-white">
                @if ($agendamentoAtivo)
                    {{-- Cabeçalho da Conversa --}}
                    <div class="p-3.5 px-4 border-b border-ink-200 flex items-center justify-between bg-white shadow-xs">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('chat.index') }}" class="sm:hidden btn-secondary !p-1.5" title="Voltar para lista">
                                <i class="ti ti-arrow-left text-sm"></i>
                            </a>
                            <div class="w-9 h-9 rounded-full bg-brand-500 text-white flex items-center justify-center font-bold text-sm shrink-0">
                                {{ strtoupper(substr($outro->name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-ink-900 text-sm leading-none">{{ $outro->name ?? 'Usuário' }}</p>
                                <p class="text-xs text-brand-600 font-medium mt-1">{{ $agendamentoAtivo->servico->nome ?? 'Atendimento' }} · Agendamento #{{ $agendamentoAtivo->id }}</p>
                            </div>
                        </div>

                        <div class="shrink-0">
                            <x-status-badge :status="$agendamentoAtivo->status" />
                        </div>
                    </div>

                    {{-- Balões de Mensagens --}}
                    <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-3.5 bg-ink-50/40" id="thread-mensagens">
                        @forelse ($mensagens as $mensagem)
                            @php($minha = $mensagem->remetente_id === auth()->id())
                            <div class="flex {{ $minha ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[78%] {{ $minha ? 'bg-brand-500 text-white rounded-2xl rounded-tr-xs shadow-xs' : 'bg-white text-ink-900 border border-ink-200 rounded-2xl rounded-tl-xs shadow-xs' }} px-4 py-2.5 text-sm">
                                    <p class="leading-relaxed whitespace-pre-wrap">{{ $mensagem->mensagem }}</p>
                                    <p class="text-[10px] mt-1.5 {{ $minha ? 'text-brand-100' : 'text-ink-600' }} text-right">
                                        {{ $mensagem->data_hora->format('d/m H:i') }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12 text-xs text-ink-600">
                                <i class="ti ti-message-dots text-3xl text-brand-400 block mb-2"></i>
                                Envie a primeira mensagem para <strong>{{ $outro->name ?? 'este usuário' }}</strong>.
                            </div>
                        @endforelse
                    </div>

                    {{-- Barra de Envio de Mensagem --}}
                    <form method="POST" action="{{ route('chat.send', $agendamentoAtivo) }}" class="p-3 bg-white border-t border-ink-200 flex items-center gap-2">
                        @csrf
                        <input type="text" name="mensagem" required maxlength="2000" autocomplete="off"
                               class="form-input flex-1 !py-2 text-sm" placeholder="Digite sua mensagem para {{ $outro->name ?? 'o usuário' }}...">
                        <button type="submit" class="btn-primary !py-2 !px-4" title="Enviar mensagem">
                            <i class="ti ti-send text-base"></i>
                        </button>
                    </form>
                @else
                    {{-- Nenhuma conversa selecionada --}}
                    <div class="flex-1 flex flex-col items-center justify-center text-sm text-ink-500 p-8 text-center bg-ink-50/20">
                        <div class="w-14 h-14 rounded-full bg-ink-100 flex items-center justify-center mb-3">
                            <i class="ti ti-messages text-ink-500 text-3xl"></i>
                        </div>
                        <p class="font-bold text-ink-800 text-base">Nenhuma conversa selecionada</p>
                        <p class="text-xs text-ink-600 mt-1 max-w-sm">Escolha uma conversa na lista lateral para visualizar o histórico de mensagens e responder.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Auto scroll para o final das mensagens --}}
    <script>
        const thread = document.getElementById('thread-mensagens');
        if (thread) {
            thread.scrollTop = thread.scrollHeight;
        }
    </script>
</x-app-layout>

