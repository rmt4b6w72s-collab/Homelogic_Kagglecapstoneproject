import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { Bar, Doughnut, Line } from 'react-chartjs-2';
import { defaultOptions, colors } from '../../utils/chartConfig';

export default function AppointmentsCharts() {
    const { data, isLoading } = useQuery({
        queryKey: ['charts-appointments'],
        queryFn: async () => (await api.get('/charts/appointments')).data,
    });

    if (isLoading) {
        return (
            <div className="text-center py-12">
                <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[#2D5016]"></div>
                <p className="mt-4 text-gray-600">Loading appointment charts...</p>
            </div>
        );
    }

    return (
        <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-6">Appointments Charts</h1>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Total Appointments</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.total_appointments || 0}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Upcoming</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.upcoming || 0}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Completed</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.completed || 0}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Pending</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.pending || 0}</p>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-xl font-semibold text-gray-900 mb-4">Appointments by Status</h2>
                    <div className="h-64">
                        {data?.by_status?.length ? (
                            <Doughnut
                                data={{
                                    labels: data.by_status.map(s => s.status),
                                    datasets: [{
                                        data: data.by_status.map(s => s.count),
                                        backgroundColor: [colors.primary, colors.success, colors.warning, colors.danger],
                                    }],
                                }}
                                options={defaultOptions}
                            />
                        ) : (
                            <div className="h-64 flex items-center justify-center text-gray-500">No data available</div>
                        )}
                    </div>
                </div>

                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-xl font-semibold text-gray-900 mb-4">Appointment Trends (Last 7 Days)</h2>
                    <div className="h-64">
                        {data?.trends?.length ? (
                            <Line
                                data={{
                                    labels: data.trends.map(t => t.date),
                                    datasets: [{
                                        label: 'Appointments',
                                        data: data.trends.map(t => t.count),
                                        borderColor: colors.info,
                                        backgroundColor: colors.info + '20',
                                        fill: true,
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
        </div>
    );
}
