/**
 * Design Token System - Official University Portal
 * Strict palette: Deep Teal + Academic Blue + Grayscale.
 * Premium University Student Services Dashboard.
 */

export const tokens = {
    colors: {
        // Primary Brand (University Teal)
        primary: '#1f4e5f',
        primaryDark: '#163a4a', // Darker shade for hover/active
        primaryLight: '#e6f2f4', // Very light shade for backgrounds

        // Secondary (Academic Blue)
        secondary: '#1f6fff',
        secondaryDark: '#165ac6',

        // Neutrals / Surfaces
        background: '#f5f7fa',
        surface: '#ffffff',
        surfaceHighlight: '#f8f9fa',

        // Borders
        border: '#dfe3e8',
        borderLight: '#edf2f7',
        borderFocus: '#1f4e5f',

        // Typography
        textStrong: '#1c2430',
        textMain: '#374151',
        textMuted: '#6b7280',
        textInverted: '#ffffff',

        // Status Colors (Clean & Visible)
        success: '#157347',     // Deep Green
        successBg: '#d1e7dd',   // Tinted bg
        successText: '#0f5132', // Dark text

        warning: '#d97706',     // Amber 600 (clean orange)
        warningBg: '#fffbeb',   // Amber 50 (light cream)
        warningText: '#92400e', // Amber 800 (dark for contrast)

        danger: '#bb2d3b',      // Official Red
        dangerBg: '#f8d7da',
        dangerText: '#842029',

        info: '#0d6efd',        // Blue Info
        infoBg: '#cff4fc',
        infoText: '#055160',

        neutral: '#6c757d',
        neutralBg: '#e9ecef',
        neutralText: '#495057',

        // Extra UI Colors
        sidebarHover: '#eef4f6',
        activeBg: '#e6f2f2',
        focusRing: 'rgba(31,78,95,0.25)',
    },

    fontSizes: {
        xs: '12px',
        sm: '13px',
        base: '14px',
        md: '15px',
        lg: '16px',
        xl: '18px',
        '2xl': '24px',
        '3xl': '30px',
    },

    fontWeights: {
        normal: '400',
        medium: '500',
        semibold: '600',
        bold: '700',
    },

    radii: {
        sm: '4px',
        md: '6px',
        lg: '8px',
        xl: '12px',
        full: '9999px',
    },

    spacing: {
        xs: '4px',
        sm: '8px',
        md: '16px',
        lg: '24px',
        xl: '32px',
        '2xl': '48px',
        '3xl': '64px',
        '4xl': '96px',
    },

    shadows: {
        none: 'none',
        sm: '0 1px 2px rgba(0, 0, 0, 0.05)',
        md: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)', // Slightly stronger lift
        lg: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
        focus: '0 0 0 4px var(--color-focusRing)', // Using variable for consistency
    },

    transitions: {
        fast: '150ms ease-in-out',
        normal: '200ms ease-in-out',
    }
};

/**
 * Inject tokens as CSS variables into :root
 */
export function injectTokensAsCSS() {
    if (typeof document === 'undefined') return;

    const root = document.documentElement;
    const setProp = (prefix, obj) => {
        Object.entries(obj).forEach(([key, value]) => {
            root.style.setProperty(`--${prefix}-${key}`, value);
        });
    };

    setProp('color', tokens.colors);
    setProp('font', tokens.fontSizes);
    setProp('fw', tokens.fontWeights);
    setProp('radius', tokens.radii);
    setProp('spacing', tokens.spacing);
    setProp('shadow', tokens.shadows);
}
