import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { Line, Bar } from 'react-chartjs-2';
import { defaultOptions, colors } from '../../utils/chartConfig';

export default function VitalsCharts() {
    const { data, isLoading } = useQuery({
        queryKey: ['charts-vitals'],
        queryFn: async () => (await api.get('/charts/vitals')).data,
    });

    if (isLoading) {
        return (
            <div className="text-center py-12">
                <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[#2D5016]"></div>
                <p className="mt-4 text-gray-600">Loading vitals charts...</p>
            </div>
        );
    }

    return (
        <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-6">Vitals Charts</h1>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Total Vitals</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.total_vitals || 0}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Today</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.today_vitals || 0}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">This Week</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.week_vitals || 0}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">This Month</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.month_vitals || 0}</p>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-xl font-semibold text-gray-900 mb-4">Vitals Trends (Last 7 Days)</h2>
                    <div className="h-64">
                        {data?.trends?.length ? (
                            <Line
                                data={{
                                    labels: data.trends.map(t => t.date),
                                    datasets: [{
                                        label: 'Vitals Count',
                                        data: data.trends.map(t => t.count),
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
                    <h2 className="text-xl font-semibold text-gray-900 mb-4">Blood Pressure Trends</h2>
                    <div className="h-64">
                        {data?.blood_pressure?.labels?.length ? (
                            <Line
                                data={{
                                    labels: data.blood_pressure.labels.slice(0, 20),
                                    datasets: [
                                        {
                                            label: 'Systolic',
                                            data: data.blood_pressure.systolic.slice(0, 20),
                                            borderColor: colors.danger,
                                            backgroundColor: colors.danger + '20',
                                            fill: false,
                                        },
                                        {
                                            label: 'Diastolic',
                                            data: data.blood_pressure.diastolic.slice(0, 20),
                                            borderColor: colors.info,
                                            backgroundColor: colors.info + '20',
                                            fill: false,
                                        },
                                    ],
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
                <h2 className="text-xl font-semibold text-gray-900 mb-4">Temperature Trends</h2>
                <div className="h-64">
                    {data?.temperature?.labels?.length ? (
                        <Bar
                            data={{
                                labels: data.temperature.labels.slice(0, 30),
                                datasets: [{
                                    label: 'Temperature (°F)',
                                    data: data.temperature.temperature.slice(0, 30),
                                    backgroundColor: colors.warning,
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
