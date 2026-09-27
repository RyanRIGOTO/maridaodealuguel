export function iniciarAgendamento() {
    const formulario = document.querySelector('[data-agendamento]');
    if (!formulario) return;

    const campoServico = formulario.elements.servico_id;
    const campoData = formulario.elements.data;
    const campoHora = formulario.elements.hora;
    const campoEndereco = formulario.elements.endereco_servico;
    const categorias = formulario.querySelectorAll('[data-categoria]');
    const servicos = formulario.querySelectorAll('[data-servico]');
    const horarios = formulario.querySelectorAll('[data-horario]');
    let etapaAtual = 1;
    let servicoSelecionado = null;

    function dadosPreenchidos() {
        return campoData.value && campoData.validity.valid && campoHora.value && campoEndereco.value.trim();
    }

    function atualizarBotoes() {
        formulario.querySelector('[data-ir-etapa="2"]').disabled = !servicoSelecionado;
        formulario.querySelector('[data-ir-etapa="4"]').disabled = !dadosPreenchidos();
    }

    function atualizarResumo() {
        if (!servicoSelecionado) return;

        const servico = servicoSelecionado.dataset;
        const resumo = {
            nome: servico.nome,
            profissional: servico.profissional,
            inicial: servico.profissional.charAt(0).toUpperCase(),
            reputacao: Number(servico.reputacao).toFixed(1),
            preco: Number(servico.preco).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }),
            area: servico.area,
            descricao: servico.descricao,
            dataHora: `${campoData.value.split('-').reverse().join('/')} às ${campoHora.value}`,
            endereco: campoEndereco.value,
        };

        // textContent mostra os dados como texto, sem interpretá-los como HTML.
        formulario.querySelectorAll('[data-resumo]').forEach((elemento) => {
            elemento.textContent = resumo[elemento.dataset.resumo];
        });
        formulario.querySelector('[data-regiao]').hidden = !servico.area;
        formulario.querySelector('[data-resumo="descricao"]').hidden = !servico.descricao;
    }

    function mostrarEtapa(numero, moverFoco = true) {
        if (numero > 1 && !servicoSelecionado) return;
        if (numero === 4 && !dadosPreenchidos()) return;

        etapaAtual = numero;
        formulario.querySelectorAll('[data-etapa]').forEach((etapa) => {
            etapa.hidden = Number(etapa.dataset.etapa) !== numero;
        });
        formulario.querySelectorAll('[data-indicador-etapa]').forEach((indicador) => {
            const etapa = Number(indicador.dataset.indicadorEtapa);
            indicador.classList.toggle('etapa-atingida', etapa <= numero);
            if (etapa === numero) indicador.setAttribute('aria-current', 'step');
            else indicador.removeAttribute('aria-current');
        });
        atualizarResumo();
        if (moverFoco) formulario.querySelector(`[data-etapa="${numero}"] h2`).focus();
    }

    function selecionarCategoria(id) {
        servicoSelecionado = null;
        campoServico.value = '';
        categorias.forEach((botao) => botao.setAttribute('aria-pressed', String(botao.dataset.categoria === id)));
        servicos.forEach((botao) => botao.setAttribute('aria-pressed', 'false'));
        formulario.querySelectorAll('[data-lista-servicos]').forEach((lista) => {
            lista.hidden = lista.dataset.listaServicos !== id;
        });
        atualizarBotoes();
    }

    function selecionarServico(botao) {
        servicoSelecionado = botao;
        campoServico.value = botao.dataset.servico;
        servicos.forEach((item) => item.setAttribute('aria-pressed', String(item === botao)));
        atualizarBotoes();
    }

    categorias.forEach((botao) => botao.addEventListener('click', () => selecionarCategoria(botao.dataset.categoria)));
    servicos.forEach((botao) => botao.addEventListener('click', () => selecionarServico(botao)));
    horarios.forEach((botao) => {
        botao.addEventListener('click', () => {
            campoHora.value = botao.dataset.horario;
            horarios.forEach((item) => item.setAttribute('aria-pressed', String(item === botao)));
            atualizarBotoes();
        });
    });
    campoData.addEventListener('input', atualizarBotoes);
    campoEndereco.addEventListener('input', atualizarBotoes);
    formulario.querySelectorAll('[data-ir-etapa]').forEach((botao) => {
        botao.addEventListener('click', () => mostrarEtapa(Number(botao.dataset.irEtapa)));
    });
    formulario.addEventListener('submit', (evento) => {
        // Enter nas etapas iniciais não pode enviar um agendamento incompleto.
        if (etapaAtual !== 4 || !servicoSelecionado || !dadosPreenchidos()) {
            evento.preventDefault();
        }
    });

    // Recupera a seleção quando o Laravel devolve o formulário com um erro.
    const servicoAnterior = [...servicos].find((botao) => botao.dataset.servico === campoServico.value);
    if (servicoAnterior) {
        selecionarCategoria(servicoAnterior.closest('[data-lista-servicos]').dataset.listaServicos);
        selecionarServico(servicoAnterior);
    } else {
        campoServico.value = '';
    }
    const horarioAnterior = [...horarios].find((botao) => botao.dataset.horario === campoHora.value);
    if (horarioAnterior) horarioAnterior.setAttribute('aria-pressed', 'true');
    else campoHora.value = '';
    atualizarBotoes();
    mostrarEtapa(1, false);
}
