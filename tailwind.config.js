import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                // Tipografía de titulares de la web pública.
                display: ['Outfit', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Marca del grupo: rojo premium/carmesí (basado en rose) para mayor elegancia.
                brand: {
                    50: '#fff1f2',
                    100: '#ffe4e6',
                    200: '#fecdd3',
                    300: '#fda4af',
                    400: '#fb7185',
                    500: '#f43f5e',
                    600: '#e11d48', // acción principal
                    700: '#be123c', // hover / marca
                    800: '#9f1239',
                    900: '#881337',
                    950: '#4c0519',
                },
                // Neutro cálido para superficies y texto (slate puro).
                ink: {
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#64748b',
                    600: '#475569',
                    700: '#334155',
                    800: '#1e293b',
                    900: '#0f172a',
                    950: '#020617',
                },
                // Superficies cálidas de la web pública (papel / arena).
                sand: {
                    50: '#fdfbf8',
                    100: '#faf6f0',
                    200: '#f2ece3',
                    300: '#e6ddd0',
                    400: '#cfc2ae',
                },
                // Verde bosque de apoyo (escultismo/naturaleza).
                forest: {
                    50: '#f1f7f2',
                    100: '#dcebde',
                    600: '#2f6b45',
                    700: '#245537',
                    800: '#1c422b',
                    900: '#14301f',
                },
                // Colores oficiales por rama del grupo scout
                rama: {
                    castor: '#f97316',      // Naranja
                    lobato: '#eab308',      // Amarillo
                    ranger: '#1e3a8a',      // Azul oscuro
                    pionero: '#e11d48',     // Rojo (alineado con brand)
                    ruta: '#16a34a',        // Verde
                    responsable: '#0284c7', // Celeste
                },
            },
            // Curva de aceleración compartida por la web pública (entrada suave, frenada firme).
            transitionTimingFunction: {
                suave: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
            keyframes: {
                // Entrada estándar: sube y aparece.
                entrada: {
                    '0%': { opacity: '0', transform: 'translate3d(0, 14px, 0)' },
                    '100%': { opacity: '1', transform: 'none' },
                },
                // Entrada del hero: un poco más de recorrido.
                heroEntrada: {
                    '0%': { opacity: '0', transform: 'translate3d(0, 28px, 0)' },
                    '100%': { opacity: '1', transform: 'none' },
                },
                // Acercamiento lentísimo de la foto de portada (efecto Ken Burns).
                acercar: {
                    '0%': { transform: 'scale(1)' },
                    '100%': { transform: 'scale(1.07)' },
                },
                // Indicador de scroll del hero.
                rebote: {
                    '0%, 100%': { transform: 'translateY(0)', opacity: '0.75' },
                    '50%': { transform: 'translateY(7px)', opacity: '1' },
                },
            },
            animation: {
                entrada: 'entrada 0.45s cubic-bezier(0.22, 1, 0.36, 1) both',
                hero: 'heroEntrada 0.85s cubic-bezier(0.22, 1, 0.36, 1) both',
                acercar: 'acercar 18s ease-out both',
                rebote: 'rebote 2.2s ease-in-out infinite',
            },
            boxShadow: {
                // Sombras muy suaves usadas por tarjetas y botones de la plataforma.
                // (En Tailwind 3 no existen por defecto; varias vistas ya las usaban.)
                '2xs': '0 1px 2px 0 rgb(15 23 42 / 0.04)',
                xs: '0 1px 3px 0 rgb(15 23 42 / 0.06)',
                card: '0 1px 3px 0 rgb(15 23 42 / 0.05), 0 1px 2px -1px rgb(15 23 42 / 0.03)',
                'card-hover': '0 10px 15px -3px rgb(15 23 42 / 0.08), 0 4px 6px -4px rgb(15 23 42 / 0.04)',
                'button-hover': '0 4px 6px -1px rgb(225 29 72 / 0.2), 0 2px 4px -2px rgb(225 29 72 / 0.1)',
            },
        },
    },

    plugins: [forms],
};
