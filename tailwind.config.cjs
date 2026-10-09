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
            colors: {
                // Inspire College of Learning palette: --ds-navy / --ds-pink now hold the ICOL navy / blue.
                ds: {
                    navy: 'var(--ds-navy)',
                    pink: 'var(--ds-pink)',
                    orange: '#F7931E',
                },
                // "pink-*" shades were companions of the old brand colour; map them to the ICOL blue scale.
                pink: {
                    50: '#EEF6FC', 100: '#D6E9F7', 200: '#AED3EF', 300: '#7DB7E3', 400: '#4A97D2',
                    500: '#2F82C0', 600: '#2774AE', 700: '#1F5F8F', 800: '#1A4D73', 900: '#163F5E', 950: '#0E2C4A',
                },
            }
        },
    },
    plugins: [
        require('@tailwindcss/typography'),
    ],
};
