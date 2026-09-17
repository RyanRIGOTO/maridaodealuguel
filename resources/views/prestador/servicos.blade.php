<x-app-layout title="Meus Serviços" subtitle="Cadastre e gerencie o catálogo de serviços que você oferece">
    {{-- Ação Superior: Adicionar Serviço --}}
    <div x-data="{ showCreate: false }" class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <p class="text-xs sm:text-sm text-ink-600">Total de serviços cadastrados: <strong>{{ $servicos->count() }}</strong></p>
            <button type="button" class="btn-primary" @click="showCreate = !showCreate">
                <i class="ti" :class="showCreate ? 'ti-x' : 'ti-plus'"></i>
                <span x-text="showCreate ? 'Fechar Formulário' : 'Novo Serviço'"></span>
            </button>
        </div>

        {{-- Formulário de Cadastro de Novo Serviço --}}
        <div x-show="showCreate" x-cloak class="card p-5 sm:p-6 mb-6 bg-white shadow-sm border border-brand-300 ring-1 ring-brand-500/10">
            <div class="flex items-center gap-2 mb-4 pb-3 border-b border-ink-100">
                <i class="ti ti-tool text-brand-600 text-xl"></i>
                <h2 class="font-bold text-ink-900 text-base">Cadastrar Novo Serviço</h2>
            </div>

            <form method="POST" action="{{ route('prestador.servicos.store') }}" class="space-y-4">
                @csrf
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nome" class="form-label font-semibold text-ink-800">Nome do Serviço <span class="text-rose-500">*</span></label>
                        <input id="nome" type="text" name="nome" value="{{ old('nome') }}" required maxlength="100"
                               class="form-input" placeholder="Ex: Instalação de luminárias e plafons">
                    </div>
                    <div>
                        <label for="categoria_id" class="form-label font-semibold text-ink-800">Categoria <span class="text-rose-500">*</span></label>
                        <select id="categoria_id" name="categoria_id" required class="form-select">
                            <option value="">Selecione a categoria</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}" {{ old('categoria_id') == $categoria->id ? 'selected' : '' }}>{{ $categoria->nome_categoria }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="descricao" class="form-label font-semibold text-ink-800">Descrição Detalhada <span class="text-rose-500">*</span></label>
                    <textarea id="descricao" name="descricao" rows="2" maxlength="2000" required class="form-input"
                              placeholder="Descreva o que está incluso no serviço, materiais necessários, etc.">{{ old('descricao') }}</textarea>
                </div>

                <div class="sm:w-64">
                    <label for="preco_sugerido" class="form-label font-semibold text-ink-800">Preço Sugerido (R$) <span class="text-rose-500">*</span></label>
                    <input id="preco_sugerido" type="number" step="0.01" min="0" max="99999.99" name="preco_sugerido"
                           value="{{ old('preco_sugerido') }}" required class="form-input" placeholder="0,00">
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-primary">
                        <i class="ti ti-device-floppy text-base"></i>
                        <span>Salvar Serviço</span>
                    </button>
                    <button type="button" class="btn-secondary" @click="showCreate = false">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Grid de Serviços Cadastrados --}}
    <div class="grid sm:grid-cols-2 gap-4">
        @forelse ($servicos as $servico)
            <div x-data="{ editing: false }" class="card p-5 bg-white shadow-sm hover:border-brand-300 transition-all flex flex-col justify-between">
                {{-- Modo Visualização --}}
                <div x-show="!editing" class="flex flex-col justify-between h-full">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-bold text-ink-900 text-base leading-tight">{{ $servico->nome }}</p>
                                <span class="inline-block px-2 py-0.5 mt-1 rounded bg-ink-100 text-ink-600 text-xs font-semibold">
                                    {{ $servico->categoria->nome_categoria }}
                                </span>
                            </div>
                            <x-status-badge :status="$servico->status" />
                        </div>
                        <p class="text-xs sm:text-sm text-ink-600 mt-3 line-clamp-2 leading-relaxed">{{ $servico->descricao }}</p>
                    </div>

                    <div class="flex items-center justify-between mt-5 pt-3 border-t border-ink-100">
                        <span class="font-extrabold text-brand-600 text-lg">R$ {{ number_format($servico->preco_sugerido, 2, ',', '.') }}</span>
                        <div class="flex items-center gap-1.5">
                            <button type="button" class="btn-secondary btn-sm" @click="editing = true" title="Editar serviço">
                                <i class="ti ti-edit text-xs"></i>
                                <span>Editar</span>
                            </button>
                            <form method="POST" action="{{ route('prestador.servicos.destroy', $servico) }}"
                                  onsubmit="return confirm('Deseja realmente excluir este serviço?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm" title="Excluir serviço">
                                    <i class="ti ti-trash text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Modo Edição Rápida --}}
                <div x-show="editing" x-cloak>
                    <div class="flex items-center gap-2 mb-3 pb-2 border-b border-ink-100">
                        <i class="ti ti-edit text-brand-600 text-base"></i>
                        <span class="text-xs font-bold text-ink-800 uppercase tracking-wider">Editar Serviço</span>
                    </div>

                    <form method="POST" action="{{ route('prestador.servicos.update', $servico) }}" class="space-y-3">
                        @csrf @method('PATCH')
                        <div>
                            <label class="form-label text-xs font-semibold">Nome do Serviço</label>
                            <input type="text" name="nome" value="{{ $servico->nome }}" required maxlength="100" class="form-input text-xs">
                        </div>
                        <div>
                            <label class="form-label text-xs font-semibold">Categoria</label>
                            <select name="categoria_id" required class="form-select text-xs">
                                @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria->id }}" {{ $servico->categoria_id == $categoria->id ? 'selected' : '' }}>{{ $categoria->nome_categoria }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-xs font-semibold">Descrição</label>
                            <textarea name="descricao" rows="2" maxlength="2000" required class="form-input text-xs">{{ $servico->descricao }}</textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="form-label text-xs font-semibold">Preço Sugerido (R$)</label>
                                <input type="number" step="0.01" min="0" max="99999.99" name="preco_sugerido" value="{{ $servico->preco_sugerido }}" required class="form-input text-xs">
                            </div>
                            <div>
                                <label class="form-label text-xs font-semibold">Status</label>
                                <select name="status" class="form-select text-xs">
                                    <option value="ativo" {{ $servico->status === 'ativo' ? 'selected' : '' }}>Ativo</option>
                                    <option value="inativo" {{ $servico->status === 'inativo' ? 'selected' : '' }}>Inativo</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="btn-primary btn-sm">
                                <i class="ti ti-check text-xs"></i>
                                <span>Salvar</span>
                            </button>
                            <button type="button" class="btn-secondary btn-sm" @click="editing = false">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="card p-12 text-center text-ink-600 sm:col-span-2 bg-white shadow-sm">
                <div class="w-12 h-12 rounded-full bg-ink-100 flex items-center justify-center mx-auto mb-3">
                    <i class="ti ti-briefcase-off text-ink-500 text-2xl"></i>
                </div>
                <p class="font-bold text-ink-800 text-base">Você ainda não cadastrou nenhum serviço.</p>
                <p class="text-xs text-ink-500 mt-1">Clique em "Novo Serviço" acima para começar a receber agendamentos.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>

