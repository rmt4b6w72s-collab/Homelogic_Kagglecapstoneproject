/**
 * Color utility functions for theme calculations
 * Ensures proper contrast and accessibility
 */

/**
 * Convert hex color to RGB
 */
export function hexToRgb(hex) {
    if (!hex) return null;
    
    // Remove # if present
    hex = hex.replace('#', '');
    
    // Handle 3-character hex
    if (hex.length === 3) {
        hex = hex.split('').map(char => char + char).join('');
    }
    
    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);
    
    return { r, g, b };
}

/**
 * Convert RGB to hex
 */
export function rgbToHex(r, g, b) {
    return '#' + [r, g, b].map(x => {
        const hex = x.toString(16);
        return hex.length === 1 ? '0' + hex : hex;
    }).join('');
}

/**
 * Lighten a color by a percentage
 */
export function lightenColor(color, percent = 10) {
    if (!color) return '#25603E';
    
    const rgb = hexToRgb(color);
    if (!rgb) return color;
    
    const r = Math.min(255, Math.round(rgb.r + (255 - rgb.r) * (percent / 100)));
    const g = Math.min(255, Math.round(rgb.g + (255 - rgb.g) * (percent / 100)));
    const b = Math.min(255, Math.round(rgb.b + (255 - rgb.b) * (percent / 100)));
    
    return rgbToHex(r, g, b);
}

/**
 * Darken a color by a percentage
 */
export function darkenColor(color, percent = 10) {
    if (!color) return '#1B402D';
    
    const rgb = hexToRgb(color);
    if (!rgb) return color;
    
    const r = Math.max(0, Math.round(rgb.r * (1 - percent / 100)));
    const g = Math.max(0, Math.round(rgb.g * (1 - percent / 100)));
    const b = Math.max(0, Math.round(rgb.b * (1 - percent / 100)));
    
    return rgbToHex(r, g, b);
}

/**
 * Get relative luminance of a color (for contrast calculation)
 */
export function getLuminance(color) {
    const rgb = hexToRgb(color);
    if (!rgb) return 0.5;
    
    const [r, g, b] = [rgb.r, rgb.g, rgb.b].map(val => {
        val = val / 255;
        return val <= 0.03928 ? val / 12.92 : Math.pow((val + 0.055) / 1.055, 2.4);
    });
    
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

/**
 * Calculate contrast ratio between two colors
 */
export function getContrastRatio(color1, color2) {
    const lum1 = getLuminance(color1);
    const lum2 = getLuminance(color2);
    
    const lighter = Math.max(lum1, lum2);
    const darker = Math.min(lum1, lum2);
    
    return (lighter + 0.05) / (darker + 0.05);
}

/**
 * Determine if text should be white or black on a background color
 */
export function getContrastColor(backgroundColor) {
    if (!backgroundColor) return '#000000';
    
    const whiteContrast = getContrastRatio(backgroundColor, '#FFFFFF');
    const blackContrast = getContrastRatio(backgroundColor, '#000000');
    
    return whiteContrast > blackContrast ? '#FFFFFF' : '#000000';
}

/**
 * Ensure text color has sufficient contrast on background
 */
export function ensureContrast(foreground, background, minRatio = 4.5) {
    const ratio = getContrastRatio(foreground, background);
    
    if (ratio >= minRatio) {
        return foreground;
    }
    
    // If contrast is insufficient, return appropriate contrast color
    return getContrastColor(background);
}

/**
 * Mix two colors
 */
export function mixColors(color1, color2, weight = 0.5) {
    const rgb1 = hexToRgb(color1);
    const rgb2 = hexToRgb(color2);
    
    if (!rgb1 || !rgb2) return color1 || color2;
    
    const w = weight;
    const r = Math.round(rgb1.r * (1 - w) + rgb2.r * w);
    const g = Math.round(rgb1.g * (1 - w) + rgb2.g * w);
    const b = Math.round(rgb1.b * (1 - w) + rgb2.b * w);
    
    return rgbToHex(r, g, b);
}

/**
 * Add opacity to a color (returns rgba string)
 */
export function addOpacity(color, opacity = 0.1) {
    const rgb = hexToRgb(color);
    if (!rgb) return color;
    
    return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${opacity})`;
}

