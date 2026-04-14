import React from 'react';
import { 
    Pill, 
    History, 
    Truck, 
    CalendarCheck, 
    BarChart3,
    AlertCircle
} from 'lucide-react';
import SectionHub from '../../components/SectionHub';

const FEATURES = [
    {
        id: 'medication-admin',
        title: 'Medication Administration',
        description: 'Record and track medication doses for residents in real-time.',
        icon: Pill,
        accent: 'bg-emerald-50 text-emerald-600 border-emerald-100',
        iconBg: 'bg-emerald-100',
        path: '/medications',
        subLinks: [
            { label: 'Administer Meds', path: '/medications' },
            { label: 'Residents List', path: '/medications/residents' },
        ],
    },
    {
        id: 'medication-history',
        title: 'Administration History',
        description: 'Review past medication records, missed doses, and refusals.',
        icon: History,
        accent: 'bg-blue-50 text-blue-600 border-blue-100',
        iconBg: 'bg-blue-100',
        path: '/medication-history',
    },
    {
        id: 'medication-deliveries',
        title: 'Deliveries & Verification',
        description: 'Manage incoming pharmacy deliveries and verify stock levels.',
        icon: Truck,
        accent: 'bg-purple-50 text-purple-600 border-purple-100',
        iconBg: 'bg-purple-100',
        path: '/medication-deliveries',
    },
    {
        id: 'medication-reports',
        title: 'Clinical Reports',
        description: 'Generate adherence reports and medication-specific analytics.',
        icon: BarChart3,
        accent: 'bg-indigo-50 text-indigo-600 border-indigo-100',
        iconBg: 'bg-indigo-100',
        path: '/medications/report',
    },
];

export default function MedicationHubPage() {
    return (
        <SectionHub
            title="Medication Hub"
            subtitle="Centralized medication management and administration"
            features={FEATURES}
        />
    );
}
