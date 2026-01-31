/**
 * Design Tokens - Centralized color system
 */
const tokens = {
  colors: {
    // Primary Colors
    primary: '#059669', // Emerald-600
    primaryLight: '#34d399', // Emerald-400
    primaryDark: '#047857', // Emerald-700
    primaryBg: '#d1fae5', // Emerald-100

    // Secondary Colors  
    secondary: '#64748b', // Slate-500
    secondaryLight: '#94a3b8', // Slate-400
    secondaryDark: '#475569', // Slate-600

    // Danger Colors
    danger: '#dc2626', // Red-600
    dangerLight: '#f87171', // Red-400
    dangerDark: '#b91c1c', // Red-700
    dangerBg: '#fee2e2', // Red-100
    dangerText: '#991b1b', // Red-800

    // Warning Colors
    warning: '#f59e0b', // Amber-500
    warningLight: '#fbbf24', // Amber-400
    warningDark: '#d97706', // Amber-600
    warningBg: '#fef3c7', // Amber-100
    warningText: '#92400e', // Amber-800

    // Info Colors
    info: '#3b82f6', // Blue-500
    infoLight: '#60a5fa', // Blue-400
    infoDark: '#2563eb', // Blue-600
    infoBg: '#dbeafe', // Blue-100
    infoText: '#1e40af', // Blue-800

    // Success Colors
    success: '#10b981', // Emerald-500
    successLight: '#34d399', // Emerald-400
    successDark: '#059669', // Emerald-600  
    successBg: '#d1fae5', // Emerald-100
    successText: '#065f46', // Emerald-800

    // Neutral Colors
    background: '#f8fafc', // Slate-50
    surface: '#ffffff', // White
    surfaceHighlight: '#f1f5f9', // Slate-100
    border: '#e2e8f0', // Slate-200
    borderLight: '#f1f5f9', // Slate-100
    borderDark: '#cbd5e1', // Slate-300

    // Text Colors  
    textMain: '#1e293b', // Slate-800
    textSecondary: '#64748b', // Slate-500
    textMuted: '#94a3b8', // Slate-400
    textInverse: '#ffffff', // White
  },

  spacing: {
    xs: '0.25rem',   // 4px
    sm: '0.5rem',    // 8px
    md: '0.75rem',   // 12px
    lg: '1rem',      // 16px
    xl: '1.5rem',    // 24px
    '2xl': '2rem',   // 32px
    '3xl': '3rem',   // 48px
    '4xl': '4rem',   // 64px
  },

  radius: {
    sm: '0.25rem',   // 4px
    md: '0.375rem',  // 6px
    lg: '0.5rem',    // 8px
    xl: '0.75rem',   // 12px
    '2xl': '1rem',   // 16px
    full: '9999px',  // Fully rounded
  },

  fontWeight: {
    normal: '400',
    medium: '500',
    semibold: '600',
    bold: '700',
  },

  shadow: {
    sm: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
    md: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
    lg: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
    xl: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
  },

  transition: {
    fast: '0.15s ease',
    normal: '0.25s ease',
    slow: '0.4s ease',
  },
};

/**
 * Inject design tokens as CSS custom properties
 */
export function injectTokensAsCSS() {
  const root = document.documentElement;

  // Inject colors
  Object.entries(tokens.colors).forEach(([key, value]) => {
    const cssVarName = `--color-${key}`;
    root.style.setProperty(cssVarName, value);
  });

  // Inject spacing
  Object.entries(tokens.spacing).forEach(([key, value]) => {
    const cssVarName = `--spacing-${key}`;
    root.style.setProperty(cssVarName, value);
  });

  // Inject border radius
  Object.entries(tokens.radius).forEach(([key, value]) => {
    const cssVarName = `--radius-${key}`;
    root.style.setProperty(cssVarName, value);
  });

  // Inject font weights
  Object.entries(tokens.fontWeight).forEach(([key, value]) => {
    const cssVarName = `--fw-${key}`;
    root.style.setProperty(cssVarName, value);
  });

  // Inject shadows
  Object.entries(tokens.shadow).forEach(([key, value]) => {
    const cssVarName = `--shadow-${key}`;
    root.style.setProperty(cssVarName, value);
  });

  // Inject transitions
  Object.entries(tokens.transition).forEach(([key, value]) => {
    const cssVarName = `--transition-${key}`;
    root.style.setProperty(cssVarName, value);
  });
}

export default tokens;
