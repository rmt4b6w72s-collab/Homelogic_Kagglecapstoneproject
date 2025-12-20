import React from 'react';
import {
    Calendar, Clock, CheckCircle, AlertCircle,
    ChevronRight, Activity, Pill, User,
    MapPin, Phone, FileText, Sparkles, Heart, ClipboardList
} from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import SectionCard from '../SectionCard';

export default function CaregiverDashboard({
    user,
    stats,
    todaysSchedule = [],
    upcomingEvents = []
}) {
    const navigate = useNavigate();
    const currentHour = new Date().getHours();
    const greeting = currentHour < 12 ? 'Good Morning' : currentHour < 18 ? 'Good Afternoon' : 'Good Evening';

    // Group schedule by time status (Past, Current, Upcoming)
    const getScheduleStatus = (timeStr) => {
        if (!timeStr) return 'upcoming';
        const now = new Date();
        const [hours, minutes] = timeStr.split(':').map(Number);
        const scheduleTime = new Date();
        scheduleTime.setHours(hours, minutes, 0);

        const diff = (scheduleTime - now) / (1000 * 60); // diff in minutes

        if (diff < -30) return 'past'; // More than 30 mins ago
        if (diff >= -30 && diff <= 30) return 'current'; // Within 30 mins window
        return 'upcoming';
    };

    return (
        <div className="space-y-6">
            {/* Header Section */}
            <div className="bg-gradient-to-br from-[var(--theme-primary)] to-[var(--theme-primary-dark)] rounded-xl shadow-sm p-6 text-white">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold mb-1">
                            {greeting}, {user?.first_name || 'Caregiver'} 👋
                        </h1>
                        <p className="text-white/90 text-sm">
                            Welcome to your Care Dashboard • {user?.branch?.name || 'Your Facility'}
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="bg-white/20 backdrop-blur-sm px-4 py-2 rounded-lg flex items-center gap-2 border border-white/30">
                            <div className="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
                            <span className="text-sm font-medium">On Shift</span>
                        </div>
                        <button
                            onClick={() => navigate('/appointments')}
                            className="bg-white text-[var(--theme-primary)] px-4 py-2 rounded-lg text-sm font-semibold transition-colors hover:bg-white/90 shadow-md"
                        >
                            View Calendar
                        </button>
                    </div>
                </div>
            </div>

            {/* Quick Stats Row */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    title="My Residents"
                    value={stats?.assigned_residents || 0}
                    icon={User}
                    onClick={() => navigate('/administration/residents')}
                />
                <StatCard
                    title="Appointments"
                    value={stats?.todays_appointments || 0}
                    icon={Calendar}
                    onClick={() => navigate('/appointments')}
                />
                <StatCard
                    title="Medications Due"
                    value={stats?.medication_reminders?.length || 0}
                    icon={Pill}
                    onClick={() => navigate('/medications')}
                />
                <StatCard
                    title="Pending Tasks"
                    value={stats?.pending_assessments || 0}
                    icon={ClipboardList}
                    onClick={() => navigate('/assessments')}
                />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Left Column: Today's Schedule */}
                <div className="lg:col-span-2">
                    <SectionCard
                        title="Today's Schedule"
                        headerRight={
                            <span className="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                                {new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' })}
                            </span>
                        }
                    >
                        {todaysSchedule.length > 0 ? (
                            <div className="space-y-0">
                                {todaysSchedule.map((item, index) => {
                                    const status = getScheduleStatus(item.time_24h);
                                    const isLast = index === todaysSchedule.length - 1;

                                    return (
                                        <div key={item.id} className="relative pl-8 pb-6 group">
                                            {/* Timeline Line */}
                                            {!isLast && (
                                                <div className="absolute left-[11px] top-8 bottom-0 w-0.5 bg-gray-200 group-hover:bg-gray-300 transition-colors"></div>
                                            )}

                                            {/* Timeline Dot */}
                                            <div className={`absolute left-0 top-1.5 w-6 h-6 rounded-full border-2 flex items-center justify-center z-10 bg-white
                                                ${status === 'current' ? 'border-[var(--theme-primary)] shadow-[0_0_0_4px_rgba(var(--theme-primary-rgb),0.2)]' :
                                                    status === 'past' ? 'border-gray-300 bg-gray-50' : 'border-[var(--theme-primary)]'}`}
                                            >
                                                {status === 'past' ? (
                                                    <div className="w-2.5 h-2.5 rounded-full bg-gray-300" />
                                                ) : (
                                                    <div className={`w-2.5 h-2.5 rounded-full ${status === 'current' ? 'bg-[var(--theme-primary)] animate-pulse' : 'bg-[var(--theme-primary)]'}`} />
                                                )}
                                            </div>

                                            {/* Content Card */}
                                            <div className={`relative p-4 rounded-lg border transition-all duration-200 hover:shadow-md cursor-pointer
                                                ${status === 'current' ? 'bg-[var(--theme-primary-bg-light)] border-[var(--theme-primary)]/20' :
                                                    status === 'past' ? 'bg-gray-50 border-gray-200 opacity-75' : 'bg-white border-gray-200 hover:border-[var(--theme-primary)]/30'}`}
                                                onClick={() => item.link && navigate(item.link)}
                                            >
                                                <div className="flex items-start justify-between gap-4">
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-2 mb-1">
                                                            <span className={`text-sm font-semibold ${status === 'current' ? 'text-[var(--theme-primary)]' : 'text-gray-900'}`}>
                                                                {item.time}
                                                            </span>
                                                            <span className={`px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider
                                                                ${item.type === 'medication' ? 'bg-green-100 text-green-700' :
                                                                    item.type === 'appointment' ? 'bg-blue-100 text-blue-700' :
                                                                        item.type === 'vitals' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700'}`}
                                                            >
                                                                {item.category || item.type}
                                                            </span>
                                                        </div>
                                                        <h3 className="font-semibold text-gray-900">{item.title}</h3>
                                                        <p className="text-sm text-gray-600 mt-0.5 flex items-center gap-1.5">
                                                            <User className="w-3.5 h-3.5" />
                                                            {item.resident_name}
                                                        </p>
                                                        {item.location && (
                                                            <p className="text-xs text-gray-500 mt-1 flex items-center gap-1.5">
                                                                <MapPin className="w-3 h-3" />
                                                                {item.location}
                                                            </p>
                                                        )}
                                                    </div>

                                                    {item.link && (
                                                        <ChevronRight className="w-5 h-5 text-gray-400 group-hover:text-[var(--theme-primary)] transition-colors flex-shrink-0" />
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="flex flex-col items-center justify-center py-12 text-center text-gray-500">
                                <div className="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <Sparkles className="w-8 h-8 text-gray-300" />
                                </div>
                                <h3 className="text-lg font-medium text-gray-900">All Clear!</h3>
                                <p className="text-sm max-w-xs mx-auto mt-1">No scheduled tasks or appointments remaining for today.</p>
                            </div>
                        )}
                    </SectionCard>
                </div>

                {/* Right Column: Upcoming & Quick Actions */}
                <div className="space-y-6">
                    {/* Upcoming Events */}
                    <SectionCard
                        title="Upcoming Events"
                        actionLabel="View All"
                        onAction={() => navigate('/events')}
                    >
                        {upcomingEvents.length > 0 ? (
                            <div className="divide-y divide-gray-200">
                                {upcomingEvents.slice(0, 5).map((event) => (
                                    <div key={event.id} className="p-3 hover:bg-gray-50 transition-colors rounded-lg cursor-pointer" onClick={() => event.link && navigate(event.link)}>
                                        <div className="flex gap-3">
                                            <div className={`flex-shrink-0 w-12 h-12 rounded-lg flex flex-col items-center justify-center
                                                ${event.color === 'orange' ? 'bg-orange-50 text-orange-600' :
                                                    event.color === 'blue' ? 'bg-blue-50 text-blue-600' : 'bg-gray-50 text-gray-600'}`}
                                            >
                                                <span className="text-xs font-bold uppercase">{new Date(event.date).toLocaleDateString(undefined, { month: 'short' })}</span>
                                                <span className="text-lg font-bold leading-none">{new Date(event.date).getDate()}</span>
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <h4 className="text-sm font-semibold text-gray-900 truncate">{event.title}</h4>
                                                <p className="text-xs text-gray-500 mt-0.5 line-clamp-1">{event.description}</p>
                                                <div className="flex items-center gap-2 mt-1.5">
                                                    {event.time && (
                                                        <span className="text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-medium">
                                                            {event.time}
                                                        </span>
                                                    )}
                                                    <span className="text-[10px] text-gray-400">{event.branch}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="py-8 text-center text-gray-500">
                                <Calendar className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                                <p className="text-sm">No upcoming events scheduled.</p>
                            </div>
                        )}
                    </SectionCard>

                    {/* Quick Actions */}
                    <SectionCard title="Quick Actions">
                        <div className="grid grid-cols-2 gap-3">
                            <QuickAction
                                label="Record Vitals"
                                icon={Heart}
                                onClick={() => navigate('/vitals')}
                            />
                            <QuickAction
                                label="New Incident"
                                icon={AlertCircle}
                                onClick={() => navigate('/incidents')}
                            />
                            <QuickAction
                                label="Administer Meds"
                                icon={Pill}
                                onClick={() => navigate('/medications')}
                            />
                            <QuickAction
                                label="Daily Notes"
                                icon={FileText}
                                onClick={() => navigate('/t-logs')}
                            />
                        </div>
                    </SectionCard>
                </div>
            </div>
        </div>
    );
}

function StatCard({ title, value, icon: Icon, onClick }) {
    return (
        <div
            onClick={onClick}
            className="bg-white rounded-xl shadow-sm border border-gray-200 p-5 cursor-pointer hover:shadow-md hover:border-[var(--theme-primary)]/30 transition-all duration-200 group"
        >
            <div className="flex items-center justify-between mb-3">
                <div className="p-2.5 rounded-lg bg-[var(--theme-primary-bg)] text-[var(--theme-primary)] group-hover:scale-110 transition-transform duration-200">
                    <Icon className="w-5 h-5" />
                </div>
                <ChevronRight className="w-4 h-4 text-gray-300 group-hover:text-[var(--theme-primary)] transition-colors" />
            </div>
            <div>
                <p className="text-sm font-medium text-gray-500">{title}</p>
                <h3 className="text-2xl font-bold text-gray-900 mt-1">{value}</h3>
            </div>
        </div>
    );
}

function QuickAction({ label, icon: Icon, onClick }) {
    return (
        <button
            onClick={onClick}
            className="bg-[var(--theme-primary)] hover:bg-[var(--theme-primary-hover)] text-[var(--theme-text-on-primary)] p-4 rounded-lg flex flex-col items-center justify-center gap-2 transition-all duration-200 hover:shadow-md shadow-sm"
        >
            <Icon className="w-5 h-5" />
            <span className="text-xs font-semibold">{label}</span>
        </button>
    );
}
