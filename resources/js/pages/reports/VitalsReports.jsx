import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { Download, FileText, Calendar } from 'lucide-react';

export default function VitalsReports() {
    const [dateFrom, setDateFrom] = useState(new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]);
    const [dateTo, setDateTo] = useState(new Date().toISOString().split('T')[0]);

    const { data, isLoading } = useQuery({
        queryKey: ['vitals-report', dateFrom, dateTo],
        queryFn: async () => {
            const params = {
                date_from: dateFrom,
                date_to: dateTo,
                per_page: 1000,
            };
            return (await api.get('/vitals', { params })).data;
        },
    });

    if (isLoading) {
        return (
            <div className="text-center py-12">
                <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[#2D5016]"></div>
                <p className="mt-4 text-gray-600">Loading vitals report...</p>
            </div>
        );
    }

    const vitals = data?.data || [];
    const stats = {
        total: vitals.length,
        withBP: vitals.filter(v => v.systolic && v.diastolic).length,
        withTemp: vitals.filter(v => v.temperature).length,
        withPulse: vitals.filter(v => v.pulse).length,
    };

    return (
        <div>
            <h1 className="text-2xl md:text-3xl font-bold text-gray-900 mb-4 md:mb-6">Vitals Reports</h1>

            <div className="bg-white rounded-lg shadow p-6 mb-6">
                <h2 className="text-lg font-semibold text-gray-900 mb-4">Report Filters</h2>
                <div className="grid grid-cols-2 gap-4 max-w-md">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                        <input
                            type="date"
                            value={dateFrom}
                            onChange={(e) => setDateFrom(e.target.value)}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D5016] focus:border-transparent"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                        <input
                            type="date"
                            value={dateTo}
                            onChange={(e) => setDateTo(e.target.value)}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D5016] focus:border-transparent"
                        />
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">Total Records</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{stats.total}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">With BP</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{stats.withBP}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">With Temperature</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{stats.withTemp}</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <p className="text-gray-600 text-sm font-medium">With Pulse</p>
                    <p className="text-3xl font-bold text-gray-900 mt-2">{stats.withPulse}</p>
                </div>
            </div>

            <div className="bg-white rounded-lg shadow p-6 mb-6">
                <div className="flex items-center justify-between mb-4">
                    <h2 className="text-lg font-semibold text-gray-900">Vitals Data</h2>
                    <button
                        onClick={() => {
                            // Export functionality
                            alert('Export functionality coming soon');
                        }}
                        className="px-4 py-2 bg-[#2D5016] text-white rounded-lg hover:bg-[#1a3009] transition-colors flex items-center space-x-2"
                    >
                        <Download className="w-4 h-4" />
                        <span>Export Report</span>
                    </button>
                </div>
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Resident</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">BP</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Temp</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pulse</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {vitals.slice(0, 20).map((vital) => (
                                <tr key={vital.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {new Date(vital.measurement_date).toLocaleDateString()}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {vital.resident?.first_name} {vital.resident?.last_name}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {vital.systolic && vital.diastolic ? `${vital.systolic}/${vital.diastolic}` : '-'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {vital.temperature ? `${vital.temperature}°F` : '-'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {vital.pulse ? `${vital.pulse} bpm` : '-'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {vitals.length === 0 && (
                        <div className="p-12 text-center">
                            <FileText className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                            <p className="text-gray-600 text-lg font-medium">No vitals data found</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
