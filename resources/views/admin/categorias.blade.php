<x-app-layout title="Categorias de Serviço" subtitle="Organize e gerencie as áreas de atendimento da plataforma">
    {{-- Cadastro Rápido de Nova Categoria --}}
    <div class="card p-5 mb-8 bg-white shadow-sm border border-brand-200">
        <form method="POST" action="{{ route('admin.categorias.store') }}" class="flex flex-col sm:flex-row gap-3 items-center">
            @csrf
            <div class="relative flex-1 w-full">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-ink-400">
                    <i class="ti ti-tag text-base"></i>
                </div>
                <input type="text" name="nome_categoria" value="{{ old('nome_categoria') }}" required maxlength="100"
                       class="form-input !pl-10" placeholder="Nome da nova categoria (Ex: Hidráulica, Alvenaria, Pintura)">
            </div>
            <button type="submit" class="btn-primary w-full sm:w-auto shrink-0 !py-2.5">
                <i class="ti ti-plus text-base"></i>
                <span>Adicionar Categoria</span>
            </button>
        </form>
    </div>

    {{-- Grid de Categorias Cadastradas --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($categorias as $categoria)
            <div x-data="{ editing: false }" class="card p-4 bg-white shadow-sm hover:border-brand-300 transition-all">
                {{-- Modo Visualização --}}
                <div x-show="!editing" class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-bold text-ink-900 text-base leading-tight">{{ $categoria->nome_categoria }}</p>
                        <p class="text-xs text-ink-500 mt-1 flex items-center gap-1">
                            <i class="ti ti-briefcase text-xs"></i>
                            <span>{{ $categoria->servicos_count }} serviço(s) vinculado(s)</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <x-status-badge :status="$categoria->status" />
                        <button type="button" class="btn-secondary !p-1.5" @click="editing = true" title="Editar categoria">
                            <i class="ti ti-edit text-xs"></i>
                        </button>
                        <form method="POST" action="{{ route('admin.categorias.destroy', $categoria) }}"
                              onsubmit="return confirm('Deseja excluir esta categoria? Os serviços vinculados podem ser afetados.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger !p-1.5" title="Excluir categoria">
                                <i class="ti ti-trash text-xs"></i>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Modo Edição --}}
                <div x-show="editing" x-cloak>
                    <form method="POST" action="{{ route('admin.categorias.update', $categoria) }}" class="space-y-3">
                        @csrf @method('PATCH')
                        <div>
                            <label class="form-label text-xs font-semibold">Nome da Categoria</label>
                            <input type="text" name="nome_categoria" value="{{ $categoria->nome_categoria }}" required maxlength="100" class="form-input text-xs">
                        </div>
                        <div>
                            <label class="form-label text-xs font-semibold">Status</label>
                            <select name="status" class="form-select text-xs">
                                <option value="ativo" {{ $categoria->status === 'ativo' ? 'selected' : '' }}>Ativo</option>
                                <option value="inativo" {{ $categoria->status === 'inativo' ? 'selected' : '' }}>Inativo</option>
                            </select>
                        </div>
                        <div class="flex gap-2 pt-1">
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
            <div class="card p-12 text-center text-ink-600 sm:col-span-2 lg:col-span-3 bg-white shadow-sm">
                <div class="w-12 h-12 rounded-full bg-ink-100 flex items-center justify-center mx-auto mb-3">
                    <i class="ti ti-tag-off text-ink-500 text-2xl"></i>
                </div>
                <p class="font-bold text-ink-800 text-base">Nenhuma categoria cadastrada.</p>
                <p class="text-xs text-ink-500 mt-1">Utilize o campo acima para adicionar a primeira categoria do sistema.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>

