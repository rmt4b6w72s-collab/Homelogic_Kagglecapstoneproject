import React, { createContext, useContext, useMemo } from 'react';
import { useThemeVariables } from '../hooks/useThemeVariables';
import { lightenColor, darkenColor, ensureContrast, getContrastColor } from '../utils/colorUtils';

const ThemeContext = createContext(null);

/**
 * Theme Provider Component
 * Provides facility branding colors and calculated variants to all child components
 */
export function ThemeProvider({ children, facilityBranding }) {
    // Default theme if no facility branding provided
    const defaultTheme = {
        name: 'Evergreen Oasis Care Home',
        logo: '/images/logo.jpeg',
        primary_color: '#25603E',
        secondary_color: '#8B4513',
        accent_color: '#F5F5DC',
    };
    
    // Use facility branding or defaults
    const theme = useMemo(() => {
        if (!facilityBranding) {
            return defaultTheme;
        }
        
        return {
            name: facilityBranding.name || defaultTheme.name,
            logo: facilityBranding.logo || defaultTheme.logo,
            primary_color: facilityBranding.primary_color || defaultTheme.primary_color,
            secondary_color: facilityBranding.secondary_color || defaultTheme.secondary_color,
            accent_color: facilityBranding.accent_color || defaultTheme.accent_color,
        };
    }, [facilityBranding]);
    
    // Calculate color variants
    const themeValues = useMemo(() => {
        const primary = theme.primary_color;
        const secondary = theme.secondary_color;
        const accent = theme.accent_color;
        
        return {
            // Base colors
            primary,
            secondary,
            accent,
            
            // Primary variants
            primaryHover: darkenColor(primary, 10),
            primaryLight: lightenColor(primary, 20),
            primaryDark: darkenColor(primary, 15),
            primaryLighter: lightenColor(primary, 30),
            primaryLightest: lightenColor(primary, 40),
            
            // Secondary variants
            secondaryHover: darkenColor(secondary, 10),
            secondaryLight: lightenColor(secondary, 20),
            secondaryDark: darkenColor(secondary, 15),
            
            // Accent variants
            accentLight: lightenColor(accent, 10),
            
            // Text colors (with contrast checking)
            textOnPrimary: getContrastColor(primary),
            textOnSecondary: getContrastColor(secondary),
            textOnAccent: getContrastColor(accent),
            
            // Border colors (with opacity)
            border: primary,
            borderLight: lightenColor(primary, 30),
            
            // Background colors (with opacity)
            primaryBg: primary,
            primaryBgLight: lightenColor(primary, 40),
            secondaryBg: secondary,
        };
    }, [theme]);
    
    // Set CSS variables on document root
    useThemeVariables(theme);
    
    const value = useMemo(() => ({
        theme,
        ...themeValues,
        // Helper functions
        lighten: (color, percent) => lightenColor(color, percent),
        darken: (color, percent) => darkenColor(color, percent),
        ensureContrast: (foreground, background) => ensureContrast(foreground, background),
        getContrastColor: (background) => getContrastColor(background),
    }), [theme, themeValues]);
    
    return (
        <ThemeContext.Provider value={value}>
            {children}
        </ThemeContext.Provider>
    );
}

/**
 * Hook to access theme values
 */
export function useTheme() {
    const context = useContext(ThemeContext);
    
    if (!context) {
        // Return default theme if used outside provider
        return {
            theme: {
                name: 'Evergreen Oasis Care Home',
                logo: '/images/logo.jpeg',
                primary_color: '#25603E',
                secondary_color: '#8B4513',
                accent_color: '#F5F5DC',
            },
            primary: '#25603E',
            secondary: '#8B4513',
            accent: '#F5F5DC',
            primaryHover: '#1B402D',
            primaryLight: '#4a7a5a',
            primaryDark: '#1B402D',
            lighten: (color, percent) => color,
            darken: (color, percent) => color,
        };
    }
    
    return context;
}

