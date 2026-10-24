import { normalizarTexto } from './texto';

export function iniciarAgendamento() {
    const formulario = document.querySelector('[data-agendamento]');
    if (!formulario) return;

    const campoServico = formulario.elements.servico_id;
    const campoData = formulario.elements.data;
    const campoHora = formulario.elements.hora;
    const campoOpcaoEndereco = formulario.elements.endereco_opcao;
    const blocoEnderecoNovo = formulario.querySelector('[data-endereco-novo]');
    const textoEnderecoCadastro = formulario.querySelector('[data-endereco-cadastro]');
    const camposObrigatoriosEndereco = ['logradouro', 'numero', 'bairro', 'cidade', 'uf'];
    const categorias = formulario.querySelectorAll('[data-categoria]');
    const servicos = formulario.querySelectorAll('[data-servico]');
    const horarios = formulario.querySelectorAll('[data-horario]');
    let etapaAtual = 1;
    let servicoSelecionado = null;

    function dadosPreenchidos() {
        return campoData.value && campoData.validity.valid && campoHora.value && enderecoPreenchido();
    }

    const valorCampo = (nome) => formulario.elements[nome].value.trim();

    function enderecoPreenchido() {
        return campoOpcaoEndereco.value === 'cadastro' || camposObrigatoriosEndereco.every((nome) => valorCampo(nome));
    }

    // Texto do endereço para o resumo da etapa 4.
    function textoEndereco() {
        if (campoOpcaoEndereco.value === 'cadastro') return textoEnderecoCadastro.textContent.trim();
        return [
            `${valorCampo('logradouro')}, ${valorCampo('numero')}`,
            valorCampo('complemento'),
            valorCampo('bairro'),
            `${valorCampo('cidade')}/${valorCampo('uf').toUpperCase()}`,
        ].filter(Boolean).join(' - ');
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
            preco: Number(servico.preco) > 0
                ? Number(servico.preco).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
                : 'Grátis',
            area: servico.area,
            descricao: servico.descricao,
            dataHora: `${campoData.value.split('-').reverse().join('/')} às ${campoHora.value}`,
            endereco: textoEndereco(),
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

    // Pesquisa de categorias: filtra os botões enquanto o cliente digita (ignora acentos e maiúsculas).
    const buscaCategoria = formulario.querySelector('[data-busca-categoria]');
    const semCategoria = formulario.querySelector('[data-categoria-nao-encontrada]');

    function filtrarCategorias() {
        const termo = normalizarTexto(buscaCategoria.value);
        let visiveis = 0;
        categorias.forEach((botao) => {
            const aparece = normalizarTexto(botao.dataset.nomeCategoria).includes(termo);
            botao.hidden = !aparece;
            if (aparece) visiveis += 1;
        });
        semCategoria.hidden = visiveis > 0;
    }

    buscaCategoria?.addEventListener('input', filtrarCategorias);
    // Enter na pesquisa não deve tentar enviar o formulário.
    buscaCategoria?.addEventListener('keydown', (evento) => {
        if (evento.key === 'Enter') evento.preventDefault();
    });

    // Pesquisa de serviços dentro da categoria; mostra no máximo LIMITE_SERVICOS para caber na tela.
    const LIMITE_SERVICOS = 5;

    function filtrarServicos(lista) {
        const busca = lista.querySelector('[data-busca-servico]');
        if (!busca) return;

        const termo = normalizarTexto(busca.value);
        const itens = [...lista.querySelectorAll('[data-servico]')];
        const encontrados = itens.filter((botao) => normalizarTexto(`${botao.dataset.nome} ${botao.dataset.profissional}`).includes(termo));
        const visiveis = encontrados.slice(0, LIMITE_SERVICOS);
        itens.forEach((botao) => { botao.hidden = !visiveis.includes(botao); });

        const info = lista.querySelector('[data-servicos-info]');
        if (encontrados.length === 0) info.textContent = 'Nenhum serviço encontrado.';
        else if (encontrados.length > LIMITE_SERVICOS) info.textContent = `Mostrando ${LIMITE_SERVICOS} de ${encontrados.length} serviços. Pesquise pelo nome para encontrar os outros.`;
        info.hidden = encontrados.length > 0 && encontrados.length <= LIMITE_SERVICOS;
    }

    formulario.querySelectorAll('[data-lista-servicos]').forEach((lista) => {
        const busca = lista.querySelector('[data-busca-servico]');
        busca?.addEventListener('input', () => filtrarServicos(lista));
        busca?.addEventListener('keydown', (evento) => {
            if (evento.key === 'Enter') evento.preventDefault();
        });
        filtrarServicos(lista);
    });

    categorias.forEach((botao) => botao.addEventListener('click', () => selecionarCategoria(botao.dataset.categoria)));
    servicos.forEach((botao) => botao.addEventListener('click', () => selecionarServico(botao)));
    horarios.forEach((botao) => {
        botao.addEventListener('click', () => {
            campoHora.value = botao.dataset.horario;
            horarios.forEach((item) => item.setAttribute('aria-pressed', String(item === botao)));
            atualizarBotoes();
        });
    });
    // Horários de hoje que já passaram ficam desativados (o servidor também recusa).
    const semHorario = formulario.querySelector('[data-sem-horario]');

    function atualizarHorarios() {
        const agora = new Date();
        const doisDigitos = (n) => String(n).padStart(2, '0');
        const hoje = `${agora.getFullYear()}-${doisDigitos(agora.getMonth() + 1)}-${doisDigitos(agora.getDate())}`;
        const horaAtual = `${doisDigitos(agora.getHours())}:${doisDigitos(agora.getMinutes())}`;
        let livres = 0;

        horarios.forEach((botao) => {
            const passou = campoData.value === hoje && botao.dataset.horario <= horaAtual;
            botao.disabled = passou;
            if (passou && campoHora.value === botao.dataset.horario) {
                campoHora.value = '';
                botao.setAttribute('aria-pressed', 'false');
            }
            if (!passou) livres += 1;
        });
        semHorario.hidden = !campoData.value || livres > 0;
    }

    campoData.addEventListener('input', () => {
        atualizarHorarios();
        atualizarBotoes();
    });
    blocoEnderecoNovo.addEventListener('input', atualizarBotoes);

    // "No meu endereço" usa o endereço do cadastro; "Em outro endereço" mostra os campos para digitar.
    const opcoesEndereco = formulario.querySelectorAll('[data-endereco-opcao]');

    function escolherEndereco(opcao, moverFoco = true) {
        campoOpcaoEndereco.value = opcao;
        opcoesEndereco.forEach((botao) => botao.setAttribute('aria-pressed', String(botao.dataset.enderecoOpcao === opcao)));
        blocoEnderecoNovo.hidden = opcao !== 'outro';
        if (textoEnderecoCadastro) textoEnderecoCadastro.hidden = opcao !== 'cadastro';
        if (opcao === 'outro' && moverFoco) formulario.elements.cep.focus();
        atualizarBotoes();
    }

    opcoesEndereco.forEach((botao) => botao.addEventListener('click', () => escolherEndereco(botao.dataset.enderecoOpcao)));
    escolherEndereco(campoOpcaoEndereco.value, false);
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
        const lista = servicoAnterior.closest('[data-lista-servicos]');
        selecionarCategoria(lista.dataset.listaServicos);
        selecionarServico(servicoAnterior);
        // Garante que o serviço escolhido continue visível mesmo se estiver além dos 5 primeiros.
        const busca = lista.querySelector('[data-busca-servico]');
        if (busca) {
            busca.value = servicoAnterior.dataset.nome;
            filtrarServicos(lista);
        }
    } else {
        campoServico.value = '';
    }
    const horarioAnterior = [...horarios].find((botao) => botao.dataset.horario === campoHora.value);
    if (horarioAnterior) horarioAnterior.setAttribute('aria-pressed', 'true');
    else campoHora.value = '';
    atualizarHorarios();
    atualizarBotoes();
    mostrarEtapa(1, false);
}
