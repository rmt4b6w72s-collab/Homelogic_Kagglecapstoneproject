import { useEffect } from 'react';
import { lightenColor, darkenColor, addOpacity } from '../utils/colorUtils';

/**
 * Hook to set CSS custom properties (CSS variables) on the document root
 * Updates when theme colors change
 */
export function useThemeVariables(theme) {
    useEffect(() => {
        if (!theme) return;
        
        const root = document.documentElement;
        const {
            primary_color = '#25603E',
            secondary_color = '#8B4513',
            accent_color = '#F5F5DC',
        } = theme;
        
        // Primary color variants
        root.style.setProperty('--theme-primary', primary_color);
        root.style.setProperty('--theme-primary-hover', darkenColor(primary_color, 10));
        root.style.setProperty('--theme-primary-light', lightenColor(primary_color, 20));
        root.style.setProperty('--theme-primary-dark', darkenColor(primary_color, 15));
        root.style.setProperty('--theme-primary-lighter', lightenColor(primary_color, 30));
        root.style.setProperty('--theme-primary-lightest', lightenColor(primary_color, 40));
        
        // Secondary color variants
        root.style.setProperty('--theme-secondary', secondary_color);
        root.style.setProperty('--theme-secondary-hover', darkenColor(secondary_color, 10));
        root.style.setProperty('--theme-secondary-light', lightenColor(secondary_color, 20));
        root.style.setProperty('--theme-secondary-dark', darkenColor(secondary_color, 15));
        
        // Accent color
        root.style.setProperty('--theme-accent', accent_color);
        root.style.setProperty('--theme-accent-light', lightenColor(accent_color, 10));
        
        // Semantic colors with opacity for backgrounds
        root.style.setProperty('--theme-primary-bg', addOpacity(primary_color, 0.1));
        root.style.setProperty('--theme-primary-bg-light', addOpacity(primary_color, 0.05));
        root.style.setProperty('--theme-secondary-bg', addOpacity(secondary_color, 0.1));
        
        // Border colors
        root.style.setProperty('--theme-border', addOpacity(primary_color, 0.2));
        root.style.setProperty('--theme-border-light', addOpacity(primary_color, 0.1));
        
        // Text colors (ensure contrast)
        root.style.setProperty('--theme-text-primary', primary_color);
        root.style.setProperty('--theme-text-secondary', secondary_color);
        root.style.setProperty('--theme-text-on-primary', '#FFFFFF');
        root.style.setProperty('--theme-text-on-secondary', '#FFFFFF');
        
        // Focus ring colors
        root.style.setProperty('--theme-focus-ring', addOpacity(primary_color, 0.5));
        
        // Return cleanup function
        return () => {
            // Optionally reset to defaults on unmount
        };
    }, [theme]);
}

