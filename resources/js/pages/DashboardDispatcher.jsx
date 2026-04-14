import React, { Suspense, lazy } from 'react';
import { useQuery } from '@tanstack/react-query';
import { currentUserQueryOptions } from '../queries/currentUser';
import ModularGateway from './caregiver/ModularGateway';
import PageLoader from '../components/PageLoader';

const LegacyDashboard = lazy(() => import('./Dashboard'));

export default function DashboardDispatcher() {
    const { data: currentUser, isLoading, isError } = useQuery(currentUserQueryOptions);

    if (isLoading) return <PageLoader />;
    
    if (isError) {
        return (
            <div className="flex flex-col items-center justify-center min-h-screen bg-gray-50 p-6 text-center">
                <p className="text-red-600 font-bold mb-4 text-xl">Connection Error</p>
                <p className="text-gray-600 mb-6 max-w-md">We couldn't load your profile. Please check your internet connection and try again.</p>
                <button 
                    onClick={() => window.location.reload()}
                    className="bg-[var(--theme-primary)] text-white px-6 py-2 rounded-xl font-bold shadow-lg hover:bg-[var(--theme-primary-dark)] transition-colors"
                >
                    Reload Page
                </button>
            </div>
        );
    }

    const isCaregiver = currentUser?.is_caregiver || 
                        currentUser?.role === 'caregiver' || 
                        currentUser?.roles?.some(r => r.name === 'caregiver');

    // For the UI-UX overhaul, we want caregivers to use the new Modular Gateway by default.
    if (isCaregiver) {
        return <ModularGateway />;
    }

    return (
        <Suspense fallback={<PageLoader />}>
            <LegacyDashboard />
        </Suspense>
    );
}
