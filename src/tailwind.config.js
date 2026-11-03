import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/*
 * Tailwind do PAINEL (03/11/2026, pendência 246). Só o painel (Vue) usa: o
 * site público é Blade com CSS próprio e não passa por aqui — por isso o
 * `content` olha só `resources/js`.
 *
 * As cores são os tokens do site em trio RGB (`resources/css/tokens-do-painel.css`,
 * gerado). Os nomes são os do molde do Duo, para as telas copiadas de lá
 * funcionarem sem reescrever classe.
 */
const t = (nome) => ({ opacityValue }) =>
    opacityValue === undefined
        ? `rgb(var(--${nome}))`
        : `rgb(var(--${nome}) / ${opacityValue})`;

export default {
    darkMode: 'class',

    content: [
        './resources/views/app.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            // As três famílias do site (layouts/site.blade.php), pelo bunny.net.
            fontFamily: {
                sans: ['"Instrument Sans"', ...defaultTheme.fontFamily.sans],
                display: ['"Bricolage Grotesque"', ...defaultTheme.fontFamily.sans],
                label: ['"Instrument Sans"', ...defaultTheme.fontFamily.sans],
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
            },
            // Os raios do site: 10px no botão, 16px no cartão.
            borderRadius: {
                sm: '6px',
                DEFAULT: '10px',
                md: '10px',
                lg: '16px',
                xl: '16px',
                card: '16px',
            },
            fontSize: {
                label: ['0.75rem', { lineHeight: '1.4', letterSpacing: '0.06em', fontWeight: '600' }],
            },
        },
    },

    plugins: [forms],
};
