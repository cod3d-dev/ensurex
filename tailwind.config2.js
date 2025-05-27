import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
import plugin from 'tailwindcss/plugin';

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    safelist: [
        // Safelist all text color utilities with common shades
        {
            pattern: /^text-(amber|red|green|blue|yellow|zinc|slate|gray|white|black)(-\d+)?$/,
        },
        // Safelist all custom color utilities
        {
            pattern: /^text-(primary|danger|success|warning|info|violet|pending)(-\d+)?$/,
        }
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: colors.amber,
                danger: colors.red,
                success: colors.green,
                warning: colors.amber,
                info: colors.blue,
                violet: colors.violet,
                pending: colors.yellow,
                gray: colors.zinc,
            },
        },
    },
    plugins: [
    ],
};
