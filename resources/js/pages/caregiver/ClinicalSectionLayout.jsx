import React from 'react';
import { ClipboardList, Heart, Moon, LayoutDashboard } from 'lucide-react';
import SectionLayout from '../../components/SectionLayout';

const TABS = [
    { id: 'overview',            label: 'Overview',            icon: LayoutDashboard, path: '/clinical'           },
    { id: 'medication-history',  label: 'Medication History',  icon: ClipboardList,   path: '/medication-history' },
    { id: 'vitals',              label: 'Vitals',              icon: Heart,           path: '/vitals'             },
    { id: 'sleep',               label: 'Sleep',               icon: Moon,            path: '/sleep'              },
];

export default function ClinicalSectionLayout() {
    return (
        <SectionLayout
            title="Clinical"
            subtitle="Health monitoring and clinical records"
            tabs={TABS}
        />
    );
}
