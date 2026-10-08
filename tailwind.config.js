import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

const t = (nome) => ({ opacityValue }) =>
    opacityValue === undefined
        ? `rgb(var(--${nome}))`
        : `rgb(var(--${nome}) / ${opacityValue})`;

export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {

                sans: ['Barlow', ...defaultTheme.fontFamily.sans],

                display: ['"Big Shoulders Display"', '"Arial Narrow"', 'Impact', ...defaultTheme.fontFamily.sans],

                label: ['"Barlow Condensed"', '"Arial Narrow"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },

            colors: {

                bg: t('bg'),
                surface: t('surface'),
                'surface-raised': t('surface-raised'),
                'surface-sunken': t('surface-sunken'),
                overlay: t('overlay'),

                fg: t('fg'),
                'fg-muted': t('fg-muted'),
                'fg-subtle': t('fg-subtle'),
                'fg-on-accent': t('fg-on-accent'),

                line: t('border'),
                'line-subtle': t('border-subtle'),
                'line-input': t('border-input'),
                'line-strong': t('border-strong'),

                accent: t('accent'),
                'accent-hover': t('accent-hover'),
                'accent-subtle': t('accent-subtle'),
                'accent-ring': t('accent-ring'),

                success: t('success'),
                'success-subtle': t('success-subtle'),
                warning: t('warning'),
                'warning-subtle': t('warning-subtle'),
                danger: t('danger'),
                'danger-subtle': t('danger-subtle'),
                info: t('info'),
                'info-subtle': t('info-subtle'),

                palco: {
                    data: '#FFD200',
                    creme: '#F4D9AE',
                    escuro: '#06040C',
                },
            },

            borderRadius: {

                sm: '6px',
                DEFAULT: '10px',
                md: '10px',
                lg: '16px',
                xl: '24px',
            },

            fontSize: {
                label: ['0.75rem', { lineHeight: '1.4', letterSpacing: '0.06em', fontWeight: '600' }],
            },

            keyframes: {

                flicker: {
                    '0%, 92%, 100%': { opacity: '1' },
                    '94%': { opacity: '0.55' },
                    '96%': { opacity: '1' },
                    '98%': { opacity: '0.7' },
                },
            },

            animation: {
                flicker: 'flicker 6s ease-in-out infinite',
            },
        },
    },

    plugins: [forms, typography],
};
