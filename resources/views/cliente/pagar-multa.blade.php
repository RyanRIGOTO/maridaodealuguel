<x-app-layout title="Pagar multa de cancelamento" subtitle="Regularize sua conta para voltar a agendar serviços">
    <div class="max-w-xl">
        <div class="card p-5 sm:p-6 bg-white shadow-sm">
            <dl class="divide-y divide-ink-200 text-sm border border-ink-200 rounded-xl p-5 bg-ink-50/40 mb-6">
                <div class="py-2.5 flex justify-between gap-4"><dt>Serviço</dt><dd class="font-bold text-right">{{ $agendamento->servico->nome ?? '—' }}</dd></div>
                <div class="py-2.5 flex justify-between gap-4"><dt>Profissional</dt><dd class="font-bold text-right">{{ $agendamento->prestador->name }}</dd></div>
                <div class="py-2.5 flex justify-between gap-4"><dt>Cancelado em</dt><dd class="text-right">{{ $agendamento->cancelamento->created_at->format('d/m/Y H:i') ?? '—' }}</dd></div>
                <div class="py-3 flex justify-between items-center gap-4"><dt class="font-bold">Valor da multa</dt><dd class="font-extrabold text-brand-600 text-xl">R$ {{ number_format($agendamento->cancelamento->multa, 2, ',', '.') }}</dd></div>
            </dl>

            <form method="POST" action="{{ route('cliente.multas.pagar.store', $agendamento) }}" class="space-y-4">
                @csrf
                <fieldset>
                    <legend class="form-label font-semibold text-ink-800">Forma de pagamento</legend>
                    <div class="grid sm:grid-cols-3 gap-2.5">
                        @foreach ($metodos as $valor => $nome)
                            <label class="flex items-center gap-2 border border-ink-300 rounded-lg px-3 py-2.5 text-sm cursor-pointer has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                <input type="radio" name="metodo" value="{{ $valor }}" required @checked(old('metodo', 'pix') === $valor)>
                                <span>{{ $nome }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('metodo')
                        <p class="form-error mt-1">{{ $message }}</p>
                    @enderror
                </fieldset>

                <p class="p-3 bg-ink-50 border border-ink-200 rounded-lg text-xs text-ink-700">
                    <i class="ti ti-info-circle"></i>
                    Pagamento simulado apenas.
                </p>

                <div class="flex items-center gap-3">
                    <button type="submit" class="btn-primary">
                        <i class="ti ti-lock text-base"></i>
                        <span>Pagar R$ {{ number_format($agendamento->cancelamento->multa, 2, ',', '.') }}</span>
                    </button>
                    <a href="{{ route('cliente.historico') }}" class="btn-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
