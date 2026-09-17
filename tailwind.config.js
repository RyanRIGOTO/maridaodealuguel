import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Paleta Laranja (Identidade Visual Maridão de Aluguel)
                brand: {
                    50: '#FFF4EC',
                    100: '#FFE3CC',
                    200: '#FFCEAA',
                    300: '#FF9F5E',
                    400: '#F97E33',
                    500: '#F2600C', // Cor Principal da Marca
                    600: '#D2500A',
                    700: '#A63F08',
                    800: '#7D2F07',
                    900: '#4A1C04',
                },
                // Paleta Neutra Quente (Ink)
                ink: {
                    50: '#FAF9F7',  // Fundo geral do sistema
                    100: '#F0EEEA', // Fundos secundários, bordas suaves e headers de tabela
                    200: '#E4E0D8',
                    300: '#C7C1B7', // Bordas e divisores padrão
                    400: '#A39C90',
                    500: '#7D7569',
                    600: '#5C554C', // Textos secundários e legendas
                    700: '#453F38',
                    800: '#332E27', // Títulos e botões secundários
                    900: '#231F1A', // Textos principais e títulos de alto contraste
                },
            },
        },
    },
    plugins: [],
};

