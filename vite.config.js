import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import viteCompression from 'vite-plugin-compression';

export default defineConfig({
    plugins: [
        react(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'], // Include both CSS and JS as separate entries
            refresh: true,
        }),
        tailwindcss(),
        viteCompression({
            algorithm: 'gzip',
            ext: '.gz',
            deleteOriginFile: false,
        }),
        viteCompression({
            algorithm: 'brotliCompress',
            ext: '.br',
            deleteOriginFile: false,
        }),
    ],
    server: {
        hmr: {
            host: 'localhost',
        },
    },
    optimizeDeps: {
        include: ['animejs'],
        // Force pre-bundling of critical dependencies to avoid TDZ issues
        force: true,
        esbuildOptions: {
            // Don't minify during pre-bundling to avoid TDZ issues
            minify: false,
        },
    },
    build: {
        // Clear cache before build to ensure fresh builds
        emptyOutDir: true,
        commonjsOptions: {
            include: [/node_modules/],
            transformMixedEsModules: true,
        },
        rollupOptions: {
            // Preserve entry signatures to maintain proper module boundaries
            preserveEntrySignatures: 'strict',
            output: {
                // Use ES module format
                format: 'es',
                // Better chunk naming for production
                chunkFileNames: 'assets/[name]-[hash].js',
                entryFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]',
                // Split large vendors for parallel cache/load (entry still loads first)
                manualChunks(id) {
                    if (id.includes('node_modules/react-dom') || id.includes('node_modules/react/')) {
                        return 'react-vendor';
                    }
                    if (id.includes('node_modules/chart.js') || id.includes('node_modules/react-chartjs')) {
                        return 'chart-vendor';
                    }
                    return undefined;
                },
            },
        },
        // Warn if chunk exceeds 1000KB
        chunkSizeWarningLimit: 1000, // Increase limit since we're not minifying
        // Disable sourcemaps for production
        sourcemap: false,
        // Use esbuild with minimal settings - only compress whitespace
        minify: 'esbuild',
        // Target modern browsers
        target: 'es2020',
        // Configure esbuild to be extremely conservative
        esbuild: {
            legalComments: 'none',
            // CRITICAL: Don't rename or transform anything
            minifyIdentifiers: false,
            minifySyntax: false,
            minifyWhitespace: true, // Only remove whitespace
            // Enable tree-shaking to reduce bundle size (revisit if TDZ regressions appear in QA)
            treeShaking: true,
        },
        // CSS handling - ensure CSS is properly extracted and linked
        cssCodeSplit: true, // Enable CSS code splitting - it works better with Laravel Vite plugin
        cssMinify: true,
    },
});
