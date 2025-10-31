import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { Bar, Line } from 'react-chartjs-2';
import { defaultOptions, colors } from '../../utils/chartConfig';

export default function SleepCharts() {
    const { data, isLoading } = useQuery({
        queryKey: ['charts-sleep'],
        queryFn: async () => (await api.get('/charts/sleep')).data,
    });

    if (isLoading) {
        return (
            <div className="text-center py-12">
                <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[#2D5016]"></div>
                <p className="mt-4 text-gray-600">Loading sleep charts...</p>
            </div>
        );
    }

    return (
        <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-6">Sleep Charts</h1>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Total Records</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.total_records || 0}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Avg Sleep Hours</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">
                        {data?.avg_sleep_hours ? parseFloat(data.avg_sleep_hours).toFixed(1) : '0.0'}h
                    </p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Avg Quality</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">
                        {data?.avg_quality ? parseFloat(data.avg_quality).toFixed(1) : '0.0'}/10
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-xl font-semibold text-gray-900 mb-4">Sleep Duration Trends (Last 7 Days)</h2>
                    <div className="h-64">
                        {data?.sleep_duration_trends?.length ? (
                            <Line
                                data={{
                                    labels: data.sleep_duration_trends.map(t => t.date),
                                    datasets: [{
                                        label: 'Avg Hours',
                                        data: data.sleep_duration_trends.map(t => t.avg_hours),
                                        borderColor: colors.primary,
                                        backgroundColor: colors.primary + '20',
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

                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-xl font-semibold text-gray-900 mb-4">Sleep Quality Distribution</h2>
                    <div className="h-64">
                        {data?.quality_distribution?.length ? (
                            <Bar
                                data={{
                                    labels: data.quality_distribution.map(q => `Quality ${q.quality}`),
                                    datasets: [{
                                        label: 'Count',
                                        data: data.quality_distribution.map(q => q.count),
                                        backgroundColor: colors.info,
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
