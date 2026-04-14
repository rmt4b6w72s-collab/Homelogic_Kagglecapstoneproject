import React from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { 
    Pill, 
    Sparkles, 
    ClipboardList, 
    Calendar, 
    ArrowRight,
    Users,
    Activity,
    Bell
} from 'lucide-react';
import { currentUserQueryOptions } from '../../queries/currentUser';
import api from '../../services/api';

export default function ModularGateway() {
    const navigate = useNavigate();
    const { data: currentUser } = useQuery(currentUserQueryOptions);

    const { data: stats } = useQuery({
        queryKey: ['dashboard-stats'],
        queryFn: async () => {
            const response = await api.get('/dashboard/stats');
            return response.data?.data || response.data;
        }
    });

    const HUBS = [
        {
            id: 'medication',
            title: 'Medication Hub',
            description: 'Administration, deliveries, and clinical history.',
            icon: Pill,
            color: 'text-emerald-600',
            bgColor: 'bg-emerald-50',
            borderColor: 'border-emerald-100',
            path: '/medication-hub',
            stats: stats?.medication_reminders?.length ? `${stats.medication_reminders.length} Due Soon` : 'Up to Date'
        },
        {
            id: 'clinical',
            title: 'Clinical Hub',
            description: 'Resident vitals, T-Logs, and health monitoring.',
            icon: Activity,
            color: 'text-red-600',
            bgColor: 'bg-red-50',
            borderColor: 'border-red-100',
            path: '/clinical',
            stats: stats?.today_vitals ? `${stats.today_vitals} Recorded Today` : 'Record Vitals'
        },
        {
            id: 'operations',
            title: 'Operations Hub',
            description: 'Housekeeping, supplies, and facility compliance.',
            icon: Sparkles,
            color: 'text-amber-600',
            bgColor: 'bg-amber-50',
            borderColor: 'border-amber-100',
            path: '/operations',
            stats: stats?.low_inventory_count ? `${stats.low_inventory_count} Low Stock Items` : 'Facility Ready'
        },
        {
            id: 'assessments',
            title: 'Assessment Hub',
            description: 'Pending evaluations and resident reviews.',
            icon: ClipboardList,
            color: 'text-teal-600',
            bgColor: 'bg-teal-50',
            borderColor: 'border-teal-100',
            path: '/assessment-hub',
            stats: stats?.pending_assessments ? `${stats.pending_assessments} Pending` : 'All Caught Up'
        },
        {
            id: 'appointments',
            title: 'Appointment Hub',
            description: 'Scheduling, visit tracking, and care reminders.',
            icon: Calendar,
            color: 'text-blue-600',
            bgColor: 'bg-blue-50',
            borderColor: 'border-blue-100',
            path: '/appointment-hub',
            stats: stats?.todays_appointments ? `${stats.todays_appointments} Today` : 'View Schedule'
        },
        {
            id: 'management',
            title: 'Management',
            description: 'Staff clocking, billing, and system reports.',
            icon: Users,
            color: 'text-purple-600',
            bgColor: 'bg-purple-50',
            borderColor: 'border-purple-100',
            path: '/reports',
            stats: 'Personnel & Billing'
        }
    ];

    return (
        <div className="max-w-7xl mx-auto space-y-8">
            <header className="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 className="text-3xl font-extrabold text-gray-900 tracking-tight">
                        Hello, {currentUser?.first_name || 'Caregiver'}
                    </h1>
                    <p className="text-gray-500 mt-1">Welcome to your operational command center.</p>
                </div>
                <div className="flex items-center gap-3 glass-card px-4 py-2 rounded-2xl shadow-sm border border-white/50">
                    <div className="w-2 h-2 rounded-full bg-green-500 animate-pulse" />
                    <span className="text-sm font-bold text-gray-700">System Live</span>
                </div>
            </header>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {HUBS.map((hub) => (
                    <button
                        key={hub.id}
                        onClick={() => navigate(hub.path)}
                        className={`group relative bg-white/80 backdrop-blur-md rounded-3xl p-6 border-2 ${hub.borderColor} shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-left overflow-hidden glass-card`}
                    >
                        {/* Decorative Background Icon */}
                        <hub.icon className={`absolute -right-4 -bottom-4 w-32 h-32 ${hub.color} opacity-[0.03] group-hover:scale-110 transition-transform duration-500`} />
                        
                        <div className="relative z-10 space-y-4">
                            <div className={`w-14 h-14 ${hub.bgColor} ${hub.color} rounded-2xl flex items-center justify-center shadow-inner soft-glow-${hub.id === 'medication' ? 'emerald' : hub.id === 'appointments' ? 'blue' : hub.id === 'operations' ? 'amber' : 'emerald'}`}>
                                <hub.icon className="w-7 h-7" strokeWidth={2.5} />
                            </div>
                            
                            <div>
                                <h2 className="text-xl font-bold text-gray-900 group-hover:text-[var(--theme-primary)] transition-colors">
                                    {hub.title}
                                </h2>
                                <p className="text-sm text-gray-500 mt-1 leading-relaxed">
                                    {hub.description}
                                </p>
                            </div>

                            <div className="flex items-center justify-between pt-2">
                                <span className={`text-xs font-extrabold uppercase tracking-widest ${hub.color} px-3 py-1 rounded-full ${hub.bgColor} border border-current opacity-80`}>
                                    {hub.stats}
                                </span>
                                <div className={`w-8 h-8 rounded-full ${hub.bgColor} ${hub.color} flex items-center justify-center group-hover:translate-x-1 transition-transform`}>
                                    <ArrowRight className="w-4 h-4" />
                                </div>
                            </div>
                        </div>
                    </button>
                ))}
            </div>

            {/* Quick Alert Strip */}
            {stats?.pending_assessments > 0 && (
                <div className="bg-amber-50 border-2 border-amber-100 rounded-2xl p-4 flex items-center justify-between shadow-sm">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center">
                            <Bell className="w-5 h-5" />
                        </div>
                        <div>
                            <p className="text-sm font-bold text-amber-900">Action Required</p>
                            <p className="text-xs text-amber-700">You have {stats.pending_assessments} pending assessments requiring your review.</p>
                        </div>
                    </div>
                    <button 
                        onClick={() => navigate('/assessment-hub')}
                        className="text-amber-600 hover:text-amber-700 text-sm font-bold flex items-center gap-1"
                    >
                        Resolve Now <ArrowRight className="w-4 h-4" />
                    </button>
                </div>
            )}
        </div>
    );
}
