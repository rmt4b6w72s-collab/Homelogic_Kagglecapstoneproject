import axios from 'axios';

const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    withCredentials: true,
});

const getStoredAuthToken = () => {
    const candidates = [
        localStorage.getItem('auth_token'),
        localStorage.getItem('token'),
        localStorage.getItem('access_token'),
    ];

    const token = candidates.find((value) =>
        typeof value === 'string' &&
        value.trim() !== '' &&
        value !== 'null' &&
        value !== 'undefined'
    );

    return token || null;
};

const clearStoredAuth = () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('token');
    localStorage.removeItem('access_token');
    localStorage.removeItem('user_name');
    localStorage.removeItem('user_role');
};

// Add CSRF token to requests
api.interceptors.request.use((config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token;
    }
    
    // Add auth token if available
    const authToken = getStoredAuthToken();
    if (authToken) {
        config.headers['Authorization'] = `Bearer ${authToken}`;
    }
    
    // For FormData (file uploads), let browser set Content-Type automatically
    if (config.data instanceof FormData) {
        delete config.headers['Content-Type'];
    }
    
    return config;
});

// Handle 401 responses (unauthorized)
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Don't redirect if we're on a public page
            const currentPath = window.location.pathname;
            
            // Define public paths - exact matches or specific prefixes
            const isPublicPath = 
                currentPath === '/' ||  // Root welcome page
                currentPath === '/login' ||
                currentPath.startsWith('/login/') ||
                currentPath === '/staff/clock-in' ||
                currentPath.startsWith('/staff/clock-in/') ||
                currentPath === '/features' ||
                currentPath.startsWith('/features/') ||
                currentPath === '/pricing' ||
                currentPath.startsWith('/pricing/') ||
                currentPath === '/modules' ||
                currentPath.startsWith('/modules/') ||
                currentPath === '/security' ||
                currentPath.startsWith('/security/') ||
                currentPath === '/about' ||
                currentPath.startsWith('/about/') ||
                currentPath === '/contact' ||
                currentPath.startsWith('/contact/') ||
                currentPath === '/support' ||
                currentPath.startsWith('/support/') ||
                currentPath === '/privacy-policy' ||
                currentPath.startsWith('/privacy-policy/') ||
                currentPath === '/terms-of-service' ||
                currentPath.startsWith('/terms-of-service/') ||
                currentPath === '/hipaa-compliance' ||
                currentPath.startsWith('/hipaa-compliance/') ||
                currentPath === '/cookie-policy' ||
                currentPath.startsWith('/cookie-policy/');
            
            // On public pages, don't force logout/redirect.
            if (isPublicPath) {
                return Promise.reject(error);
            }

            // Any 401 on protected routes means auth is no longer valid.
            clearStoredAuth();
            sessionStorage.setItem('session_expired', '1');
            
            // Only redirect if not on a public path and not already redirecting
            if (!isPublicPath && currentPath !== '/login' && !sessionStorage.getItem('redirecting_to_login')) {
                sessionStorage.setItem('redirecting_to_login', 'true');
                setTimeout(() => {
                    sessionStorage.removeItem('redirecting_to_login');
                    // Only redirect if still on a protected path
                    const stillOnProtectedPath = !window.location.pathname.startsWith('/login') && 
                                                 !window.location.pathname.startsWith('/staff/clock-in') &&
                                                 !window.location.pathname.startsWith('/features') &&
                                                 !window.location.pathname.startsWith('/pricing') &&
                                                 !window.location.pathname.startsWith('/modules') &&
                                                 !window.location.pathname.startsWith('/about') &&
                                                 !window.location.pathname.startsWith('/contact');
                    if (stillOnProtectedPath) {
                        window.location.href = '/login?reason=session-expired';
                    }
                }, 100);
            }
        }
        return Promise.reject(error);
    }
);

export default api;

