export function iniciarInterface() {
    // Botões que abrem ou fecham um elemento pelo seu id.
    function mostrarPainel(id, aberto) {
        const painel = document.getElementById(id);
        if (!painel) return;

        painel.hidden = !aberto;
        document.querySelectorAll('[data-alternar]').forEach((botao) => {
            if (botao.dataset.alternar !== id) return;

            botao.setAttribute('aria-expanded', String(aberto));
            botao.querySelectorAll('[data-quando-aberto]').forEach((item) => item.hidden = !aberto);
            botao.querySelectorAll('[data-quando-fechado]').forEach((item) => item.hidden = aberto);
        });
    }

    document.querySelectorAll('[data-alternar]').forEach((botao) => {
        botao.addEventListener('click', () => {
            const painel = document.getElementById(botao.dataset.alternar);
            if (painel) mostrarPainel(painel.id, painel.hidden);
        });
    });

    document.querySelectorAll('[data-fechar]').forEach((botao) => {
        botao.addEventListener('click', () => {
            mostrarPainel(botao.dataset.fechar, false);
            document.querySelector(`[data-alternar="${botao.dataset.fechar}"]`)?.focus();
        });
    });

    // Cada cartão possui sua própria visualização e seu próprio formulário.
    document.querySelectorAll('[data-edicao]').forEach((cartao) => {
        const visualizacao = cartao.querySelector('[data-visualizacao]');
        const formulario = cartao.querySelector('[data-formulario-edicao]');
        const editar = cartao.querySelector('[data-editar]');

        editar.addEventListener('click', () => {
            visualizacao.hidden = true;
            formulario.hidden = false;
            formulario.querySelector('input:not([type="hidden"])')?.focus();
        });

        cartao.querySelector('[data-cancelar-edicao]').addEventListener('click', () => {
            formulario.hidden = true;
            visualizacao.hidden = false;
            editar.focus();
        });
    });

    const menu = document.getElementById('menu-lateral');
    const botaoMenu = document.querySelector('[data-menu-lateral]');
    const fundoMenu = document.querySelector('[data-fundo-menu]');

    if (menu && botaoMenu && fundoMenu) {
        const telaGrande = window.matchMedia('(min-width: 1024px)');

        function mostrarMenu(aberto) {
            menu.classList.toggle('-translate-x-full', !aberto);
            menu.inert = !aberto && !telaGrande.matches;
            fundoMenu.hidden = !aberto;
            botaoMenu.setAttribute('aria-expanded', String(aberto));
        }

        botaoMenu.addEventListener('click', () => mostrarMenu(fundoMenu.hidden));
        fundoMenu.addEventListener('click', () => mostrarMenu(false));
        document.addEventListener('keydown', (evento) => {
            if (evento.key === 'Escape' && !fundoMenu.hidden) {
                mostrarMenu(false);
                botaoMenu.focus();
            }
        });
        telaGrande.addEventListener('change', () => mostrarMenu(false));
        mostrarMenu(false);
    }

    const mensagens = document.getElementById('thread-mensagens');
    if (mensagens) mensagens.scrollTop = mensagens.scrollHeight;
}
