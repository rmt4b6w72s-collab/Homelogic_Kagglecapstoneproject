import React from 'react';
import {
    LayoutDashboard,
    Building2,
    DollarSign,
    ClipboardList,
    Settings,
    UserCheck,
} from 'lucide-react';
import SectionLayout from '../../components/SectionLayout';

const TABS = [
    { id: 'overview',       label: 'Overview',       icon: LayoutDashboard, path: '/management', exact: true },
    { id: 'pharmacy',       label: 'Pharmacy',       icon: Building2,       path: '/pharmacy' },
    { id: 'billing',        label: 'Billing',        icon: DollarSign,      path: '/billing' },
    {
        id: 'staff-site',
        label: 'Staff & site',
        icon: UserCheck,
        path: '/check-in-dashboard',
        extraPaths: ['/staff', '/visitors', '/residents/sign-out'],
    },
];

export default function ManagementSectionLayout() {
    return (
        <SectionLayout title="Management" tabs={TABS} />
    );
}
