import { normalizarTexto } from './texto';

export const LIMITE_SUGESTOES = 5;

// Opções cujo texto contém o termo, ignorando acentos e maiúsculas.
export function filtrarOpcoes(textos, termo) {
    const busca = normalizarTexto(termo);
    return textos.filter((texto) => normalizarTexto(texto).includes(busca));
}

// Campo único de pesquisa + escolha (ex.: categoria do serviço). O texto é só para
// pesquisar; o que vai para o servidor é o id no campo oculto.
export function iniciarCombobox() {
    document.querySelectorAll('[data-combobox]').forEach((caixa) => {
        const texto = caixa.querySelector('[data-combobox-texto]');
        const valor = caixa.querySelector('[data-combobox-valor]');
        const lista = caixa.querySelector('[data-combobox-lista]');
        const vazio = caixa.querySelector('[data-combobox-vazio]');
        const info = caixa.querySelector('[data-combobox-info]');
        const opcoes = [...lista.querySelectorAll('[role="option"]')];
        let visiveis = [];
        let ativa = -1;

        function marcarAtiva(indice) {
            ativa = indice;
            opcoes.forEach((opcao) => opcao.setAttribute('aria-selected', 'false'));
            const opcao = visiveis[ativa];
            if (opcao) {
                opcao.setAttribute('aria-selected', 'true');
                opcao.scrollIntoView({ block: 'nearest' });
                texto.setAttribute('aria-activedescendant', opcao.id);
            } else {
                texto.removeAttribute('aria-activedescendant');
            }
        }

        function abrir() {
            // Com uma categoria já escolhida, abrir mostra a lista inteira (até o limite).
            const termo = valor.value ? '' : texto.value;
            const encontrados = filtrarOpcoes(opcoes.map((opcao) => opcao.textContent), termo);
            const encontradas = opcoes.filter((opcao) => encontrados.includes(opcao.textContent));
            visiveis = encontradas.slice(0, LIMITE_SUGESTOES);
            opcoes.forEach((opcao) => { opcao.hidden = !visiveis.includes(opcao); });
            vazio.hidden = encontradas.length > 0;
            info.hidden = encontradas.length <= LIMITE_SUGESTOES;
            info.textContent = `Mostrando ${LIMITE_SUGESTOES} de ${encontradas.length}. Continue digitando para ver as outras.`;
            lista.hidden = false;
            texto.setAttribute('aria-expanded', 'true');
            marcarAtiva(visiveis.length ? 0 : -1);
        }

        function fechar() {
            lista.hidden = true;
            texto.setAttribute('aria-expanded', 'false');
            marcarAtiva(-1);
        }

        function escolher(opcao) {
            texto.value = opcao.textContent;
            valor.value = opcao.dataset.valor;
            texto.setCustomValidity('');
            fechar();
        }

        texto.addEventListener('input', () => {
            // Ao digitar, a escolha anterior deixa de valer até escolher de novo.
            valor.value = '';
            abrir();
        });
        texto.addEventListener('focus', abrir);
        texto.addEventListener('blur', () => {
            // Se o texto digitado é exatamente o nome de uma categoria, considera escolhida.
            const exata = opcoes.find((opcao) => normalizarTexto(opcao.textContent) === normalizarTexto(texto.value));
            if (!valor.value && exata) escolher(exata);
            fechar();
        });
        texto.addEventListener('keydown', (evento) => {
            if (evento.key === 'ArrowDown' || evento.key === 'ArrowUp') {
                evento.preventDefault();
                if (lista.hidden) abrir();
                const passo = evento.key === 'ArrowDown' ? 1 : -1;
                marcarAtiva((ativa + passo + visiveis.length) % Math.max(visiveis.length, 1));
            } else if (evento.key === 'Enter') {
                if (!lista.hidden && visiveis[ativa]) {
                    evento.preventDefault();
                    escolher(visiveis[ativa]);
                }
            } else if (evento.key === 'Escape') {
                fechar();
            }
        });
        // mousedown (e não click) para escolher antes de o campo perder o foco.
        opcoes.forEach((opcao) => opcao.addEventListener('mousedown', (evento) => {
            evento.preventDefault();
            escolher(opcao);
        }));

        texto.form?.addEventListener('submit', (evento) => {
            if (!valor.value) {
                evento.preventDefault();
                texto.setCustomValidity('Escolha uma categoria da lista.');
                texto.reportValidity();
            }
        });
    });
}
