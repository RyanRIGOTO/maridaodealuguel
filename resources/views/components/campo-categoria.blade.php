@props(['categorias', 'selecionada' => null, 'id', 'compacto' => false])
{{-- Um único campo para pesquisar e escolher a categoria (combobox acessível).
     O texto digitado filtra a lista; o id escolhido vai no campo oculto categoria_id. --}}
@php($atual = $categorias->firstWhere('id', $selecionada))

<div data-combobox class="relative">
    <label for="{{ $id }}" class="form-label {{ $compacto ? 'text-xs' : 'font-semibold text-ink-800' }}">
        Categoria <span class="text-rose-500">*</span>
    </label>
    <input id="{{ $id }}" type="text" data-combobox-texto role="combobox" autocomplete="off" required
           aria-autocomplete="list" aria-expanded="false" aria-controls="{{ $id }}-opcoes"
           value="{{ $atual?->nome_categoria }}" placeholder="Digite para pesquisar a categoria..."
           class="form-input {{ $compacto ? 'text-xs' : '' }}">
    <input type="hidden" name="categoria_id" value="{{ $atual?->id }}" data-combobox-valor>

    <ul id="{{ $id }}-opcoes" role="listbox" hidden data-combobox-lista
        class="absolute z-30 mt-1 w-full max-h-60 overflow-y-auto rounded-lg border border-ink-300 bg-white shadow-lg text-sm">
        @foreach ($categorias as $categoria)
            <li id="{{ $id }}-opcao-{{ $categoria->id }}" role="option" aria-selected="false" data-valor="{{ $categoria->id }}"
                class="px-3.5 py-2 cursor-pointer hover:bg-brand-50 aria-selected:bg-brand-100 aria-selected:text-brand-800">{{ $categoria->nome_categoria }}</li>
        @endforeach
        <li data-combobox-vazio hidden class="px-3.5 py-2 text-ink-500">Nenhuma categoria encontrada.</li>
        <li data-combobox-info hidden class="px-3.5 py-2 text-xs text-ink-500 border-t border-ink-100"></li>
    </ul>
</div>
