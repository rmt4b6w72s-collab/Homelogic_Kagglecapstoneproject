import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import App from './App';
import ErrorBoundary from './components/ErrorBoundary';
import { ToastProvider } from './contexts/ToastContext';
import ThemeWrapper from './components/ThemeWrapper';
import '../css/app.css';

// Suppress Cloudflare cookie warnings - after imports
// This prevents these harmless errors from cluttering the console
(function() {
    const originalWarn = console.warn;
    const originalError = console.error;
    const originalLog = console.log;
    
    // Helper function to check if message should be suppressed
    function shouldSuppress(message) {
        const lowerMessage = message.toString().toLowerCase();
        return (
            lowerMessage.includes('cookie') && (
                lowerMessage.includes('_cf_bm') || 
                lowerMessage.includes('__cf_bm') || 
                lowerMessage.includes('cf_clearance') ||
                lowerMessage.includes('cf_bm') ||
                lowerMessage.includes('rejected for invalid domain') ||
                lowerMessage.includes('has been rejected')
            )
        ) || (
            lowerMessage.includes('__cf_bm') ||
            lowerMessage.includes('_cf_bm')
        );
    }
    
    console.warn = function(...args) {
        const message = args.join(' ');
        if (shouldSuppress(message)) {
            return; // Suppress Cloudflare cookie warnings
        }
        originalWarn.apply(console, args);
    };
    
    console.error = function(...args) {
        const message = args.join(' ');
        if (shouldSuppress(message)) {
            return; // Suppress Cloudflare cookie errors
        }
        originalError.apply(console, args);
    };
    
    // Also override console.log in case errors are logged there
    console.log = function(...args) {
        const message = args.join(' ');
        if (shouldSuppress(message)) {
            return; // Suppress Cloudflare cookie logs
        }
        originalLog.apply(console, args);
    };
})();

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            refetchOnWindowFocus: false,
            retry: 1,
        },
    },
});

// Wait for DOM to be ready
function initApp() {
    const rootElement = document.getElementById('react-app');
    if (!rootElement) {
        console.error('React app root element (#react-app) not found');
        document.body.innerHTML = `
            <div style="padding: 20px; text-align: center; font-family: sans-serif; background: white;">
                <h1 style="color: red;">Error: React root element not found</h1>
                <p>Looking for element with id="react-app"</p>
                <p style="margin-top: 20px; color: #666;">Please check the browser console for more details.</p>
            </div>
        `;
        return;
    }

    console.log('React app root element found, initializing...');
    
    // First, render a simple test to verify React is working
    try {
        const root = ReactDOM.createRoot(rootElement);
        
        // Clear any existing content
        rootElement.innerHTML = '';
        
        // Render a simple test first
        console.log('Rendering test component...');
        root.render(
            React.createElement('div', { 
                style: { 
                    padding: '40px', 
                    textAlign: 'center', 
                    background: '#f0f0f0', 
                    minHeight: '100vh',
                    fontFamily: 'Arial, sans-serif'
                } 
            },
                React.createElement('h1', { style: { color: 'green', marginBottom: '20px' } }, '✓ React is Working!'),
                React.createElement('p', { style: { color: '#666', marginBottom: '20px' } }, 'React loaded successfully. Now loading full app...'),
                React.createElement('div', { 
                    style: { 
                        marginTop: '40px',
                        padding: '20px',
                        background: 'white',
                        borderRadius: '8px',
                        boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                    }
                },
                    React.createElement('p', { style: { color: '#333', marginBottom: '10px' } }, 'If you see this message, React is rendering correctly.'),
                    React.createElement('p', { style: { color: '#666', fontSize: '14px' } }, 'The full app should load below in a moment...')
                )
            )
        );
        
        console.log('Test render successful, now rendering full app...');
        
        // Wait a moment, then render the full app
        setTimeout(() => {
            try {
                root.render(
                    <React.StrictMode>
                        <ErrorBoundary>
                            <QueryClientProvider client={queryClient}>
                                <ThemeWrapper>
                                    <ToastProvider>
                                        <BrowserRouter basename="/app">
                                            <App />
                                        </BrowserRouter>
                                    </ToastProvider>
                                </ThemeWrapper>
                            </QueryClientProvider>
                        </ErrorBoundary>
                    </React.StrictMode>
                );
                console.log('React app rendered successfully');
            } catch (renderError) {
                console.error('Error rendering full app:', renderError);
                root.render(
                    React.createElement('div', { 
                        style: { padding: '40px', textAlign: 'center', background: 'white', minHeight: '100vh' } 
                    },
                        React.createElement('h1', { style: { color: 'red' } }, 'Error Loading Full App'),
                        React.createElement('p', { style: { color: '#666' } }, renderError.message),
                        React.createElement('button', { 
                            onClick: () => window.location.reload(),
                            style: { padding: '10px 20px', marginTop: '20px', background: '#3b82f6', color: 'white', border: 'none', borderRadius: '5px', cursor: 'pointer' }
                        }, 'Reload Page')
                    )
                );
            }
        }, 500);
    } catch (error) {
        console.error('Error rendering React app:', error);
        rootElement.innerHTML = `
            <div style="padding: 20px; text-align: center; font-family: sans-serif; background: white;">
                <h1 style="color: red; margin-bottom: 20px;">Error Loading Application</h1>
                <p style="color: #666; margin-bottom: 10px;">${error.message}</p>
                <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; text-align: left; overflow: auto; max-width: 800px; margin: 0 auto;">${error.stack}</pre>
                <button onclick="window.location.reload()" style="margin-top: 20px; padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    Reload Page
                </button>
            </div>
        `;
    }
}

// Initialize immediately - don't wait for DOMContentLoaded
// This ensures React initializes as soon as the script loads
console.log('app.jsx file loaded, readyState:', document.readyState);

// Try to initialize immediately
try {
    initApp();
} catch (error) {
    console.error('Error during immediate init:', error);
    // If immediate init fails, wait for DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initApp);
    } else {
        // Try again after a short delay
        setTimeout(initApp, 100);
    }
}

