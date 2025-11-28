import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../services/api';
import { ThemeProvider } from '../contexts/ThemeContext';

/**
 * Wrapper component that fetches user data and provides theme
 * This ensures theme is available at the root level
 */
export default function ThemeWrapper({ children }) {
    const { data: userData } = useQuery({
        queryKey: ['current-user'],
        queryFn: async () => {
            try {
                const response = await api.get('/user');
                return response.data;
            } catch (err) {
                // Don't log 401 errors - they're expected when not logged in
                if (err.response?.status !== 401) {
                    console.error('Failed to fetch user for theme:', err);
                }
                return null;
            }
        },
        staleTime: 5 * 60 * 1000, // Cache for 5 minutes
        retry: false, // Don't retry on 401 errors
    });

    // Fetch super admin theme if user is super admin
    const isSuperAdmin = userData?.role === 'super_admin';
    const { data: superAdminTheme } = useQuery({
        queryKey: ['super-admin-theme'],
        queryFn: async () => {
            try {
                const response = await api.get('/system-settings/super-admin-theme');
                return response.data.data;
            } catch (err) {
                console.error('Failed to fetch super admin theme:', err);
                return null;
            }
        },
        enabled: isSuperAdmin, // Only fetch if user is super admin
        staleTime: 5 * 60 * 1000, // Cache for 5 minutes
        retry: 1,
    });

    // Determine facility branding
    // If super admin, use super admin theme colors, otherwise use facility branding
    const facilityBranding = React.useMemo(() => {
        if (isSuperAdmin && superAdminTheme) {
            // For super admin, use super admin theme colors but keep facility branding structure
            return {
                ...userData?.facility_branding,
                primary_color: superAdminTheme.primary_color,
                secondary_color: superAdminTheme.secondary_color,
                accent_color: superAdminTheme.accent_color,
            };
        }
        return userData?.facility_branding || null;
    }, [userData, isSuperAdmin, superAdminTheme]);

    return (
        <ThemeProvider facilityBranding={facilityBranding}>
            {children}
        </ThemeProvider>
    );
}

