/**
 * Laravel Echo Service — Reverb WebSocket backend
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import logger from '../utils/logger';

window.Pusher = Pusher;

const reverbKey    = import.meta.env.VITE_REVERB_APP_KEY    || '';
const reverbHost   = import.meta.env.VITE_REVERB_HOST       || 'localhost';
const reverbPort   = parseInt(import.meta.env.VITE_REVERB_PORT   || '8080', 10);
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME     || 'http';

function getAuthToken() {
    return localStorage.getItem('auth_token');
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

let echoInstance = null;

export function initializeEcho() {
    if (echoInstance) return echoInstance;

    if (!reverbKey) {
        logger.warn('[Echo] VITE_REVERB_APP_KEY not set. Real-time features disabled.');
        return null;
    }

    try {
        echoInstance = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: reverbHost,
            wsPort: reverbPort,
            wssPort: reverbPort,
            forceTLS: reverbScheme === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/api/v1/broadcasting/auth',
            auth: {
                headers: {
                    Authorization: `Bearer ${getAuthToken()}`,
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
            },
        });

        echoInstance.connector.pusher.connection.bind('connected', () => {
            logger.debug('[Echo] Connected to Reverb');
        });

        echoInstance.connector.pusher.connection.bind('disconnected', () => {
            logger.debug('[Echo] Disconnected from Reverb');
        });

        echoInstance.connector.pusher.connection.bind('error', (error) => {
            logger.error('[Echo] Connection error:', error);
        });

        logger.debug('[Echo] Initialized successfully (Reverb)');
        return echoInstance;
    } catch (error) {
        logger.error('[Echo] Failed to initialize:', error);
        return null;
    }
}

export function getEcho() {
    if (!echoInstance) return initializeEcho();
    return echoInstance;
}

export function disconnectEcho() {
    if (echoInstance) {
        echoInstance.disconnect();
        echoInstance = null;
        logger.debug('[Echo] Disconnected');
    }
}

/** Call after a fresh login so the auth header is up-to-date */
export function reconnectEcho() {
    disconnectEcho();
    return initializeEcho();
}

export default getEcho;
