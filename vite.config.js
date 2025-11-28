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
    },
    build: {
        commonjsOptions: {
            include: [/node_modules/],
            transformMixedEsModules: true,
        },
        rollupOptions: {
            output: {
                // Manual chunking to control how code is split
                manualChunks: (id) => {
                    if (id.includes('node_modules')) {
                        // React and React DOM together
                        if (id.includes('react') || id.includes('react-dom')) {
                            return 'vendor-react';
                        }
                        // All other vendor code in one chunk
                        return 'vendor';
                    }
                },
            },
        },
        // Warn if chunk exceeds 500KB
        chunkSizeWarningLimit: 500,
        // Disable sourcemaps for production
        sourcemap: false,
        // Use esbuild with only whitespace minification
        // This avoids variable reordering that causes TDZ issues
        minify: 'esbuild',
        // Target modern browsers
        target: 'es2020',
        // Configure esbuild to only remove whitespace, not transform code
        esbuild: {
            legalComments: 'none',
            minifyIdentifiers: false,  // Don't rename variables
            minifySyntax: false,       // Don't transform syntax
            minifyWhitespace: true,    // Only remove whitespace
        },
    },
});
