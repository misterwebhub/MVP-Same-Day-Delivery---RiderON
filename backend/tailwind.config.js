import defaultTheme from 'tailwindcss/defaultTheme';

/**
 * RiderON admin panel theme — colors transcribed 1:1 from
 * packages/design-tokens/src/colors.ts so the admin panel matches the
 * customer/partner mobile apps exactly. Fonts mirror
 * packages/design-tokens/src/typography.ts (Poppins for headings, Inter
 * for body), loaded via Google Fonts in resources/views/admin/layout.blade.php.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: '#FF6A00',
                    dark: '#E85A00',
                    tint: '#FFF1E6',
                },
                secondary: {
                    DEFAULT: '#0A1B3D',
                    tint: '#13284F',
                },
                surface: '#FFFFFF',
                background: '#F5F6FA',
                'text-primary': '#0F172A',
                'text-secondary': '#6B7280',
                'text-inverse': '#FFFFFF',
                border: '#E5E7EB',
                success: '#16A34A',
                warning: '#F59E0B',
                danger: '#DC2626',
                info: '#2563EB',
                tint: {
                    'orange-bg': '#FFE8D9',
                    'orange-icon': '#FF6A00',
                    'blue-bg': '#DCEAFF',
                    'blue-icon': '#2563EB',
                    'green-bg': '#DFF5E3',
                    'green-icon': '#16A34A',
                    'purple-bg': '#EAE1FB',
                    'purple-icon': '#7C3AED',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                heading: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                display: ['28px', { lineHeight: '36px', fontWeight: '600' }],
                h1: ['22px', { lineHeight: '28px', fontWeight: '600' }],
                h2: ['18px', { lineHeight: '24px', fontWeight: '600' }],
                body: ['15px', { lineHeight: '22px', fontWeight: '400' }],
                'body-strong': ['15px', { lineHeight: '22px', fontWeight: '600' }],
                caption: ['13px', { lineHeight: '18px', fontWeight: '400' }],
                micro: ['11px', { lineHeight: '14px', fontWeight: '500', letterSpacing: '0.04em' }],
            },
            borderRadius: {
                sm: '8px',
                md: '12px',
                lg: '16px',
                pill: '999px',
            },
            boxShadow: {
                card: '0 2px 8px 0 rgba(10, 27, 61, 0.08)',
            },
        },
    },
    plugins: [],
};
