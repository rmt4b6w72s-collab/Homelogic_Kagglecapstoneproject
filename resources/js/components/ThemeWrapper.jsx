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
                console.error('Failed to fetch user for theme:', err);
                return null;
            }
        },
        staleTime: 5 * 60 * 1000, // Cache for 5 minutes
        retry: 1,
    });

    // Get facility branding from user data
    const facilityBranding = userData?.facility_branding || null;

    return (
        <ThemeProvider facilityBranding={facilityBranding}>
            {children}
        </ThemeProvider>
    );
}

