export function iniciarEnderecos() {
    document.querySelectorAll('[data-endereco]').forEach((formulario) => {
        const campoCep = formulario.querySelector('[name="cep"]');
        const carregando = formulario.querySelector('[data-carregando-cep]');
        const erro = formulario.querySelector('[data-erro-cep]');
        let consultaAtual = null;

        function mostrarErro(mensagem) {
            erro.textContent = mensagem;
            erro.hidden = !mensagem;
        }

        function formatarCep() {
            const numeros = campoCep.value.replace(/\D/g, '').slice(0, 8);
            campoCep.value = numeros.replace(/(\d{5})(\d)/, '$1-$2');
        }

        campoCep.addEventListener('input', () => {
            // Uma resposta antiga não deve preencher o endereço de um novo CEP.
            consultaAtual?.abort();
            carregando.hidden = true;
            mostrarErro('');
            formatarCep();
        });

        campoCep.addEventListener('blur', async () => {
            consultaAtual?.abort();
            const cep = campoCep.value.replace(/\D/g, '');
            mostrarErro('');
            if (!cep) return;
            if (cep.length !== 8) {
                mostrarErro('Informe os 8 dígitos do CEP.');
                return;
            }

            const consulta = new AbortController();
            consultaAtual = consulta;
            carregando.hidden = false;

            try {
                const resposta = await fetch(`https://viacep.com.br/ws/${cep}/json/`, {
                    signal: consulta.signal,
                });
                if (!resposta.ok) throw new Error('Falha na consulta');
                const endereco = await resposta.json();
                if (consulta.signal.aborted) return;
                if (endereco.erro) {
                    mostrarErro('CEP não encontrado. Você pode preencher o endereço manualmente.');
                    return;
                }

                formulario.querySelector('[name="logradouro"]').value = endereco.logradouro || '';
                formulario.querySelector('[name="bairro"]').value = endereco.bairro || '';
                formulario.querySelector('[name="cidade"]').value = endereco.localidade || '';
                formulario.querySelector('[name="uf"]').value = endereco.uf || '';
                formulario.querySelector('[name="numero"]').focus();
            } catch (error) {
                if (error.name !== 'AbortError') {
                    mostrarErro('Não foi possível consultar o CEP. Preencha o endereço manualmente ou tente novamente.');
                }
            } finally {
                if (consultaAtual === consulta) carregando.hidden = true;
            }
        });

        formatarCep();
    });
}
