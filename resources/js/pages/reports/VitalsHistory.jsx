import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { Line, Bar } from 'react-chartjs-2';
import { defaultOptions, colors } from '../../utils/chartConfig';
import { Calendar, Activity } from 'lucide-react';

export default function VitalsHistory() {
    const [dateRange, setDateRange] = useState('week');

    const { data: vitalsData, isLoading } = useQuery({
        queryKey: ['vitals-history', dateRange],
        queryFn: async () => {
            const params = { per_page: 100 };
            if (dateRange === 'week') {
                const weekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
                params.date_from = weekAgo.toISOString().split('T')[0];
            } else if (dateRange === 'month') {
                const monthAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);
                params.date_from = monthAgo.toISOString().split('T')[0];
            }
            return (await api.get('/vitals', { params })).data;
        },
    });

    if (isLoading) {
        return (
            <div className="text-center py-12">
                <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[#2D5016]"></div>
                <p className="mt-4 text-gray-600">Loading vitals history...</p>
            </div>
        );
    }

    // Process data for charts
    const chartData = React.useMemo(() => {
        if (!vitalsData?.data?.length) return null;

        const sorted = [...vitalsData.data].sort((a, b) => 
            new Date(a.measurement_date) - new Date(b.measurement_date)
        );

        return {
            labels: sorted.map(v => new Date(v.measurement_date).toLocaleDateString()),
            systolic: sorted.filter(v => v.systolic).map(v => v.systolic),
            diastolic: sorted.filter(v => v.diastolic).map(v => v.diastolic),
            temperature: sorted.filter(v => v.temperature).map(v => v.temperature),
            pulse: sorted.filter(v => v.pulse).map(v => v.pulse),
        };
    }, [vitalsData]);

    return (
        <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-6">Vitals History</h1>

            <div className="bg-white rounded-lg shadow p-6 mb-6">
                <div className="flex space-x-2">
                    {['week', 'month', 'all'].map((range) => (
                        <button
                            key={range}
                            onClick={() => setDateRange(range)}
                            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors capitalize ${
                                dateRange === range
                                    ? 'bg-[#2D5016] text-white'
                                    : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                            }`}
                        >
                            {range}
                        </button>
                    ))}
                </div>
            </div>

            {chartData ? (
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {chartData.systolic.length > 0 && (
                        <div className="bg-white rounded-lg shadow p-6">
                            <h2 className="text-xl font-semibold text-gray-900 mb-4">Blood Pressure</h2>
                            <div className="h-64">
                                <Line
                                    data={{
                                        labels: chartData.labels.slice(-30),
                                        datasets: [
                                            {
                                                label: 'Systolic',
                                                data: chartData.systolic.slice(-30),
                                                borderColor: colors.danger,
                                                backgroundColor: colors.danger + '20',
                                                fill: false,
                                            },
                                            {
                                                label: 'Diastolic',
                                                data: chartData.diastolic.slice(-30),
                                                borderColor: colors.info,
                                                backgroundColor: colors.info + '20',
                                                fill: false,
                                            },
                                        ],
                                    }}
                                    options={defaultOptions}
                                />
                            </div>
                        </div>
                    )}

                    {chartData.temperature.length > 0 && (
                        <div className="bg-white rounded-lg shadow p-6">
                            <h2 className="text-xl font-semibold text-gray-900 mb-4">Temperature</h2>
                            <div className="h-64">
                                <Bar
                                    data={{
                                        labels: chartData.labels.slice(-30),
                                        datasets: [{
                                            label: 'Temperature (°F)',
                                            data: chartData.temperature.slice(-30),
                                            backgroundColor: colors.warning,
                                        }],
                                    }}
                                    options={defaultOptions}
                                />
                            </div>
                        </div>
                    )}

                    {chartData.pulse.length > 0 && (
                        <div className="bg-white rounded-lg shadow p-6">
                            <h2 className="text-xl font-semibold text-gray-900 mb-4">Heart Rate</h2>
                            <div className="h-64">
                                <Line
                                    data={{
                                        labels: chartData.labels.slice(-30),
                                        datasets: [{
                                            label: 'Pulse (bpm)',
                                            data: chartData.pulse.slice(-30),
                                            borderColor: colors.success,
                                            backgroundColor: colors.success + '20',
                                            fill: true,
                                        }],
                                    }}
                                    options={defaultOptions}
                                />
                            </div>
                        </div>
                    )}
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow p-12 text-center">
                    <Activity className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                    <p className="text-gray-600 text-lg font-medium">No vitals data available</p>
                </div>
            )}
        </div>
    );
}
