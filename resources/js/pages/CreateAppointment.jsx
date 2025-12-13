import React, { useState, useMemo } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../services/api';
import { Calendar, Edit, ArrowLeft, CheckCircle, Stethoscope, MapPin, ChevronDown, X, List, Grid } from 'lucide-react';
import CalendarView from '../components/CalendarView';

export default function CreateAppointment() {
    const { residentId } = useParams();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [formData, setFormData] = useState({
        appointment_date: new Date().toISOString().split('T')[0],
        appointment_time: '',
        provider_name: '',
        location: '',
        description: '',
    });
    const [errors, setErrors] = useState({});
    const [completingAppointment, setCompletingAppointment] = useState(null);
    const [completionNotes, setCompletionNotes] = useState('');
    const [viewMode, setViewMode] = useState('list'); // 'list' or 'calendar' - default to list (calendar hidden)

    // Fetch resident data
    const { data: residentData, isLoading: residentLoading } = useQuery({
        queryKey: ['resident', residentId],
        queryFn: async () => {
            const response = await api.get(`/residents/${residentId}`);
            return response.data;
        },
        enabled: !!residentId,
    });

    // Fetch appointments for this resident
    const { data: appointmentsData, isLoading: appointmentsLoading, refetch } = useQuery({
        queryKey: ['appointments', residentId],
        queryFn: async () => {
            const response = await api.get('/appointments', {
                params: {
                    resident_id: residentId,
                    per_page: 100
                }
            });
            return response.data;
        },
        enabled: !!residentId,
    });

    // Submit appointment mutation
    const submitMutation = useMutation({
        mutationFn: async () => {
            const payload = {
                resident_id: parseInt(residentId),
                branch_id: residentData?.branch_id || '',
                appointment_date: formData.appointment_date,
                appointment_time: formData.appointment_time,
                provider_name: formData.provider_name || null,
                location: formData.location || null,
                description: formData.description || null,
                notes: formData.description || null,
                status: 'scheduled',
            };

            return await api.post('/appointments', payload);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['appointments', residentId]);
            setFormData({
                appointment_date: new Date().toISOString().split('T')[0],
                appointment_time: '',
                provider_name: '',
                location: '',
                description: '',
            });
            setErrors({});
            refetch();
        },
        onError: (error) => {
            console.error('Error creating appointment:', error);
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            }
        },
    });

    // Mark appointment as complete mutation
    const markCompleteMutation = useMutation({
        mutationFn: async ({ appointmentId, notes }) => {
            return await api.patch(`/appointments/${appointmentId}/status`, {
                status: 'completed',
                notes: notes || null,
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['appointments', residentId]);
            setCompletingAppointment(null);
            setCompletionNotes('');
            refetch();
        },
        onError: (error) => {
            console.error('Error marking appointment as complete:', error);
            alert('Failed to mark appointment as complete. Please try again.');
        },
    });

    const handleMarkComplete = (appointment) => {
        setCompletingAppointment(appointment);
        setCompletionNotes('');
    };

    const handleCompleteSubmit = () => {
        if (!completingAppointment) return;
        markCompleteMutation.mutate({
            appointmentId: completingAppointment.id,
            notes: completionNotes,
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const nextErrors = {};

        if (!formData.appointment_date) {
            nextErrors.appointment_date = 'Date is required';
        }

        if (!formData.appointment_time) {
            nextErrors.appointment_time = 'Time is required';
        }

        if (Object.keys(nextErrors).length > 0) {
            setErrors(nextErrors);
            return;
        }

        setErrors({});
        submitMutation.mutate();
    };

    if (residentLoading) {
        return (
            <div className="flex items-center justify-center min-h-screen">
                <div className="text-center">
                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--theme-primary)]"></div>
                    <p className="mt-4 text-gray-600">Loading...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <button
                            onClick={() => navigate('/appointments')}
                            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                            title="Back to Appointments"
                        >
                            <ArrowLeft className="w-5 h-5 text-gray-600" />
                        </button>
                        <div>
                            <h2 className="text-xl font-semibold text-gray-900">
                                Schedule Appointment
                            </h2>
                            <p className="text-sm text-gray-500">
                                {residentData?.first_name} {residentData?.last_name}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Appointment Form */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Appointment Details</h3>
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Date *
                                </label>
                                <input
                                    type="date"
                                    value={formData.appointment_date}
                                    onChange={(e) => {
                                        setFormData({ ...formData, appointment_date: e.target.value });
                                        setErrors({ ...errors, appointment_date: null });
                                    }}
                                    required
                                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent ${errors.appointment_date ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                />
                                {errors.appointment_date && (
                                    <p className="text-xs text-red-600 mt-1">{errors.appointment_date}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Time *
                                </label>
                                <input
                                    type="time"
                                    value={formData.appointment_time}
                                    onChange={(e) => {
                                        setFormData({ ...formData, appointment_time: e.target.value });
                                        setErrors({ ...errors, appointment_time: null });
                                    }}
                                    required
                                    className={`w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent ${errors.appointment_time ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                />
                                {errors.appointment_time && (
                                    <p className="text-xs text-red-600 mt-1">{errors.appointment_time}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Provider Name
                                </label>
                                <div className="relative">
                                    <Stethoscope className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                    <input
                                        type="text"
                                        value={formData.provider_name}
                                        onChange={(e) => setFormData({ ...formData, provider_name: e.target.value })}
                                        className="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent"
                                        placeholder="Dr. Smith"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Location
                                </label>
                                <div className="relative">
                                    <MapPin className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                    <input
                                        type="text"
                                        value={formData.location}
                                        onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                                        className="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent"
                                        placeholder="Clinic / Room"
                                    />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Notes / Description
                            </label>
                            <textarea
                                value={formData.description}
                                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                placeholder="Enter any additional details..."
                                rows={4}
                                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent resize-none"
                            />
                        </div>

                        {submitMutation.isError && (
                            <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                                <p className="text-sm text-red-800">
                                    {submitMutation.error?.response?.data?.message || 'Failed to create appointment. Please try again.'}
                                </p>
                                {submitMutation.error?.response?.data?.errors && (
                                    <ul className="mt-2 list-disc list-inside text-xs text-red-700">
                                        {Object.entries(submitMutation.error.response.data.errors).map(([key, messages]) => (
                                            <li key={key}>{key}: {Array.isArray(messages) ? messages.join(', ') : messages}</li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        )}
                    </div>

                    <div className="flex justify-center mt-6">
                        <button
                            type="submit"
                            disabled={submitMutation.isPending || !formData.appointment_date || !formData.appointment_time}
                            className="px-6 py-2 bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] font-bold rounded-lg hover:bg-[var(--theme-primary-hover)] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {submitMutation.isPending ? 'Creating...' : 'Create Appointment'}
                        </button>
                    </div>
                </form>
            </div>

            {/* Appointment History Grid */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200">
                <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 className="text-lg font-semibold text-gray-900">Appointment History</h2>
                    {appointmentsData?.data?.length > 0 && (
                        <div className="inline-flex rounded-lg border border-gray-200 bg-white p-1 shadow-sm">
                            <button
                                onClick={() => setViewMode('list')}
                                className={`flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors ${viewMode === 'list'
                                        ? 'bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)]'
                                        : 'text-gray-700 hover:bg-gray-50'
                                    }`}
                            >
                                <List className="w-4 h-4" />
                                List View
                            </button>
                            <button
                                onClick={() => setViewMode('calendar')}
                                className={`flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors ${viewMode === 'calendar'
                                        ? 'bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)]'
                                        : 'text-gray-700 hover:bg-gray-50'
                                    }`}
                            >
                                <Grid className="w-4 h-4" />
                                Calendar View
                            </button>
                        </div>
                    )}
                </div>
                <div className="p-6">
                    {appointmentsLoading ? (
                        <div className="text-center py-12">
                            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--theme-primary)]"></div>
                            <p className="mt-4 text-gray-600">Loading appointments...</p>
                        </div>
                    ) : appointmentsData?.data?.length > 0 ? (
                        viewMode === 'calendar' ? (
                            <CalendarView
                                events={useMemo(() => {
                                    if (!appointmentsData?.data) return [];
                                    return appointmentsData.data.map(apt => {
                                        const date = apt.appointment_date ? new Date(apt.appointment_date) : new Date();
                                        let start = new Date(date);
                                        let end = new Date(date);

                                        if (apt.appointment_time) {
                                            const timeParts = apt.appointment_time.split(':');
                                            if (timeParts.length >= 2) {
                                                const hours = parseInt(timeParts[0]) || 0;
                                                const minutes = parseInt(timeParts[1]) || 0;
                                                start.setHours(hours, minutes, 0);
                                                end.setHours(hours + 1, minutes, 0);
                                            }
                                        } else {
                                            start.setHours(9, 0, 0);
                                            end.setHours(10, 0, 0);
                                        }

                                        const statusColors = {
                                            scheduled: 'var(--theme-primary)',
                                            confirmed: '#10b981',
                                            completed: '#059669',
                                            cancelled: '#ef4444',
                                            pending: '#f59e0b',
                                        };

                                        return {
                                            id: apt.id,
                                            title: `${apt.resident?.first_name || ''} ${apt.resident?.last_name || ''} - ${apt.appointment_type?.name || apt.appointmentType?.name || apt.description || 'Appointment'}`,
                                            start,
                                            end,
                                            color: statusColors[apt.status] || 'var(--theme-primary)',
                                            borderColor: statusColors[apt.status] || 'var(--theme-primary)',
                                            textColor: '#ffffff',
                                            resource: apt,
                                        };
                                    });
                                }, [appointmentsData?.data])}
                                onSelectEvent={(event) => {
                                    if (event.resource) {
                                        // Navigate to edit or show details
                                        navigate(`/appointments?edit=${event.resource.id}`);
                                    }
                                }}
                                views={['month', 'week', 'day']}
                            />
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {appointmentsData.data.map((appointment) => {
                                    if (!appointment) return null;

                                    const date = appointment.appointment_date ? new Date(appointment.appointment_date) : null;
                                    const dateStr = date && !isNaN(date.getTime()) ? date.toLocaleDateString('en-US', {
                                        month: 'long',
                                        day: 'numeric',
                                        year: 'numeric'
                                    }) : 'N/A';

                                    let timeStr = '';
                                    if (appointment.appointment_time) {
                                        try {
                                            const timeParts = appointment.appointment_time.split(':');
                                            if (timeParts.length >= 2) {
                                                const hours = parseInt(timeParts[0]) || 0;
                                                const minutes = timeParts[1] || '00';
                                                const hour12 = hours % 12 || 12;
                                                const ampm = hours >= 12 ? 'PM' : 'AM';
                                                timeStr = `${hour12}:${minutes} ${ampm}`;
                                            }
                                        } catch (err) {
                                            console.error('Error parsing appointment time:', err);
                                        }
                                    }

                                    const nextApptDate = appointment.next_appointment_date ? new Date(appointment.next_appointment_date) : null;
                                    const nextApptDateStr = nextApptDate && !isNaN(nextApptDate.getTime()) ? nextApptDate.toLocaleDateString('en-US', {
                                        month: 'long',
                                        day: 'numeric',
                                        year: 'numeric'
                                    }) : 'N/A';

                                    return (
                                        <div key={appointment.id} className="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow border border-gray-200 p-5">
                                            <div className="flex items-start justify-between mb-3">
                                                <div className="flex-1">
                                                    <h4 className="text-lg font-semibold text-gray-900 mb-1">
                                                        {appointment.resident?.first_name} {appointment.resident?.last_name}
                                                    </h4>
                                                    <div className="flex items-center space-x-2 text-sm text-gray-600">
                                                        <Calendar className="w-4 h-4" />
                                                        <span>{dateStr}</span>
                                                        {timeStr && <span className="text-gray-500">• {timeStr}</span>}
                                                    </div>
                                                </div>
                                                <span className={`px-2 py-1 rounded-full text-xs font-medium ${appointment.status === 'scheduled' ? 'bg-amber-100 text-amber-800' :
                                                        appointment.status === 'confirmed' ? 'bg-green-100 text-green-800' :
                                                            appointment.status === 'completed' ? 'bg-emerald-100 text-emerald-800' :
                                                                appointment.status === 'cancelled' ? 'bg-red-100 text-red-800' :
                                                                    'bg-gray-100 text-gray-800'
                                                    }`}>
                                                    {appointment.status?.charAt(0).toUpperCase() + appointment.status?.slice(1)}
                                                </span>
                                            </div>

                                            <div className="space-y-2 mb-4">
                                                <div className="text-sm text-gray-600">
                                                    <span className="font-medium">Type:</span> {appointment.appointment_type?.name || appointment.appointmentType?.name || 'Other'}
                                                </div>
                                                {(appointment.description || appointment.provider_name) && (
                                                    <div className="text-sm text-gray-600">
                                                        <span className="font-medium">Details:</span> {appointment.description || appointment.provider_name || '-'}
                                                    </div>
                                                )}
                                                {nextApptDateStr !== 'N/A' && (
                                                    <div className="text-sm text-gray-600">
                                                        <span className="font-medium">Next Appointment:</span> {nextApptDateStr}
                                                    </div>
                                                )}
                                                {appointment.notes && (
                                                    <div className="text-sm text-gray-600 mt-2 pt-2 border-t border-gray-200">
                                                        <span className="font-medium">Notes:</span>
                                                        <p className="text-gray-700 mt-1 whitespace-pre-wrap">{appointment.notes}</p>
                                                    </div>
                                                )}
                                            </div>

                                            <div className="flex items-center justify-end space-x-2">
                                                {appointment.status !== 'completed' && appointment.status !== 'cancelled' && (
                                                    <button
                                                        onClick={() => handleMarkComplete(appointment)}
                                                        className="flex items-center space-x-1 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"
                                                        title="Mark as Complete"
                                                    >
                                                        <CheckCircle className="w-4 h-4" />
                                                        <span>Mark Complete</span>
                                                    </button>
                                                )}
                                                <button
                                                    onClick={() => {
                                                        // Navigate to edit appointment or open modal
                                                        navigate(`/appointments?edit=${appointment.id}`);
                                                    }}
                                                    className="text-[var(--theme-primary)] hover:text-[var(--theme-primary-hover)] p-2 hover:bg-gray-100 rounded-lg transition-colors"
                                                    title="Edit"
                                                >
                                                    <Edit className="w-5 h-5" />
                                                </button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )
                    ) : (
                        <div className="text-center py-12">
                            <Calendar className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                            <p className="text-gray-900 text-lg font-semibold mb-2">No Appointments Found</p>
                            <p className="text-gray-500 text-sm">No appointments found for this resident.</p>
                        </div>
                    )}
                </div>
            </div>

            {/* Mark Complete Modal */}
            {completingAppointment && (
                <div className="fixed inset-0 backdrop-blur-md bg-black/50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                        <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h3 className="text-xl font-semibold text-gray-900">Mark Appointment as Complete</h3>
                            <button
                                onClick={() => {
                                    setCompletingAppointment(null);
                                    setCompletionNotes('');
                                }}
                                className="text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                        <div className="px-6 py-4">
                            <div className="mb-4">
                                <p className="text-sm text-gray-600 mb-2">
                                    <span className="font-medium">Date:</span> {completingAppointment.appointment_date ? new Date(completingAppointment.appointment_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : 'N/A'}
                                </p>
                                <p className="text-sm text-gray-600 mb-2">
                                    <span className="font-medium">Provider:</span> {completingAppointment.provider_name || 'N/A'}
                                </p>
                                <p className="text-sm text-gray-600">
                                    <span className="font-medium">Type:</span> {completingAppointment.appointment_type?.name || completingAppointment.appointmentType?.name || 'Other'}
                                </p>
                            </div>
                            <div className="mb-4">
                                <label className="block text-sm font-bold text-gray-900 mb-2">
                                    Completion Notes <span className="text-gray-500 font-normal">(Optional)</span>
                                </label>
                                <textarea
                                    value={completionNotes}
                                    onChange={(e) => setCompletionNotes(e.target.value)}
                                    placeholder="Enter notes about the appointment outcome..."
                                    rows={4}
                                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent resize-none"
                                />
                            </div>
                        </div>
                        <div className="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                            <button
                                onClick={() => {
                                    setCompletingAppointment(null);
                                    setCompletionNotes('');
                                }}
                                className="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                                disabled={markCompleteMutation.isPending}
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleCompleteSubmit}
                                disabled={markCompleteMutation.isPending}
                                className="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {markCompleteMutation.isPending ? 'Marking...' : 'Mark as Complete'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

