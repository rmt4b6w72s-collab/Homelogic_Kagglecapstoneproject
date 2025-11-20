import { useState, useCallback } from 'react';

let toastIdCounter = 0;

export function useToast() {
    const [toasts, setToasts] = useState([]);

    const addToast = useCallback((toast) => {
        const id = ++toastIdCounter;
        const newToast = {
            id,
            ...toast,
        };

        setToasts((prev) => [...prev, newToast]);
        return id;
    }, []);

    const removeToast = useCallback((id) => {
        setToasts((prev) => prev.filter((toast) => toast.id !== id));
    }, []);

    const success = useCallback((title, message, options = {}) => {
        return addToast({ type: 'success', title, message, ...options });
    }, [addToast]);

    const error = useCallback((title, message, options = {}) => {
        return addToast({ type: 'error', title, message, ...options });
    }, [addToast]);

    const warning = useCallback((title, message, options = {}) => {
        return addToast({ type: 'warning', title, message, ...options });
    }, [addToast]);

    const info = useCallback((title, message, options = {}) => {
        return addToast({ type: 'info', title, message, ...options });
    }, [addToast]);

    const showToast = useCallback((message, type = 'success', options = {}) => {
        // Support both formats: showToast(message, type) and showToast(message, type, options)
        if (typeof type === 'string') {
            switch (type) {
                case 'success':
                    return success(message, '', options);
                case 'error':
                    return error(message, '', options);
                case 'warning':
                    return warning(message, '', options);
                case 'info':
                    return info(message, '', options);
                default:
                    return success(message, '', options);
            }
        } else {
            // If type is an object (options), treat message as title and type as options
            return success(message, '', type || {});
        }
    }, [success, error, warning, info]);

    return {
        toasts,
        addToast,
        removeToast,
        success,
        error,
        warning,
        info,
        showToast,
    };
}





