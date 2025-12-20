import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { Bar, Doughnut, Line } from 'react-chartjs-2';
import { defaultOptions, colors } from '../../utils/chartConfig';
import { 
    UserCheck, 
    RefreshCcw,
    Download,
    Users,
    CheckCircle2,
    Clock,
    Calendar,
    PieChart,
    BarChart3,
    TrendingUp,
    MapPin,
    UserCog,
    AlertCircle,
    ChevronDown,
    ChevronUp,
    Building2,
    Award
} from 'lucide-react';

export default function StaffCharts() {
    const [expandedCards, setExpandedCards] = useState({});

    const { data, isLoading, refetch } = useQuery({
        queryKey: ['charts-staff'],
        queryFn: async () => (await api.get('/charts/staff')).data,
    });

    const toggleCard = (cardId) => {
        setExpandedCards(prev => ({
            ...prev,
            [cardId]: !prev[cardId]
        }));
    };

    const handleExport = () => {
        if (!data) return;
        let csv = 'Category,Value\n';
        csv += `Total Staff,${data.total_staff || 0}\n`;
        csv += `Caregivers,${data.total_caregivers || 0}\n`;
        csv += `Active Assignments,${data.active_assignments || 0}\n`;
        csv += `Pending Leave,${data.pending_leave || 0}\n`;
        csv += `Approved Leave,${data.approved_leave || 0}\n`;
        csv += `Today Clock-ins,${data.today_clock_ins || 0}\n`;
        csv += `Active Clock-ins,${data.active_clock_ins || 0}\n`;
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'staff-charts.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    };

    if (isLoading) {
        return (
            <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
                <div className="max-w-7xl mx-auto px-4 py-8">
                    <div className="text-center py-12">
                        <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--theme-primary)]"></div>
                        <p className="mt-4 text-gray-600">Loading staff charts...</p>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
            <div className="max-w-7xl mx-auto px-4 py-8">
                {/* Header */}
                <div className="mb-8">
                    <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                                <UserCheck className="h-8 w-8 text-indigo-600" />
                                Staff Analytics Dashboard
                            </h1>
                            <p className="mt-2 text-gray-600">Comprehensive staff statistics and leave management</p>
                        </div>
                        <div className="flex items-center gap-3">
                            <button
                                onClick={handleExport}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                            >
                                <Download className="h-4 w-4" />
                                Export
                            </button>
                            <button
                                onClick={() => refetch()}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] rounded-lg text-sm font-medium hover:bg-[var(--theme-primary-hover)] transition"
                            >
                                <RefreshCcw className="h-4 w-4" />
                                Refresh
                            </button>
                        </div>
                    </div>
                </div>

                {/* Statistics Cards - Row 1 */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
                    <div className="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-transparent">
                        <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-indigo-500 to-indigo-600"></div>
                        <div className="p-6">
                            <div className="flex items-start justify-between mb-3">
                                <div className="flex-1">
                                    <p className="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-2">Total Staff</p>
                                    <p className="text-3xl font-bold text-gray-900">{(data?.total_staff || 0).toLocaleString()}</p>
                                    {data?.staff_by_role && data.staff_by_role.length > 0 && (
                                        <p className="text-xs text-gray-500 mt-1">
                                            {data.staff_by_role.length} role{data.staff_by_role.length !== 1 ? 's' : ''}
                                        </p>
                                    )}
                                </div>
                                <div className="bg-indigo-50 p-3 rounded-xl group-hover:scale-110 transition-transform duration-300">
                                    <UserCheck className="w-6 h-6 text-indigo-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-transparent">
                        <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-500 to-blue-600"></div>
                        <div className="p-6">
                            <div className="flex items-start justify-between mb-3">
                                <div className="flex-1">
                                    <p className="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-2">Caregivers</p>
                                    <p className="text-3xl font-bold text-gray-900">{(data?.total_caregivers || 0).toLocaleString()}</p>
                                    {data?.total_staff > 0 && (
                                        <p className="text-xs text-gray-500 mt-1">
                                            {Math.round((data.total_caregivers / data.total_staff) * 100)}% of total staff
                                        </p>
                                    )}
                                </div>
                                <div className="bg-blue-50 p-3 rounded-xl group-hover:scale-110 transition-transform duration-300">
                                    <Users className="w-6 h-6 text-blue-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Enhanced Active Assignments Card */}
                    <div className="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-transparent">
                        <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-green-500 to-green-600"></div>
                        <div className="p-6">
                            <div className="flex items-start justify-between mb-3">
                                <div className="flex-1">
                                    <p className="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-2">Active Assignments</p>
                                    <p className="text-3xl font-bold text-gray-900">{(data?.active_assignments || 0).toLocaleString()}</p>
                                    {data?.assignments_by_caregiver && data.assignments_by_caregiver.length > 0 && (
                                        <p className="text-xs text-gray-500 mt-1">
                                            {data.assignments_by_caregiver.length} caregiver{data.assignments_by_caregiver.length !== 1 ? 's' : ''} assigned
                                        </p>
                                    )}
                                </div>
                                <div className="bg-green-50 p-3 rounded-xl group-hover:scale-110 transition-transform duration-300">
                                    <CheckCircle2 className="w-6 h-6 text-green-600" />
                                </div>
                            </div>
                            {(data?.assignments_by_branch?.length > 0 || data?.assignments_by_caregiver?.length > 0) && (
                                <button
                                    onClick={() => toggleCard('assignments')}
                                    className="w-full mt-3 flex items-center justify-between text-xs text-gray-600 hover:text-gray-900 transition"
                                >
                                    <span>View details</span>
                                    {expandedCards.assignments ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                                </button>
                            )}
                            {expandedCards.assignments && (
                                <div className="mt-4 pt-4 border-t border-gray-200 space-y-3">
                                    {data?.assignments_by_branch && data.assignments_by_branch.length > 0 && (
                                        <div>
                                            <p className="text-xs font-semibold text-gray-700 mb-2">By Branch:</p>
                                            <div className="space-y-1">
                                                {data.assignments_by_branch.map((item, idx) => (
                                                    <div key={idx} className="flex items-center justify-between text-xs">
                                                        <span className="text-gray-600">{item.branch?.name || 'Unknown'}</span>
                                                        <span className="font-semibold text-gray-900">{item.count}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                    {data?.assignments_by_caregiver && data.assignments_by_caregiver.length > 0 && (
                                        <div>
                                            <p className="text-xs font-semibold text-gray-700 mb-2">Top Caregivers:</p>
                                            <div className="space-y-1">
                                                {data.assignments_by_caregiver.slice(0, 3).map((item, idx) => (
                                                    <div key={idx} className="flex items-center justify-between text-xs">
                                                        <span className="text-gray-600 truncate">{item.caregiver?.name || 'Unknown'}</span>
                                                        <span className="font-semibold text-gray-900">{item.count}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Enhanced Pending Leave Card */}
                    <div className="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-transparent">
                        <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-orange-500 to-orange-600"></div>
                        <div className="p-6">
                            <div className="flex items-start justify-between mb-3">
                                <div className="flex-1">
                                    <p className="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-2">Pending Leave</p>
                                    <p className="text-3xl font-bold text-gray-900">{(data?.pending_leave || 0).toLocaleString()}</p>
                                    {data?.approved_leave > 0 && (
                                        <p className="text-xs text-gray-500 mt-1">
                                            {data.approved_leave} approved
                                        </p>
                                    )}
                                </div>
                                <div className="bg-orange-50 p-3 rounded-xl group-hover:scale-110 transition-transform duration-300">
                                    <Clock className="w-6 h-6 text-orange-600" />
                                </div>
                            </div>
                            {data?.recent_pending_leave && data.recent_pending_leave.length > 0 && (
                                <button
                                    onClick={() => toggleCard('leave')}
                                    className="w-full mt-3 flex items-center justify-between text-xs text-gray-600 hover:text-gray-900 transition"
                                >
                                    <span>View requests</span>
                                    {expandedCards.leave ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
                                </button>
                            )}
                            {expandedCards.leave && data?.recent_pending_leave && data.recent_pending_leave.length > 0 && (
                                <div className="mt-4 pt-4 border-t border-gray-200 space-y-2 max-h-48 overflow-y-auto">
                                    {data.recent_pending_leave.map((leave, idx) => (
                                        <div key={idx} className="text-xs">
                                            <div className="flex items-center justify-between">
                                                <span className="font-medium text-gray-900">{leave.staff?.name || 'Unknown'}</span>
                                                <span className="text-gray-500">
                                                    {new Date(leave.start_date).toLocaleDateString()} - {new Date(leave.end_date).toLocaleDateString()}
                                                </span>
                                            </div>
                                            <p className="text-gray-600 truncate">{leave.leave_type || 'Personal'}</p>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Statistics Cards - Row 2 (New Cards) */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
                    <div className="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-transparent">
                        <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-purple-500 to-purple-600"></div>
                        <div className="p-6">
                            <div className="flex items-start justify-between mb-3">
                                <div className="flex-1">
                                    <p className="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-2">Approved Leave</p>
                                    <p className="text-3xl font-bold text-gray-900">{(data?.approved_leave || 0).toLocaleString()}</p>
                                    {data?.pending_leave > 0 && (
                                        <p className="text-xs text-gray-500 mt-1">
                                            {data.pending_leave} pending
                                        </p>
                                    )}
                                </div>
                                <div className="bg-purple-50 p-3 rounded-xl group-hover:scale-110 transition-transform duration-300">
                                    <CheckCircle2 className="w-6 h-6 text-purple-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-transparent">
                        <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-teal-500 to-teal-600"></div>
                        <div className="p-6">
                            <div className="flex items-start justify-between mb-3">
                                <div className="flex-1">
                                    <p className="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-2">Today Clock-ins</p>
                                    <p className="text-3xl font-bold text-gray-900">{(data?.today_clock_ins || 0).toLocaleString()}</p>
                                    <p className="text-xs text-gray-500 mt-1">
                                        {data?.active_clock_ins || 0} currently active
                                    </p>
                                </div>
                                <div className="bg-teal-50 p-3 rounded-xl group-hover:scale-110 transition-transform duration-300">
                                    <Clock className="w-6 h-6 text-teal-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-transparent">
                        <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-cyan-500 to-cyan-600"></div>
                        <div className="p-6">
                            <div className="flex items-start justify-between mb-3">
                                <div className="flex-1">
                                    <p className="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-2">Active Clock-ins</p>
                                    <p className="text-3xl font-bold text-gray-900">{(data?.active_clock_ins || 0).toLocaleString()}</p>
                                    <p className="text-xs text-gray-500 mt-1">
                                        Currently working
                                    </p>
                                </div>
                                <div className="bg-cyan-50 p-3 rounded-xl group-hover:scale-110 transition-transform duration-300">
                                    <TrendingUp className="w-6 h-6 text-cyan-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-transparent">
                        <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-rose-500 to-rose-600"></div>
                        <div className="p-6">
                            <div className="flex items-start justify-between mb-3">
                                <div className="flex-1">
                                    <p className="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-2">Staff by Role</p>
                                    <p className="text-3xl font-bold text-gray-900">{data?.staff_by_role?.length || 0}</p>
                                    <p className="text-xs text-gray-500 mt-1">
                                        Different roles
                                    </p>
                                </div>
                                <div className="bg-rose-50 p-3 rounded-xl group-hover:scale-110 transition-transform duration-300">
                                    <UserCog className="w-6 h-6 text-rose-600" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Charts Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-xl font-semibold text-gray-900 flex items-center gap-2">
                                <PieChart className="h-5 w-5 text-indigo-600" />
                                Leave Requests by Status
                            </h2>
                        </div>
                        <div className="h-80">
                            {data?.leave_by_status?.length ? (
                                <Doughnut
                                    data={{
                                        labels: data.leave_by_status.map(l => l.status.charAt(0).toUpperCase() + l.status.slice(1)),
                                        datasets: [{
                                            data: data.leave_by_status.map(l => l.count),
                                            backgroundColor: [
                                                colors.primary + '80',
                                                colors.success + '80',
                                                colors.warning + '80',
                                            ],
                                            borderColor: [
                                                colors.primary,
                                                colors.success,
                                                colors.warning,
                                            ],
                                            borderWidth: 2,
                                        }],
                                    }}
                                    options={{
                                        ...defaultOptions,
                                        maintainAspectRatio: false,
                                    }}
                                />
                            ) : (
                                <div className="h-80 flex items-center justify-center text-gray-500">
                                    <div className="text-center">
                                        <PieChart className="h-12 w-12 text-gray-300 mx-auto mb-2" />
                                        <p>No data available</p>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {data?.staff_by_role && data.staff_by_role.length > 0 && (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h2 className="text-xl font-semibold text-gray-900 flex items-center gap-2">
                                    <BarChart3 className="h-5 w-5 text-indigo-600" />
                                    Staff by Role
                                </h2>
                            </div>
                            <div className="h-80">
                                <Bar
                                    data={{
                                        labels: data.staff_by_role.map(r => r.role.charAt(0).toUpperCase() + r.role.slice(1)),
                                        datasets: [{
                                            label: 'Count',
                                            data: data.staff_by_role.map(r => r.count),
                                            backgroundColor: colors.primary + '80',
                                            borderColor: colors.primary,
                                            borderWidth: 2,
                                        }],
                                    }}
                                    options={{
                                        ...defaultOptions,
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                title: {
                                                    display: true,
                                                    text: 'Number of Staff'
                                                }
                                            }
                                        }
                                    }}
                                />
                            </div>
                        </div>
                    )}
                </div>

                {/* Additional Details Section */}
                {(data?.assignments_by_branch?.length > 0 || data?.assignments_by_caregiver?.length > 0) && (
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {data?.assignments_by_branch && data.assignments_by_branch.length > 0 && (
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <h2 className="text-xl font-semibold text-gray-900 flex items-center gap-2">
                                        <Building2 className="h-5 w-5 text-indigo-600" />
                                        Assignments by Branch
                                    </h2>
                                </div>
                                <div className="space-y-3">
                                    {data.assignments_by_branch.map((item, idx) => (
                                        <div key={idx} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                                    <Building2 className="w-5 h-5 text-indigo-600" />
                                                </div>
                                                <div>
                                                    <p className="font-medium text-gray-900">{item.branch?.name || 'Unknown Branch'}</p>
                                                    <p className="text-xs text-gray-500">Branch assignments</p>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-2xl font-bold text-indigo-600">{item.count}</p>
                                                <p className="text-xs text-gray-500">assignments</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {data?.assignments_by_caregiver && data.assignments_by_caregiver.length > 0 && (
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <h2 className="text-xl font-semibold text-gray-900 flex items-center gap-2">
                                        <Award className="h-5 w-5 text-indigo-600" />
                                        Top Caregivers by Assignments
                                    </h2>
                                </div>
                                <div className="space-y-3">
                                    {data.assignments_by_caregiver.map((item, idx) => (
                                        <div key={idx} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                    <Users className="w-5 h-5 text-green-600" />
                                                </div>
                                                <div>
                                                    <p className="font-medium text-gray-900">{item.caregiver?.name || 'Unknown Caregiver'}</p>
                                                    <p className="text-xs text-gray-500">Active assignments</p>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-2xl font-bold text-green-600">{item.count}</p>
                                                <p className="text-xs text-gray-500">residents</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
