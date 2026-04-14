import React from 'react';
import { Link } from 'react-router-dom';
import { ClipboardList, Heart, Moon, ArrowRight } from 'lucide-react';

const TILES = [
    {
        id: 'medication-history',
        title: 'Medication History',
        description: 'Review past medication administrations and reconciliation records for all residents.',
        icon: ClipboardList,
        path: '/medication-history',
        accent: 'text-violet-600',
        bg: 'bg-violet-50',
    },
    {
        id: 'vitals',
        title: 'Vital Signs',
        description: 'Log and track resident blood pressure, heart rate, temperature, oxygen saturation and weight.',
        icon: Heart,
        path: '/vitals',
        accent: 'text-rose-500',
        bg: 'bg-rose-50',
    },
    {
        id: 'sleep',
        title: 'Sleep Tracking',
        description: 'Record nightly sleep quality and duration patterns to support resident wellbeing plans.',
        icon: Moon,
        path: '/sleep',
        accent: 'text-indigo-500',
        bg: 'bg-indigo-50',
    },
];

export default function ClinicalHubPage() {
    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            {TILES.map(tile => {
                const Icon = tile.icon;
                return (
                    <Link
                        key={tile.id}
                        to={tile.path}
                        className="group bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md hover:border-gray-200 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--theme-primary)]"
                    >
                        <div className="flex items-start justify-between">
                            <div className={`w-11 h-11 rounded-xl flex items-center justify-center ${tile.bg}`}>
                                <Icon className={`w-5 h-5 ${tile.accent}`} aria-hidden="true" />
                            </div>
                            <ArrowRight
                                className="w-4 h-4 text-gray-300 group-hover:text-[var(--theme-primary)] group-hover:translate-x-0.5 transition-all mt-1"
                                aria-hidden="true"
                            />
                        </div>
                        <div>
                            <h2 className="text-base font-bold text-gray-900 group-hover:text-[var(--theme-primary)] transition-colors leading-tight">
                                {tile.title}
                            </h2>
                            <p className="mt-1 text-sm text-gray-500 leading-snug">
                                {tile.description}
                            </p>
                        </div>
                    </Link>
                );
            })}
        </div>
    );
}
