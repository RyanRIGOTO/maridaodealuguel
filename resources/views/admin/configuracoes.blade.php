<x-app-layout title="Configurações" subtitle="Parâmetros gerais da plataforma">
    <div class="max-w-2xl">
        <div class="card p-5 sm:p-6 bg-white shadow-sm">
            <div class="flex items-center gap-2 mb-1">
                <i class="ti ti-percentage text-brand-600 text-xl"></i>
                <h2 class="font-bold text-ink-900 text-base">Taxa da plataforma</h2>
            </div>
            <p class="text-xs sm:text-sm text-ink-600 mb-5 leading-relaxed">
                Percentual retido pela plataforma em cada serviço concluído. O restante é repassado ao prestador.
                A alteração vale para os serviços concluídos depois dela; os recebimentos já gerados mantêm a taxa da época.
            </p>

            <form method="POST" action="{{ route('admin.configuracoes.taxa') }}" class="space-y-4"
                  onsubmit="return confirm('Confirmar a nova taxa da plataforma?');">
                  {{-- controle de sessão que protege de ataques, sobre tonken de validação --}}
                @csrf
                @method('PUT')

                <div class="sm:w-64">
                    <label for="taxa_plataforma" class="form-label font-semibold text-ink-800">Taxa (%)</label>
                    <input id="taxa_plataforma" type="number" name="taxa_plataforma" step="0.01" min="0" max="100" required
                           value="{{ old('taxa_plataforma', number_format($taxa, 2, '.', '')) }}" class="form-input">
                    @error('taxa_plataforma')
                        <p class="form-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- <div class="rounded-lg bg-ink-50 border border-ink-200 px-4 py-3 text-xs sm:text-sm text-ink-700">
                    Exemplo com a taxa atual: em um serviço de <strong>R$ 100,00</strong>, a plataforma fica com
                    <strong>R$ {{ number_format($taxa, 2, ',', '.') }}</strong> e o prestador recebe
                    <strong>R$ {{ number_format(100 - $taxa, 2, ',', '.') }}</strong>.
                </div> --}}

                <button type="submit" class="btn-primary">
                    <i class="ti ti-device-floppy text-base"></i>
                    <span>Salvar taxa</span>
                </button>
            </form>
        </div>

        {{-- RN4: política de cancelamento --}}
        <div class="card p-5 sm:p-6 bg-white shadow-sm mt-6">
            <div class="flex items-center gap-2 mb-1">
                <i class="ti ti-calendar-x text-brand-600 text-xl"></i>
                <h2 class="font-bold text-ink-900 text-base">Multa de cancelamento</h2>
            </div>
            <p class="text-xs sm:text-sm text-ink-600 mb-5 leading-relaxed">
                {{-- regra de negócio: multa de cancelamento --}}
                Percentual do valor do serviço cobrado quando o <strong>cliente</strong> cancela com menos de 24h de antecedência.
                A multa compensa o prestador e segue o fluxo financeiro normal (taxa da plataforma e liberação em 48h).
                Use 0 para não cobrar multa.
            </p>

            <form method="POST" action="{{ route('admin.configuracoes.multa') }}" class="space-y-4"
                  onsubmit="return confirm('Confirmar a nova multa de cancelamento?');">
                @csrf
                @method('PUT')

                <div class="sm:w-64">
                    <label for="multa_cancelamento" class="form-label font-semibold text-ink-800">Multa (%)</label>
                    <input id="multa_cancelamento" type="number" name="multa_cancelamento" step="0.01" min="0" max="100" required
                           value="{{ old('multa_cancelamento', number_format($multa, 2, '.', '')) }}" class="form-input">
                    @error('multa_cancelamento')
                        <p class="form-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-primary">
                    <i class="ti ti-device-floppy text-base"></i>
                    <span>Salvar multa</span>
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
