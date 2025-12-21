import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, Link } from 'react-router-dom';
import api from '../services/api';
import { 
    Calendar, 
    CheckCircle, 
    XCircle, 
    Clock, 
    TrendingUp,
    Plus,
    Filter,
    Search,
    User,
    MapPin,
    Stethoscope,
    ChevronRight,
    FileText
} from 'lucide-react';
import Card from '../components/Card';
import SectionCard from '../components/SectionCard';

export default function AppointmentsDashboard() {
    const queryClient = useQueryClient();
    const navigate = useNavigate();
    const [statusFilter, setStatusFilter] = useState('all');
    const [dateFilter, setDateFilter] = useState('upcoming');
    const [search, setSearch] = useState('');

    // Fetch current user
    const { data: currentUser } = useQuery({
        queryKey: ['current-user'],
        queryFn: async () => {
            const response = await api.get('/user');
            return response.data;
        },
    });

    // Check if user is admin
    const isAdmin = React.useMemo(() => {
        if (!currentUser) return false;
        const role = currentUser.role?.toLowerCase().trim() || '';
        return role === 'administrator' || role === 'admin' || role === 'super_admin';
    }, [currentUser]);

    // Fetch statistics
    const { data: statistics, isLoading: statsLoading } = useQuery({
        queryKey: ['appointments-statistics'],
        queryFn: async () => {
            const response = await api.get('/appointments/statistics');
            return response.data;
        },
    });

    // Fetch appointments based on filters
    const { data: appointmentsData, isLoading: appointmentsLoading, refetch } = useQuery({
        queryKey: ['appointments-dashboard', statusFilter, dateFilter, search],
        queryFn: async () => {
            const params = {
                per_page: 50,
            };
            
            if (statusFilter !== 'all') {
                params.status = statusFilter;
            }
            
            if (dateFilter === 'upcoming') {
                params.date_filter = 'upcoming';
            } else if (dateFilter === 'past') {
                params.date_filter = 'past';
            } else if (dateFilter === 'today') {
                params.date_filter = 'today';
            }
            
            if (search) {
                params.search = search;
            }
            
            const response = await api.get('/appointments', { params });
            return response.data;
        },
    });

    // Mark appointment as complete mutation
    const completeMutation = useMutation({
        mutationFn: async ({ id, notes }) => {
            const formData = new FormData();
            formData.append('status', 'completed');
            if (notes) {
                formData.append('notes', notes);
            }
            return await api.patch(`/appointments/${id}/status`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['appointments-dashboard']);
            queryClient.invalidateQueries(['appointments-statistics']);
        },
    });

    const handleQuickComplete = async (appointmentId) => {
        if (window.confirm('Mark this appointment as completed?')) {
            await completeMutation.mutateAsync({ id: appointmentId });
        }
    };

    const appointments = appointmentsData?.data || [];
    const stats = statistics || {
        today: 0,
        upcoming: 0,
        completed: 0,
        cancelled: 0,
        total: 0,
        this_week: 0,
        this_month: 0,
    };

    const formatTime = (timeString) => {
        if (!timeString) return '';
        try {
            const [hours, minutes] = timeString.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
        } catch {
            return timeString;
        }
    };

    const formatDate = (dateString) => {
        if (!dateString) return '';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                weekday: 'short',
                month: 'short', 
                day: 'numeric',
                year: 'numeric'
            });
        } catch {
            return dateString;
        }
    };

    const getStatusBadge = (status) => {
        const badges = {
            scheduled: 'bg-blue-100 text-blue-800 border-blue-300',
            completed: 'bg-green-100 text-green-800 border-green-300',
            cancelled: 'bg-red-100 text-red-800 border-red-300',
            confirmed: 'bg-purple-100 text-purple-800 border-purple-300',
            in_progress: 'bg-yellow-100 text-yellow-800 border-yellow-300',
        };
        return badges[status] || 'bg-gray-100 text-gray-800 border-gray-300';
    };

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Appointments Dashboard</h1>
                    <p className="text-gray-600 mt-1">Overview and management of all appointments</p>
                </div>
                <div className="flex items-center gap-3">
                    <Link
                        to="/appointments"
                        className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2"
                    >
                        <Filter className="w-4 h-4" />
                        View All
                    </Link>
                    <button
                        onClick={() => navigate('/appointments?action=create')}
                        className="px-4 py-2 bg-[var(--theme-primary)] text-white rounded-lg hover:bg-[var(--theme-primary-hover)] transition-colors flex items-center gap-2"
                    >
                        <Plus className="w-5 h-5" />
                        Add Appointment
                    </button>
                </div>
            </div>

            {/* Statistics Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card className="p-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-gray-600">Today</p>
                            <p className="text-2xl font-bold text-gray-900 mt-1">{stats.today}</p>
                        </div>
                        <div className="p-3 bg-blue-100 rounded-lg">
                            <Calendar className="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                </Card>

                <Card className="p-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-gray-600">Upcoming</p>
                            <p className="text-2xl font-bold text-gray-900 mt-1">{stats.upcoming}</p>
                        </div>
                        <div className="p-3 bg-green-100 rounded-lg">
                            <Clock className="w-6 h-6 text-green-600" />
                        </div>
                    </div>
                </Card>

                <Card className="p-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-gray-600">Completed</p>
                            <p className="text-2xl font-bold text-gray-900 mt-1">{stats.completed}</p>
                        </div>
                        <div className="p-3 bg-purple-100 rounded-lg">
                            <CheckCircle className="w-6 h-6 text-purple-600" />
                        </div>
                    </div>
                </Card>

                <Card className="p-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-gray-600">This Month</p>
                            <p className="text-2xl font-bold text-gray-900 mt-1">{stats.this_month}</p>
                        </div>
                        <div className="p-3 bg-orange-100 rounded-lg">
                            <TrendingUp className="w-6 h-6 text-orange-600" />
                        </div>
                    </div>
                </Card>
            </div>

            {/* Filters and Search */}
            <SectionCard>
                <div className="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                    <div className="flex flex-wrap gap-3 flex-1">
                        <div className="flex items-center gap-2">
                            <Filter className="w-4 h-4 text-gray-500" />
                            <select
                                value={statusFilter}
                                onChange={(e) => setStatusFilter(e.target.value)}
                                className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent"
                            >
                                <option value="all">All Status</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="in_progress">In Progress</option>
                            </select>
                        </div>

                        <select
                            value={dateFilter}
                            onChange={(e) => setDateFilter(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent"
                        >
                            <option value="upcoming">Upcoming</option>
                            <option value="today">Today</option>
                            <option value="past">Past</option>
                            <option value="all">All Dates</option>
                        </select>
                    </div>

                    <div className="relative w-full md:w-auto">
                        <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                        <input
                            type="text"
                            placeholder="Search appointments..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm w-full md:w-64 focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent"
                        />
                    </div>
                </div>
            </SectionCard>

            {/* Appointments List */}
            <SectionCard>
                <h2 className="text-lg font-semibold text-gray-900 mb-4">
                    {dateFilter === 'today' ? 'Today\'s Appointments' : 
                     dateFilter === 'upcoming' ? 'Upcoming Appointments' : 
                     dateFilter === 'past' ? 'Past Appointments' : 
                     'All Appointments'}
                </h2>

                {appointmentsLoading ? (
                    <div className="text-center py-12">
                        <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--theme-primary)]"></div>
                        <p className="mt-4 text-gray-600">Loading appointments...</p>
                    </div>
                ) : appointments.length === 0 ? (
                    <div className="text-center py-12">
                        <Calendar className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-600">No appointments found</p>
                        <p className="text-gray-500 text-sm mt-2">
                            {search ? 'Try adjusting your search' : 'Create a new appointment to get started'}
                        </p>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {appointments.map((appointment) => (
                            <div
                                key={appointment.id}
                                className="p-4 border border-gray-200 rounded-lg hover:border-[var(--theme-primary)] hover:shadow-md transition-all"
                            >
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-3 mb-2">
                                            <div className={`px-2 py-1 rounded text-xs font-medium border ${getStatusBadge(appointment.status)}`}>
                                                {appointment.status?.replace('_', ' ').toUpperCase() || 'SCHEDULED'}
                                            </div>
                                            {appointment.appointment_type && (
                                                <div className="flex items-center gap-1 text-sm text-gray-600">
                                                    <Stethoscope className="w-4 h-4" />
                                                    {appointment.appointment_type?.name || 'General'}
                                                </div>
                                            )}
                                        </div>

                                        <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                            {appointment.resident ? (
                                                `${appointment.resident.first_name} ${appointment.resident.last_name}`
                                            ) : (
                                                'Unknown Resident'
                                            )}
                                        </h3>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-600">
                                            <div className="flex items-center gap-2">
                                                <Calendar className="w-4 h-4" />
                                                <span>{formatDate(appointment.appointment_date)}</span>
                                                {appointment.appointment_time && (
                                                    <span className="ml-2">at {formatTime(appointment.appointment_time)}</span>
                                                )}
                                            </div>

                                            {appointment.location && (
                                                <div className="flex items-center gap-2">
                                                    <MapPin className="w-4 h-4" />
                                                    <span>{appointment.location}</span>
                                                </div>
                                            )}

                                            {appointment.provider_name && (
                                                <div className="flex items-center gap-2">
                                                    <User className="w-4 h-4" />
                                                    <span>{appointment.provider_name}</span>
                                                </div>
                                            )}

                                            {appointment.branch && (
                                                <div className="flex items-center gap-2">
                                                    <MapPin className="w-4 h-4" />
                                                    <span>{appointment.branch.name}</span>
                                                </div>
                                            )}
                                        </div>

                                        {appointment.description && (
                                            <p className="text-sm text-gray-600 mt-2 line-clamp-2">
                                                {appointment.description}
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex items-center gap-2 ml-4">
                                        {isAdmin && appointment.status === 'scheduled' && (
                                            <button
                                                onClick={() => handleQuickComplete(appointment.id)}
                                                disabled={completeMutation.isPending}
                                                className="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium flex items-center gap-1.5 disabled:opacity-50"
                                                title="Mark as Complete"
                                            >
                                                <CheckCircle className="w-4 h-4" />
                                                Complete
                                            </button>
                                        )}
                                        <Link
                                            to={`/appointments?id=${appointment.id}`}
                                            className="px-3 py-1.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors text-sm font-medium flex items-center gap-1.5"
                                        >
                                            View
                                            <ChevronRight className="w-4 h-4" />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </SectionCard>
        </div>
    );
}

