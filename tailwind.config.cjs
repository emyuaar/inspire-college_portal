/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                ds: {
                    navy: 'var(--ds-navy)',
                    pink: 'var(--ds-pink)',
                }
            }
        },
    },
    plugins: [],
};
