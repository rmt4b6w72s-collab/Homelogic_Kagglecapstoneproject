import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../services/api';

/**
 * Reusable filter component for chart pages
 * Provides branch and resident filters
 */
export default function ChartFilters({ 
    branchId, 
    setBranchId, 
    residentId, 
    setResidentId,
    dateRange,
    setDateRange
}) {
    // Fetch branches
    const { data: branchesData } = useQuery({
        queryKey: ['branches-options'],
        queryFn: async () => (await api.get('/branches', { params: { per_page: 100 } })).data,
    });

    // Fetch residents (filter by branch if selected)
    const { data: residentsData } = useQuery({
        queryKey: ['residents-options', branchId],
        queryFn: async () => {
            const params = { per_page: 100 };
            if (branchId) params.branch_id = branchId;
            return (await api.get('/residents', { params })).data;
        },
    });

    return (
        <div className="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden mb-6">
            <div className="p-4 md:p-6">
                <h2 className="text-lg font-semibold text-[#2D5016] mb-4">Filters</h2>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {/* Branch Filter */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Branch
                        </label>
                        <select
                            value={branchId || ''}
                            onChange={(e) => {
                                setBranchId(e.target.value || null);
                                setResidentId(null); // Reset resident when branch changes
                            }}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D5016] focus:border-transparent"
                        >
                            <option value="">All Branches</option>
                            {branchesData?.data?.map((branch) => (
                                <option key={branch.id} value={branch.id}>
                                    {branch.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Resident Filter */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Resident
                        </label>
                        <select
                            value={residentId || ''}
                            onChange={(e) => setResidentId(e.target.value || null)}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2D5016] focus:border-transparent"
                            disabled={branchId && !residentsData?.data?.length}
                        >
                            <option value="">All Residents</option>
                            {residentsData?.data?.map((resident) => (
                                <option key={resident.id} value={resident.id}>
                                    {resident.first_name} {resident.last_name}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Date Range Filter */}
                    {dateRange !== undefined && setDateRange && (
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Date Range
                            </label>
                            <div className="flex space-x-2">
                                {['week', 'month', 'all'].map((range) => (
                                    <button
                                        key={range}
                                        type="button"
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
                    )}
                </div>
            </div>
        </div>
    );
}

