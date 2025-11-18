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

    return {
        toasts,
        addToast,
        removeToast,
        success,
        error,
        warning,
        info,
    };
}



