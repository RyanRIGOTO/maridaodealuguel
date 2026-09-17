<x-report-layout titulo="USUÁRIOS CADASTRADOS" :periodo="$periodo">
    <table class="table-app">
        <thead>
            <tr>
                <th>Nome Completo</th>
                <th>Tipo de Usuário</th>
                <th>Data Cadastro</th>
                <th>Status da Conta</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($usuarios as $usuario)
                <tr>
                    <td class="font-bold text-ink-900">{{ $usuario->name }}</td>
                    <td>
                        @if ($usuario->role === 'admin')
                            <span class="badge bg-purple-100 text-purple-800">Admin</span>
                        @elseif ($usuario->role === 'prestador')
                            <span class="badge bg-brand-100 text-brand-800">Prestador</span>
                        @else
                            <span class="badge bg-ink-100 text-ink-700">Cliente</span>
                        @endif
                    </td>
                    <td class="text-ink-600 font-mono text-xs">{{ $usuario->created_at->format('d/m/Y') }}</td>
                    <td><x-status-badge :status="$usuario->status" /></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-ink-400 py-8">Nenhum usuário cadastrado no período selecionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-4 border-t border-ink-200 text-xs sm:text-sm font-bold bg-ink-50 p-4 rounded-lg">
        <p class="text-ink-800 uppercase tracking-wider">Total: <span class="text-ink-900 font-extrabold">{{ $usuarios->count() }}</span></p>
        <p class="text-ink-800 uppercase tracking-wider">Clientes: <span class="text-brand-600 font-extrabold">{{ $totalClientes }}</span></p>
        <p class="text-ink-800 uppercase tracking-wider">Prestadores: <span class="text-brand-600 font-extrabold">{{ $totalPrestadores }}</span></p>
        <p class="text-ink-800 uppercase tracking-wider">Administradores: <span class="text-purple-700 font-extrabold">{{ $totalAdmins }}</span></p>
    </div>
</x-report-layout>

