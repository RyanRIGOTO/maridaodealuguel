import './bootstrap';

function onlyDigits(value) {
    return value.replace(/\D/g, '');
}

function formatCpfCnpj(value) {
    const digits = onlyDigits(value).slice(0, 14);

    if (digits.length <= 11) {
        return digits
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    return digits
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/^(\d{2})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3/$4')
        .replace(/^(\d{2})\.(\d{3})\.(\d{3})\/(\d{4})(\d{1,2})$/, '$1.$2.$3/$4-$5');
}

function formatPhone(value) {
    const digits = onlyDigits(value).slice(0, 11);

    if (digits.length <= 10) {
        return digits
            .replace(/^(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d{1,4})$/, '$1-$2');
    }

    return digits
        .replace(/^(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{5})(\d{1,4})$/, '$1-$2');
}

function applyInputMask(input, formatter) {
    input.value = formatter(input.value);
    input.addEventListener('input', () => {
        input.value = formatter(input.value);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[name="cpf_cnpj"]').forEach((input) => {
        applyInputMask(input, formatCpfCnpj);
    });

    document.querySelectorAll('input[name="phone"]').forEach((input) => {
        applyInputMask(input, formatPhone);
    });
});

