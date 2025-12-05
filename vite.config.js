import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        react(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
        tailwindcss(),
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
        commonjsOptions: {
            include: [/node_modules/],
            transformMixedEsModules: true,
        },
        rollupOptions: {
            // Preserve entry signatures to maintain proper module boundaries
            preserveEntrySignatures: 'exports-only',
            output: {
                // Use ES module format
                format: 'es',
                // Better chunk naming for production
                chunkFileNames: 'assets/[name]-[hash].js',
                entryFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]',
                // Manual chunking strategy for better caching and loading
                manualChunks: (id) => {
                    // Vendor chunks
                    if (id.includes('node_modules')) {
                        if (id.includes('react') || id.includes('react-dom')) {
                            return 'vendor-react';
                        }
                        if (id.includes('chart.js') || id.includes('react-chartjs-2')) {
                            return 'vendor-charts';
                        }
                        if (id.includes('@tanstack')) {
                            return 'vendor-query';
                        }
                        return 'vendor';
                    }
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
            // Don't do any code transformations
            treeShaking: false,
        },
        // CSS handling - ensure CSS is properly extracted and linked
        cssCodeSplit: false, // Don't split CSS to avoid preload issues
        cssMinify: true,
    },
});
