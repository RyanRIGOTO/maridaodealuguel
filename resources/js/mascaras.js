function somenteNumeros(value) {
    return value.replace(/\D/g, '');
}

export function formatarCpfCnpj(value) {
    const documento = value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 14);

    if (documento.length <= 11 && !/[A-Z]/.test(documento)) {
        return documento
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    return documento
        .replace(/^([A-Z0-9]{2})([A-Z0-9])/, '$1.$2')
        .replace(/^([A-Z0-9]{2})\.([A-Z0-9]{3})([A-Z0-9])/, '$1.$2.$3')
        .replace(/^([A-Z0-9]{2})\.([A-Z0-9]{3})\.([A-Z0-9]{3})([A-Z0-9])/, '$1.$2.$3/$4')
        .replace(/^([A-Z0-9]{2})\.([A-Z0-9]{3})\.([A-Z0-9]{3})\/([A-Z0-9]{4})([A-Z0-9]{1,2})$/, '$1.$2.$3/$4-$5');
}

function formatarTelefone(value) {
    const numeros = somenteNumeros(value).slice(0, 11);

    if (numeros.length <= 10) {
        return numeros
            .replace(/^(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d{1,4})$/, '$1-$2');
    }

    return numeros
        .replace(/^(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{5})(\d{1,4})$/, '$1-$2');
}

function aplicarMascara(input, formatador) {
    input.value = formatador(input.value);
    input.addEventListener('input', () => {
        input.value = formatador(input.value);
    });
}

export function iniciarMascaras() {
    document.querySelectorAll('input[name="cpf_cnpj"]').forEach((input) => {
        aplicarMascara(input, formatarCpfCnpj);
    });

    document.querySelectorAll('input[name="phone"]').forEach((input) => {
        aplicarMascara(input, formatarTelefone);
    });
}
