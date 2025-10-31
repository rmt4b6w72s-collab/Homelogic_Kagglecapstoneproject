import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { Bar, Doughnut, Line } from 'react-chartjs-2';
import { defaultOptions, colors } from '../../utils/chartConfig';

export default function ResidentCharts() {
    const { data, isLoading } = useQuery({
        queryKey: ['charts-residents'],
        queryFn: async () => (await api.get('/charts/residents')).data,
    });

    if (isLoading) {
        return (
            <div className="text-center py-12">
                <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[#2D5016]"></div>
                <p className="mt-4 text-gray-600">Loading resident charts...</p>
            </div>
        );
    }

    return (
        <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-6">Resident Charts</h1>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-xl font-semibold text-gray-900 mb-4">Residents by Branch</h2>
                    <div className="h-64">
                        {data?.by_branch?.length ? (
                            <Bar
                                data={{
                                    labels: data.by_branch.map(b => b.branch_name),
                                    datasets: [{
                                        label: 'Residents',
                                        data: data.by_branch.map(b => b.count),
                                        backgroundColor: [colors.primary, colors.info, colors.success, colors.warning, colors.danger],
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
                    <h2 className="text-xl font-semibold text-gray-900 mb-4">Residents by Status</h2>
                    <div className="h-64">
                        {data?.by_status?.length ? (
                            <Doughnut
                                data={{
                                    labels: data.by_status.map(s => s.status || 'Unknown'),
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
            </div>

            <div className="bg-white rounded-lg shadow p-6">
                <h2 className="text-xl font-semibold text-gray-900 mb-4">Summary Statistics</h2>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="text-center">
                        <p className="text-3xl font-bold text-gray-900">{data?.total_residents || 0}</p>
                        <p className="text-sm text-gray-600 mt-1">Total Residents</p>
                    </div>
                    <div className="text-center">
                        <p className="text-3xl font-bold text-gray-900">{data?.active_residents || 0}</p>
                        <p className="text-sm text-gray-600 mt-1">Active Residents</p>
                    </div>
                </div>
            </div>
        </div>
    );
}
