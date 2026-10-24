import { normalizarTexto } from './texto';

const INTERVALO_ATUALIZACAO = 5000; // ms

export function iniciarChat() {
    const lista = document.getElementById('lista-conversas');
    if (!lista) return;

    const busca = document.querySelector('[data-busca-conversas]');

    // Busca na lista de conversas; relê os itens a cada vez porque a lista é atualizada sozinha.
    function filtrarConversas() {
        const termo = normalizarTexto(busca?.value);
        const conversas = lista.querySelectorAll('[data-conversa]');
        let visiveis = 0;
        conversas.forEach((conversa) => {
            const aparece = normalizarTexto(conversa.dataset.conversa).includes(termo);
            conversa.hidden = !aparece;
            if (aparece) visiveis += 1;
        });
        const semResultado = lista.querySelector('[data-conversa-nao-encontrada]');
        if (semResultado) semResultado.hidden = visiveis > 0 || conversas.length === 0;
    }

    busca?.addEventListener('input', filtrarConversas);

    const thread = document.getElementById('thread-mensagens');
    if (thread) thread.scrollTop = thread.scrollHeight;

    // Troca o conteúdo só se mudou, para não mexer na rolagem à toa.
    function substituir(atual, novo) {
        if (!atual || !novo || atual.innerHTML === novo.innerHTML) return false;
        atual.replaceChildren(...novo.childNodes);
        return true;
    }

    // Busca a mesma página no servidor e atualiza só a lista e as mensagens;
    // o campo de digitação e o resto da tela ficam intactos.
    async function atualizar() {
        if (document.visibilityState !== 'visible') return;

        try {
            const resposta = await fetch(window.location.href, { headers: { Accept: 'text/html' } });
            if (!resposta.ok || resposta.redirected) return; // ex.: sessão expirou

            const pagina = new DOMParser().parseFromString(await resposta.text(), 'text/html');

            if (substituir(lista, pagina.getElementById('lista-conversas'))) filtrarConversas();
            // O horário no cabeçalho muda quando uma proposta de novo horário é aceita.
            substituir(document.getElementById('chat-cabecalho'), pagina.getElementById('chat-cabecalho'));

            if (thread) {
                const estavaNoFim = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 40;
                if (substituir(thread, pagina.getElementById('thread-mensagens')) && estavaNoFim) {
                    thread.scrollTop = thread.scrollHeight;
                }
            }
        } catch {
            // Sem conexão: tenta de novo no próximo ciclo.
        }
    }

    setInterval(atualizar, INTERVALO_ATUALIZACAO);
    document.addEventListener('visibilitychange', atualizar);
}
