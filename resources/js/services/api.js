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
    
    return config;
});

// Handle 401 responses (unauthorized)
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Clear token and redirect to login
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_name');
            if (window.location.pathname !== '/app/login') {
                window.location.href = '/app/login';
            }
        }
        return Promise.reject(error);
    }
);

export default api;

