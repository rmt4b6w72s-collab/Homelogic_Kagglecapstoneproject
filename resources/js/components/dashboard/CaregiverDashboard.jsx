import React from 'react';
import {
    Calendar, Clock, CheckCircle, AlertCircle,
    ChevronRight, Activity, Pill, User,
    MapPin, Phone, FileText, Sparkles
} from 'lucide-react';
import { useNavigate } from 'react-router-dom';

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
        <div className="space-y-8 animate-in fade-in duration-500">
            {/* Header Section */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900 tracking-tight">
                        {greeting}, <span className="text-[var(--theme-primary)]">{user?.first_name || 'Caregiver'}</span>
                    </h1>
                    <p className="text-gray-500 mt-1">Here's what's happening today at {user?.branch?.name || 'your facility'}.</p>
                </div>
                <div className="flex items-center gap-3">
                    <div className="bg-white px-4 py-2 rounded-xl shadow-sm border border-gray-100 flex items-center gap-2">
                        <div className="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                        <span className="text-sm font-medium text-gray-600">On Shift</span>
                    </div>
                    <button
                        onClick={() => navigate('/appointments')}
                        className="bg-[var(--theme-primary)] hover:bg-[var(--theme-primary-dark)] text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors shadow-lg shadow-[var(--theme-primary)]/20"
                    >
                        View Calendar
                    </button>
                </div>
            </div>

            {/* Quick Stats Row */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    title="My Residents"
                    value={stats?.assigned_residents || 0}
                    icon={User}
                    color="blue"
                    onClick={() => navigate('/administration/residents')}
                />
                <StatCard
                    title="Appointments"
                    value={stats?.todays_appointments || 0}
                    icon={Calendar}
                    color="purple"
                    onClick={() => navigate('/appointments')}
                />
                <StatCard
                    title="Medications Due"
                    value={stats?.medication_reminders?.length || 0}
                    icon={Pill}
                    color="green"
                    onClick={() => navigate('/medications')}
                />
                <StatCard
                    title="Pending Tasks"
                    value={stats?.pending_assessments || 0}
                    icon={FileText}
                    color="orange"
                    onClick={() => navigate('/assessments')}
                />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Column: Today's Timeline */}
                <div className="lg:col-span-2 space-y-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                            <Clock className="w-5 h-5 text-[var(--theme-primary)]" />
                            Today's Schedule
                        </h2>
                        <span className="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                            {new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' })}
                        </span>
                    </div>

                    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 min-h-[400px]">
                        {todaysSchedule.length > 0 ? (
                            <div className="space-y-0">
                                {todaysSchedule.map((item, index) => {
                                    const status = getScheduleStatus(item.time_24h);
                                    const isLast = index === todaysSchedule.length - 1;

                                    return (
                                        <div key={item.id} className="relative pl-8 pb-8 group">
                                            {/* Timeline Line */}
                                            {!isLast && (
                                                <div className="absolute left-[11px] top-8 bottom-0 w-0.5 bg-gray-100 group-hover:bg-gray-200 transition-colors"></div>
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
                                            <div className={`relative p-4 rounded-xl border transition-all duration-200 hover:shadow-md
                                                ${status === 'current' ? 'bg-[var(--theme-primary-bg-light)] border-[var(--theme-primary)]/20' :
                                                    status === 'past' ? 'bg-gray-50 border-gray-100 opacity-75' : 'bg-white border-gray-100'}`}
                                            >
                                                <div className="flex items-start justify-between gap-4">
                                                    <div>
                                                        <div className="flex items-center gap-2 mb-1">
                                                            <span className={`text-sm font-bold ${status === 'current' ? 'text-[var(--theme-primary)]' : 'text-gray-900'}`}>
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
                                                        <button
                                                            onClick={() => navigate(item.link)}
                                                            className="p-2 hover:bg-white rounded-lg text-gray-400 hover:text-[var(--theme-primary)] transition-colors"
                                                        >
                                                            <ChevronRight className="w-5 h-5" />
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="flex flex-col items-center justify-center h-full py-12 text-center text-gray-500">
                                <div className="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <Sparkles className="w-8 h-8 text-gray-300" />
                                </div>
                                <h3 className="text-lg font-medium text-gray-900">All Clear!</h3>
                                <p className="text-sm max-w-xs mx-auto mt-1">No scheduled tasks or appointments remaining for today.</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Right Column: Upcoming & Quick Actions */}
                <div className="space-y-8">
                    {/* Upcoming Events */}
                    <div>
                        <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2 mb-6">
                            <Calendar className="w-5 h-5 text-[var(--theme-primary)]" />
                            Upcoming Events
                        </h2>
                        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            {upcomingEvents.length > 0 ? (
                                <div className="divide-y divide-gray-100">
                                    {upcomingEvents.map((event) => (
                                        <div key={event.id} className="p-4 hover:bg-gray-50 transition-colors">
                                            <div className="flex gap-3">
                                                <div className={`flex-shrink-0 w-12 h-12 rounded-xl flex flex-col items-center justify-center
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
                                    <button
                                        onClick={() => navigate('/events')}
                                        className="w-full py-3 text-sm text-center text-gray-500 hover:text-[var(--theme-primary)] font-medium transition-colors"
                                    >
                                        View All Events
                                    </button>
                                </div>
                            ) : (
                                <div className="p-8 text-center text-gray-500">
                                    <p className="text-sm">No upcoming events scheduled.</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Quick Actions */}
                    <div>
                        <h2 className="text-xl font-bold text-gray-900 flex items-center gap-2 mb-6">
                            <Activity className="w-5 h-5 text-[var(--theme-primary)]" />
                            Quick Actions
                        </h2>
                        <div className="grid grid-cols-2 gap-3">
                            <QuickAction
                                label="Record Vitals"
                                icon={Activity}
                                onClick={() => navigate('/vitals')}
                                color="bg-rose-600 text-white hover:bg-rose-700 shadow-md shadow-rose-100"
                            />
                            <QuickAction
                                label="New Incident"
                                icon={AlertCircle}
                                onClick={() => navigate('/incidents')}
                                color="bg-orange-500 text-white hover:bg-orange-600 shadow-md shadow-orange-100"
                            />
                            <QuickAction
                                label="Administer Meds"
                                icon={Pill}
                                onClick={() => navigate('/medications')}
                                color="bg-emerald-600 text-white hover:bg-emerald-700 shadow-md shadow-emerald-100"
                            />
                            <QuickAction
                                label="Daily Notes"
                                icon={FileText}
                                onClick={() => navigate('/t-logs')}
                                color="bg-blue-600 text-white hover:bg-blue-700 shadow-md shadow-blue-100"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function StatCard({ title, value, icon: Icon, color, onClick }) {
    const colors = {
        blue: 'bg-blue-50 text-blue-600',
        purple: 'bg-purple-50 text-purple-600',
        green: 'bg-green-50 text-green-600',
        orange: 'bg-orange-50 text-orange-600',
    };

    return (
        <div
            onClick={onClick}
            className="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 cursor-pointer hover:shadow-md transition-all duration-200 group"
        >
            <div className="flex items-center justify-between mb-3">
                <div className={`p-2.5 rounded-xl ${colors[color]} group-hover:scale-110 transition-transform duration-200`}>
                    <Icon className="w-5 h-5" />
                </div>
                <ChevronRight className="w-4 h-4 text-gray-300 group-hover:text-gray-500 transition-colors" />
            </div>
            <div>
                <p className="text-sm font-medium text-gray-500">{title}</p>
                <h3 className="text-2xl font-bold text-gray-900 mt-1">{value}</h3>
            </div>
        </div>
    );
}

function QuickAction({ label, icon: Icon, onClick, color }) {
    return (
        <button
            onClick={onClick}
            className={`${color} p-4 rounded-xl flex flex-col items-center justify-center gap-2 transition-all duration-200 hover:shadow-sm`}
        >
            <Icon className="w-6 h-6" />
            <span className="text-xs font-bold">{label}</span>
        </button>
    );
}
