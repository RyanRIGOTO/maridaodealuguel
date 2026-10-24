// Traduz os avisos da validação nativa do navegador (required, type="email", minlength...),
// que aparecem no idioma do navegador, e não no do sistema.
function mensagem(campo) {
    const v = campo.validity;
    if (v.valueMissing) {
        if (campo.type === 'checkbox') return 'Marque esta opção para continuar.';
        if (campo.tagName === 'SELECT' || campo.type === 'radio') return 'Selecione uma opção.';
        return 'Preencha este campo.';
    }
    if (v.typeMismatch && campo.type === 'email') return 'Informe um e-mail válido (ex.: nome@exemplo.com).';
    if (v.typeMismatch) return 'Valor em formato inválido.';
    if (v.tooShort) return `Use pelo menos ${campo.minLength} caracteres (você digitou ${campo.value.length}).`;
    if (v.tooLong) return `Use no máximo ${campo.maxLength} caracteres.`;
    if (v.rangeUnderflow) return `O valor mínimo é ${campo.min}.`;
    if (v.rangeOverflow) return `O valor máximo é ${campo.max}.`;
    if (v.stepMismatch) return 'Use no máximo duas casas decimais.';
    if (v.badInput) return 'Valor inválido.';
    if (v.patternMismatch) return 'Valor em formato inválido.';
    return '';
}

export function iniciarValidacao() {
    // 'invalid' não sobe na árvore; a captura pega o evento de todos os campos da página.
    document.addEventListener('invalid', (evento) => {
        const campo = evento.target;
        // Mensagem já definida por outro componente (ex.: combobox) ou por esta função: mantém.
        if (campo.validity.customError) return;
        campo.setCustomValidity('');
        campo.setCustomValidity(mensagem(campo));
    }, true);

    // Limpa o aviso ao corrigir, senão o campo continuaria marcado como inválido.
    ['input', 'change'].forEach((tipo) => document.addEventListener(tipo, (evento) => {
        if (evento.target.setCustomValidity) evento.target.setCustomValidity('');
    }, true));
}
