// Deixa o texto comparável em buscas: sem acentos, minúsculo e sem espaços nas pontas.
export function normalizarTexto(valor) {
    
    return (valor || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
}
