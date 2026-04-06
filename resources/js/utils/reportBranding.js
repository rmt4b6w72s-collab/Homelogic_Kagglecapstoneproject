/**
 * Match App\Support\ReportBranding::palette() so screen/print reports use the same tints as PDFs.
 */

export function sanitizeHex(color, fallback) {
    const c = typeof color === 'string' ? color.trim() : '';
    return /^#[0-9A-Fa-f]{6}$/.test(c) ? c : fallback;
}

export function lightenHex(hex, towardWhite) {
    const h = String(hex || '').replace('#', '');
    if (h.length !== 6) return '#f4f7fb';
    const t = Math.max(0, Math.min(1, towardWhite));
    const r = parseInt(h.slice(0, 2), 16);
    const g = parseInt(h.slice(2, 4), 16);
    const b = parseInt(h.slice(4, 6), 16);
    const mix = (c) => Math.round(c + (255 - c) * t);
    const rr = mix(r).toString(16).padStart(2, '0');
    const gg = mix(g).toString(16).padStart(2, '0');
    const bb = mix(b).toString(16).padStart(2, '0');
    return `#${rr}${gg}${bb}`;
}

function nearWhiteHex(hex) {
    const h = String(hex || '').replace('#', '');
    if (h.length !== 6) return true;
    const r = parseInt(h.slice(0, 2), 16);
    const g = parseInt(h.slice(2, 4), 16);
    const b = parseInt(h.slice(4, 6), 16);
    return r >= 245 && g >= 245 && b >= 245;
}

/**
 * @param {object|null} branding — user.facility_branding from API
 */
export function buildReportPalette(branding) {
    const primaryColor = sanitizeHex(branding?.primary_color, '#1E3A5F');
    const secondaryColor = sanitizeHex(branding?.secondary_color, '#86EFAC');
    const accentColor = sanitizeHex(branding?.accent_color, '#FFFFFF');

    const headerTint = nearWhiteHex(secondaryColor)
        ? lightenHex(primaryColor, 0.94)
        : lightenHex(secondaryColor, 0.91);

    const tableHeaderBg = nearWhiteHex(secondaryColor)
        ? lightenHex(primaryColor, 0.92)
        : lightenHex(secondaryColor, 0.86);

    const infoHeaderBg = nearWhiteHex(secondaryColor)
        ? lightenHex(primaryColor, 0.93)
        : lightenHex(secondaryColor, 0.84);

    const legendBg = nearWhiteHex(secondaryColor)
        ? lightenHex(primaryColor, 0.96)
        : lightenHex(secondaryColor, 0.94);

    const brandBorder = lightenHex(primaryColor, 0.78);
    const gridBorder = lightenHex(primaryColor, 0.72);

    return {
        primaryColor,
        secondaryColor,
        accentColor,
        headerTint,
        tableHeaderBg,
        infoHeaderBg,
        legendBg,
        brandBorder,
        gridBorder,
    };
}
