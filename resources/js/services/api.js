import axios from 'axios';

const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    withCredentials: true,
});

// Add CSRF token to requests
api.interceptors.request.use((config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token;
    }
    
    // Add auth token if available
    const authToken = localStorage.getItem('auth_token');
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
            
            // Check if we're on a protected route (not a public path)
            // Protected routes are anything that's not in the public paths list above
            const isOnProtectedRoute = !isPublicPath;
            
            // Check if we have a token BEFORE clearing it
            const hasToken = localStorage.getItem('auth_token');
            
            // If we're on a protected route and have a token, don't redirect or clear token
            // This might be a temporary API issue (e.g., endpoint doesn't exist, permission issue)
            // Only clear token and redirect if we're on a protected route AND we don't have a token
            if (isPublicPath || (isOnProtectedRoute && hasToken)) {
                // Don't clear token or redirect - might be a temporary API issue
                return Promise.reject(error);
            }
            
            // Clear token only if we're going to redirect
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_name');
            localStorage.removeItem('user_role');
            
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
                        window.location.href = '/login';
                    }
                }, 100);
            }
        }
        return Promise.reject(error);
    }
);

export default api;

