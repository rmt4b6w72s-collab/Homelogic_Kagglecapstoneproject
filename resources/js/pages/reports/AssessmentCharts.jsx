import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { Bar, Doughnut, Line } from 'react-chartjs-2';
import { defaultOptions, colors } from '../../utils/chartConfig';

export default function AssessmentCharts() {
    const { data, isLoading } = useQuery({
        queryKey: ['charts-assessments'],
        queryFn: async () => (await api.get('/charts/assessments')).data,
    });

    if (isLoading) {
        return (
            <div className="text-center py-12">
                <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[#2D5016]"></div>
                <p className="mt-4 text-gray-600">Loading assessment charts...</p>
            </div>
        );
    }

    return (
        <div>
            <h1 className="text-3xl font-bold text-gray-900 mb-6">Assessment Charts</h1>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Total Assessments</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.total_assessments || 0}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Completed</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.completed_assessments || 0}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Pending</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.pending_assessments || 0}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">This Month</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{data?.this_month || 0}</p>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-xl font-semibold text-gray-900 mb-4">Assessments by Type</h2>
                    <div className="h-64">
                        {data?.by_type?.length ? (
                            <Bar
                                data={{
                                    labels: data.by_type.map(t => t.assessment_type || 'Unknown'),
                                    datasets: [{
                                        label: 'Count',
                                        data: data.by_type.map(t => t.count),
                                        backgroundColor: colors.primary,
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
                    <h2 className="text-xl font-semibold text-gray-900 mb-4">Completion Trends (Last 7 Days)</h2>
                    <div className="h-64">
                        {data?.completion_trends?.length ? (
                            <Line
                                data={{
                                    labels: data.completion_trends.map(t => t.date),
                                    datasets: [{
                                        label: 'Assessments',
                                        data: data.completion_trends.map(t => t.count),
                                        borderColor: colors.success,
                                        backgroundColor: colors.success + '20',
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
