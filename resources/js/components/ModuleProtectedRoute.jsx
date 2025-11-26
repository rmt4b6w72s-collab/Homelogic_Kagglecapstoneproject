import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../services/api';
import { hasModuleAccess } from '../utils/moduleAccess';
import { MODULE_MAP } from '../utils/moduleAccess';

export default function ModuleProtectedRoute({ children, module }) {
    const location = useLocation();
    const { data: currentUser, isLoading } = useQuery({
        queryKey: ['current-user'],
        queryFn: async () => {
            try {
                const response = await api.get('/user');
                return response.data;
            } catch {
                return null;
            }
        },
        staleTime: 0, // Always fetch fresh data
        retry: 1,
    });

    if (isLoading) {
        return (
            <div className="flex items-center justify-center min-h-screen">
                <div className="text-center">
                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--theme-primary)]"></div>
                    <p className="mt-4 text-gray-600">Loading...</p>
                </div>
            </div>
        );
    }

    // Super admins have access to everything
    if (currentUser?.role === 'super_admin') {
        return children;
    }

    // If no module specified, allow access (for routes that don't require a module)
    if (!module) {
        return children;
    }

    // Check module access
    const enabledModules = currentUser?.enabled_modules || [];
    const hasAccess = hasModuleAccess(location.pathname, enabledModules, false) || 
                      enabledModules.includes(module);

    if (!hasAccess) {
        return (
            <Navigate 
                to="/dashboard" 
                replace 
                state={{ 
                    message: `The ${module} module is not available for your facility.`,
                    from: location.pathname 
                }} 
            />
        );
    }

    return children;
}

