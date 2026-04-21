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
                ds: {
                    navy: 'var(--ds-navy)',
                    pink: 'var(--ds-pink)',
                }
            }
        },
    },
    plugins: [],
};
