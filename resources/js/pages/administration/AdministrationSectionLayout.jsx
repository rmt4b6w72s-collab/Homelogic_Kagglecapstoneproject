import React from 'react';
import {
    LayoutDashboard,
    Users,
    Building2,
    Pill,
    History,
    Contact,
    ShieldCheck,
    Settings,
    ClipboardList,
} from 'lucide-react';
import SectionLayout from '../../components/SectionLayout';

const TABS = [
    { id: 'overview',       label: 'Overview',       icon: LayoutDashboard, path: '/administration', exact: true },
    { id: 'residents',      label: 'Residents',      icon: Users,           path: '/administration/residents' },
    { id: 'branches',       label: 'Branches',       icon: Building2,        path: '/administration/branches' },
    { id: 'drugs',          label: 'Drugs',          icon: Pill,            path: '/administration/drugs' },
    { id: 'users',          label: 'Staff',          icon: Contact,         path: '/administration/users' },
    { id: 'audit',          label: 'Audit Log',      icon: History,         path: '/administration/activity-logs' },
    { id: 'charts',         label: 'Behavior Config', icon: ClipboardList,   path: '/administration/behavior-charts' },
    { id: 'roles',          label: 'Roles & Perms',  icon: ShieldCheck,     path: '/administration/roles', extraPaths: ['/administration/facility-permissions'] },
];

export default function AdministrationSectionLayout() {
    return (
        <SectionLayout title="Administration" tabs={TABS} />
    );
}
