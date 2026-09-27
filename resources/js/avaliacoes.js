export function iniciarAvaliacoes() {
    document.querySelectorAll('[data-avaliacao]').forEach((avaliacao) => {
        const opcoes = avaliacao.querySelectorAll('input[type="radio"]');

        function pintarEstrelas(nota) {
            avaliacao.querySelectorAll('[data-estrela]').forEach((estrela) => {
                const selecionada = Number(estrela.dataset.estrela) <= nota;
                estrela.classList.toggle('text-amber-400', selecionada);
                estrela.classList.toggle('text-ink-200', !selecionada);
            });
        }

        function mostrarNotaSelecionada() {
            pintarEstrelas(Number(avaliacao.querySelector('input:checked')?.value || 0));
        }

        opcoes.forEach((opcao) => {
            opcao.addEventListener('change', mostrarNotaSelecionada);
            opcao.addEventListener('focus', () => pintarEstrelas(Number(opcao.value)));
            opcao.addEventListener('blur', mostrarNotaSelecionada);
            opcao.closest('label').addEventListener('mouseenter', () => pintarEstrelas(Number(opcao.value)));
        });
        avaliacao.addEventListener('mouseleave', mostrarNotaSelecionada);
        mostrarNotaSelecionada();
    });
}
