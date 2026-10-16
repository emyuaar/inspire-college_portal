/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "resources/**/*.blade.php",
        "resources/**/*.js",
        "resources/**/*.vue",
        // UI class maps live here; keep them included in Tailwind's scan to avoid purging.
        "app/Services/Ui/**/*.php",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"ICOL Text"', '"Atkinson Hyperlegible Next"', '"Segoe UI"', 'sans-serif'],
                display: ['"Schibsted Grotesk"', '"Arial Narrow"', 'sans-serif'],
            },
            colors: {
                // Built-in blues used across the portal -> ICOL violet scale.
                blue: { 50: '#F6F0FD', 100: '#ECDFFA', 200: '#D9C2F4', 300: '#C29DED', 400: '#A970E8', 500: '#9136E4', 600: '#8624E1', 700: '#6B17B8', 800: '#4E1590', 900: '#3B1D66', 950: '#29104A' },
                indigo: { 50: '#F6F0FD', 100: '#ECDFFA', 200: '#D9C2F4', 300: '#C29DED', 400: '#A970E8', 500: '#9136E4', 600: '#8624E1', 700: '#6B17B8', 800: '#4E1590', 900: '#3B1D66', 950: '#29104A' },
                sky: { 50: '#F6F0FD', 100: '#ECDFFA', 200: '#D9C2F4', 300: '#C29DED', 400: '#A970E8', 500: '#9136E4', 600: '#8624E1', 700: '#6B17B8', 800: '#4E1590', 900: '#3B1D66', 950: '#29104A' },
                // ICOL brand (2026 logo): --ds-navy = ink #29104A, --ds-pink = violet #8624E1, ds-orange = action orange.
                ds: {
                    navy: 'var(--ds-navy)',
                    pink: 'var(--ds-pink)',
                    orange: '#FF8C00',
                },
                // "pink-*" shades were companions of the old brand colour; mapped to the ICOL violet scale.
                pink: {
                    50: '#F6F0FD', 100: '#ECDFFA', 200: '#D9C2F4', 300: '#C29DED', 400: '#A970E8',
                    500: '#9136E4', 600: '#8624E1', 700: '#6B17B8', 800: '#4E1590', 900: '#3B1D66', 950: '#29104A',
                },
            }
        },
    },
    plugins: [
        require('@tailwindcss/typography'),
    ],
};
