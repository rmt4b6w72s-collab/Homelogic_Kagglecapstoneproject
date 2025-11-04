<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Healthcare Management System') }}</title>
        
        {{-- Suppress Cloudflare cookie warnings IMMEDIATELY - before any other scripts --}}
        <script>
            (function() {
                // Capture console methods before anything else
                const originalWarn = console.warn;
                const originalError = console.error;
                
                // Override console.warn
                console.warn = function() {
                    const message = Array.from(arguments).join(' ').toLowerCase();
                    if (message.includes('cookie') && (
                        message.includes('_cf_bm') || 
                        message.includes('__cf_bm') || 
                        message.includes('cf_clearance') ||
                        message.includes('rejected for invalid domain')
                    )) {
                        // Suppress Cloudflare cookie warnings
                        return;
                    }
                    originalWarn.apply(console, arguments);
                };
                
                // Override console.error
                console.error = function() {
                    const message = Array.from(arguments).join(' ').toLowerCase();
                    if (message.includes('cookie') && (
                        message.includes('_cf_bm') || 
                        message.includes('__cf_bm') || 
                        message.includes('cf_clearance') ||
                        message.includes('rejected for invalid domain')
                    )) {
                        // Suppress Cloudflare cookie errors
                        return;
                    }
                    originalError.apply(console, arguments);
                };
            })();
        </script>
        
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    </head>
    <body style="margin: 0; padding: 0;">
        <noscript>
            <div style="padding: 20px; text-align: center; background: white; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
                <div>
                    <h1 style="color: red;">JavaScript is Required</h1>
                    <p>Please enable JavaScript in your browser to use this application.</p>
                </div>
            </div>
        </noscript>
        <div id="react-app">
            <div style="padding: 20px; text-align: center; background: white; min-height: 100vh; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                <div class="spinner" style="border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin-bottom: 20px;"></div>
                <p style="color: #666; font-size: 16px;">Loading React application...</p>
                <style>
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                </style>
            </div>
        </div>
        <script>
            // Debug logging
            console.log('Page loaded, React app should initialize...');
            window.addEventListener('DOMContentLoaded', function() {
                console.log('DOM Content Loaded');
                const rootElement = document.getElementById('react-app');
                console.log('React root element found:', !!rootElement);
                if (rootElement) {
                    console.log('Root element:', rootElement);
                } else {
                    console.error('ERROR: React root element not found!');
                }
            });
            
            // Check for errors
            window.addEventListener('error', function(event) {
                console.error('Global error:', event.error);
                const rootElement = document.getElementById('react-app');
                if (rootElement && event.error) {
                    rootElement.innerHTML = `
                        <div style="padding: 40px; text-align: center; background: white; min-height: 100vh; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                            <h1 style="color: red; margin-bottom: 20px;">JavaScript Error</h1>
                            <p style="color: #666; margin-bottom: 10px;">${event.error.message}</p>
                            <pre style="background: #f5f5f5; padding: 20px; border-radius: 5px; text-align: left; overflow: auto; max-width: 800px; margin: 20px 0;">${event.error.stack || 'No stack trace available'}</pre>
                            <button onclick="window.location.reload()" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px;">
                                Reload Page
                            </button>
                        </div>
                    `;
                }
            });
        </script>
    </body>
</html>

