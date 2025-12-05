@php
    $facility = app()->bound('facility') ? app('facility') : null;
    $branding = $facility ? $facility->branding : null;
@endphp

@if($facility && $branding)
<script>
(function() {
    // Wait for Filament to load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateBranding);
    } else {
        updateBranding();
    }
    
    function updateBranding() {
        // Update brand name
        const brandNameElements = document.querySelectorAll('[data-filament-name="brand"]');
        brandNameElements.forEach(el => {
            if (el.textContent) {
                el.textContent = '{{ $branding['name'] }}';
            }
        });
        
        // Update brand logo
        const brandLogoElements = document.querySelectorAll('img[alt*="brand"], [data-filament-name="brand"] img, .fi-brand-logo img');
        brandLogoElements.forEach(img => {
            img.src = '{{ $branding['logo'] }}';
            img.onerror = function() {
                this.src = '{{ asset('images/logonew.png') }}';
            };
        });
        
        // Update CSS variables for colors
        const root = document.documentElement;
        const primaryColor = '{{ $branding['primary_color'] ?? '#1E3A5F' }}';
        const secondaryColor = '{{ $branding['secondary_color'] ?? '#86EFAC' }}';
        
        // Update Filament's CSS variables
        root.style.setProperty('--primary-50', adjustColor(primaryColor, 0.95));
        root.style.setProperty('--primary-100', adjustColor(primaryColor, 0.9));
        root.style.setProperty('--primary-200', adjustColor(primaryColor, 0.8));
        root.style.setProperty('--primary-300', adjustColor(primaryColor, 0.7));
        root.style.setProperty('--primary-400', adjustColor(primaryColor, 0.6));
        root.style.setProperty('--primary-500', primaryColor);
        root.style.setProperty('--primary-600', adjustColor(primaryColor, -0.1));
        root.style.setProperty('--primary-700', adjustColor(primaryColor, -0.2));
        root.style.setProperty('--primary-800', adjustColor(primaryColor, -0.3));
        root.style.setProperty('--primary-900', adjustColor(primaryColor, -0.4));
        
        // Force update Filament's color scheme
        setTimeout(() => {
            // Trigger a re-render by dispatching a custom event
            window.dispatchEvent(new Event('filament-branding-updated'));
        }, 100);
    }
    
    function adjustColor(hex, factor) {
        // Simple color adjustment - lighten or darken
        const num = parseInt(hex.replace('#', ''), 16);
        const r = Math.min(255, Math.max(0, (num >> 16) + Math.round(factor * 255)));
        const g = Math.min(255, Math.max(0, ((num >> 8) & 0x00FF) + Math.round(factor * 255)));
        const b = Math.min(255, Math.max(0, (num & 0x0000FF) + Math.round(factor * 255)));
        return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
    }
})();
</script>

<style>
/* Override Filament branding with facility colors */
:root {
    --primary-500: {{ $branding['primary_color'] ?? '#1E3A5F' }} !important;
    --success-500: {{ $branding['secondary_color'] ?? '#86EFAC' }} !important;
}

/* Update sidebar background if using primary color */
.fi-sidebar {
    background-color: {{ $branding['primary_color'] ?? '#1E3A5F' }} !important;
}

/* Update topbar if needed */
.fi-topbar {
    background-color: {{ $branding['primary_color'] ?? '#1E3A5F' }} !important;
}
</style>
@endif

