import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { Bar, Doughnut } from 'react-chartjs-2';
import { defaultOptions, colors } from '../../utils/chartConfig';

export default function StaffCharts() {
    const { data, isLoading } = useQuery({
        queryKey: ['charts-staff'],
        queryFn: async () => (await api.get('/charts/staff')).data,
    });

    if (isLoading) {
        return (
            <div className="text-center py-12">
                <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[#2D5016]"></div>
                <p className="mt-4 text-gray-600">Loading staff charts...</p>
            </div>
        );
    }

    return (
        <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-6">Staff Charts</h1>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Total Staff</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.total_staff || 0}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Caregivers</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.total_caregivers || 0}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Active Assignments</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.active_assignments || 0}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Pending Leave</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.pending_leave || 0}</p>
                </div>
            </div>

            <div className="bg-white rounded-lg shadow p-6">
                <h2 className="text-xl font-semibold text-gray-900 mb-4">Leave Requests by Status</h2>
                <div className="h-64">
                    {data?.leave_by_status?.length ? (
                        <Doughnut
                            data={{
                                labels: data.leave_by_status.map(l => l.status),
                                datasets: [{
                                    data: data.leave_by_status.map(l => l.count),
                                    backgroundColor: [colors.primary, colors.success, colors.warning],
                                }],
                            }}
                            options={defaultOptions}
                        />
                    ) : (
                        <div className="h-64 flex items-center justify-center text-gray-500">No data available</div>
                    )}
                </div>
            </div>
        </div>
    );
}
