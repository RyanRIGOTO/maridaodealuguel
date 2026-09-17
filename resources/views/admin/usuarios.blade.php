<x-app-layout title="Gerenciar Usuários" subtitle="Controle e moderação de contas de clientes e prestadores">
    {{-- Filtro de Pesquisa e Papéis --}}
    <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="flex-1 relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-ink-400">
                <i class="ti ti-search text-base"></i>
            </div>
            <input type="text" name="search" value="{{ request('search') }}" class="form-input !pl-10" placeholder="Buscar por nome ou e-mail...">
        </div>
        <div class="sm:w-52">
            <select name="role" onchange="this.form.submit()" class="form-select">
                <option value="todos" {{ request('role', 'todos') === 'todos' ? 'selected' : '' }}>Todos os tipos</option>
                <option value="cliente" {{ request('role') === 'cliente' ? 'selected' : '' }}>Clientes</option>
                <option value="prestador" {{ request('role') === 'prestador' ? 'selected' : '' }}>Prestadores</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administradores</option>
            </select>
        </div>
        <button type="submit" class="btn-primary">
            <i class="ti ti-filter text-sm"></i>
            <span>Filtrar</span>
        </button>
    </form>

    {{-- Tabela de Usuários --}}
    <div class="card overflow-x-auto bg-white shadow-sm">
        <table class="table-app">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>E-mail</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $usuario)
                    <tr>
                        <td>
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($usuario->name, 0, 1)) }}
                                </div>
                                <span class="font-bold text-ink-900">{{ $usuario->name }}</span>
                            </div>
                        </td>
                        <td class="text-ink-600 font-mono text-xs">{{ $usuario->email }}</td>
                        <td>
                            @if ($usuario->role === 'admin')
                                <span class="badge bg-purple-100 text-purple-800 border border-purple-200">Administrador</span>
                            @elseif ($usuario->role === 'prestador')
                                <span class="badge bg-brand-100 text-brand-800 border border-brand-200">Prestador</span>
                            @else
                                <span class="badge bg-ink-100 text-ink-700 border border-ink-200">Cliente</span>
                            @endif
                        </td>
                        <td><x-status-badge :status="$usuario->status" /></td>
                        <td class="text-right">
                            @if ($usuario->role !== 'admin')
                                <form method="POST" action="{{ route('admin.usuarios.status', $usuario) }}" class="inline"
                                      onsubmit="return confirm('Deseja realmente {{ $usuario->status === 'ativo' ? 'inativar' : 'ativar' }} este usuário?');">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn-sm {{ $usuario->status === 'ativo' ? 'btn-danger' : 'btn-success' }}">
                                        <i class="ti {{ $usuario->status === 'ativo' ? 'ti-user-x' : 'ti-user-check' }} text-xs"></i>
                                        <span>{{ $usuario->status === 'ativo' ? 'Inativar' : 'Ativar' }}</span>
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-ink-400 italic">Protegido</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-ink-500 py-10">
                            <i class="ti ti-user-off text-2xl text-ink-400 block mb-1"></i>
                            Nenhum usuário encontrado com os filtros selecionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginação --}}
    <div class="mt-8">
        {{ $usuarios->links() }}
    </div>
</x-app-layout>

