import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { 
    ClipboardList, 
    Filter,
    Eye,
    Calendar,
    User,
    CheckCircle2,
    Clock,
    FileText,
    Download,
    RefreshCw,
    X,
    AlertCircle
} from 'lucide-react';
import { formatPacificDate } from '../../utils/pacificTime';

export default function BehaviorChartsView() {
    const [branchId, setBranchId] = useState(null);
    const [residentId, setResidentId] = useState(null);
    const [month, setMonth] = useState(() => {
        const now = new Date();
        return String(now.getMonth() + 1).padStart(2, '0');
    });
    const [year, setYear] = useState(() => {
        return new Date().getFullYear().toString();
    });
    const [branches, setBranches] = useState([]);
    const [residents, setResidents] = useState([]);
    const [selectedChart, setSelectedChart] = useState(null);

    // Fetch branches and residents
    React.useEffect(() => {
        api.get('/branches', { params: { per_page: 100 } })
            .then(res => setBranches(res.data?.data || []))
            .catch(() => {});
    }, []);

    React.useEffect(() => {
        if (branchId) {
            api.get('/residents', { params: { per_page: 100, branch_id: branchId, is_active: 1 } })
                .then(res => setResidents(res.data?.data || []))
                .catch(() => {});
        } else {
            setResidents([]);
            setResidentId(null);
        }
    }, [branchId]);

    // Fetch behavior charts
    const { data: chartsData, isLoading, refetch } = useQuery({
        queryKey: ['behavior-charts', branchId, residentId, month, year],
        queryFn: async () => {
            const params = {
                per_page: 50,
                month: month,
                year: year,
            };
            if (branchId) params.branch_id = branchId;
            if (residentId) params.resident_id = residentId;
            const response = await api.get('/resident-charts', { params });
            return response.data;
        },
        enabled: !!(branchId && residentId), // Only fetch when both filters are selected
    });

    const charts = chartsData?.data || [];

    const handleViewChart = (chart) => {
        setSelectedChart(chart);
    };

    const handleCloseModal = () => {
        setSelectedChart(null);
    };

    const handleExport = () => {
        if (!charts.length) return;
        
        let csv = 'Date,Resident,Chart Status,Submitted At,Caregiver,Items Count,Logs Count\n';
        charts.forEach(chart => {
            csv += `${chart.chart_date},`;
            csv += `${chart.resident?.first_name || ''} ${chart.resident?.last_name || ''},`;
            csv += `${chart.status},`;
            csv += `${chart.submitted_at || 'N/A'},`;
            csv += `${chart.caregiver?.name || 'N/A'},`;
            csv += `${chart.items?.length || 0},`;
            csv += `${chart.logs?.length || 0}\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `behavior-charts-${year}-${month}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
    };

    const getStatusBadge = (status) => {
        if (status === 'submitted') {
            return (
                <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <CheckCircle2 className="w-3 h-3" />
                    Submitted
                </span>
            );
        }
        return (
            <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                <Clock className="w-3 h-3" />
                Draft
            </span>
        );
    };

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <ClipboardList className="w-8 h-8 text-[var(--theme-primary)]" />
                            Behavior Charts
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            View and manage caregiver-submitted behavior charts
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        {charts.length > 0 && (
                            <button
                                onClick={handleExport}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                            >
                                <Download className="h-4 w-4" />
                                Export
                            </button>
                        )}
                        <button
                            onClick={() => refetch()}
                            className="inline-flex items-center gap-2 px-4 py-2 bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] rounded-lg text-sm font-medium hover:bg-[var(--theme-primary-hover)] transition"
                        >
                            <RefreshCw className="h-4 w-4" />
                            Refresh
                        </button>
                    </div>
                </div>
            </div>

            {/* Filters */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div className="flex flex-wrap items-end gap-4">
                    <div className="flex-1 min-w-[200px]">
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            <Filter className="inline h-4 w-4 mr-1" />
                            Select Branch
                        </label>
                        <select
                            value={branchId || ''}
                            onChange={(e) => {
                                setBranchId(e.target.value || null);
                                setResidentId(null);
                            }}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent"
                        >
                            <option value="">All Branches</option>
                            {branches.map(b => (
                                <option key={b.id} value={b.id}>{b.name}</option>
                            ))}
                        </select>
                    </div>
                    <div className="flex-1 min-w-[200px]">
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            <Filter className="inline h-4 w-4 mr-1" />
                            Select Resident
                        </label>
                        <select
                            value={residentId || ''}
                            onChange={(e) => setResidentId(e.target.value || null)}
                            disabled={!branchId}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                        >
                            <option value="">Select Resident</option>
                            {residents.map(r => (
                                <option key={r.id} value={r.id}>
                                    {r.first_name} {r.last_name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex-1 min-w-[150px]">
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            <Filter className="inline h-4 w-4 mr-1" />
                            Month
                        </label>
                        <select
                            value={month}
                            onChange={(e) => setMonth(e.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent"
                        >
                            <option value="01">January</option>
                            <option value="02">February</option>
                            <option value="03">March</option>
                            <option value="04">April</option>
                            <option value="05">May</option>
                            <option value="06">June</option>
                            <option value="07">July</option>
                            <option value="08">August</option>
                            <option value="09">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <div className="flex-1 min-w-[120px]">
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            <Filter className="inline h-4 w-4 mr-1" />
                            Year
                        </label>
                        <input
                            type="number"
                            value={year}
                            onChange={(e) => setYear(e.target.value)}
                            min="2020"
                            max="2099"
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent"
                        />
                    </div>
                </div>
            </div>

            {/* Charts Grid */}
            {!branchId || !residentId ? (
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <Filter className="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p className="text-gray-600 font-medium">Please select a branch and resident to view behavior charts</p>
                </div>
            ) : isLoading ? (
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--theme-primary)]"></div>
                    <p className="mt-4 text-gray-600">Loading charts...</p>
                </div>
            ) : charts.length === 0 ? (
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <ClipboardList className="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p className="text-gray-600 font-medium">No behavior charts found for the selected filters</p>
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {charts.map((chart) => (
                        <div key={chart.id} className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                            <div className="flex items-start justify-between mb-4">
                                <div className="flex-1">
                                    <div className="flex items-center gap-2 mb-2">
                                        <Calendar className="w-4 h-4 text-gray-400" />
                                        <span className="text-sm font-medium text-gray-900">
                                            {formatPacificDate(chart.chart_date)}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-2 mb-2">
                                        <User className="w-4 h-4 text-gray-400" />
                                        <span className="text-sm text-gray-700">
                                            {chart.resident?.first_name} {chart.resident?.last_name}
                                        </span>
                                    </div>
                                    {chart.caregiver && (
                                        <p className="text-xs text-gray-500">
                                            Submitted by: {chart.caregiver.name}
                                        </p>
                                    )}
                                </div>
                                {getStatusBadge(chart.status)}
                            </div>

                            <div className="space-y-2 mb-4">
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-gray-600">Items:</span>
                                    <span className="font-medium text-gray-900">{chart.items?.length || 0}</span>
                                </div>
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-gray-600">Logs:</span>
                                    <span className="font-medium text-gray-900">{chart.logs?.length || 0}</span>
                                </div>
                                {chart.submitted_at && (
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="text-gray-600">Submitted:</span>
                                        <span className="font-medium text-gray-900">
                                            {new Date(chart.submitted_at).toLocaleString()}
                                        </span>
                                    </div>
                                )}
                            </div>

                            <button
                                onClick={() => handleViewChart(chart)}
                                className="w-full px-4 py-2 bg-[var(--theme-primary)] text-white rounded-lg hover:bg-[var(--theme-primary-hover)] transition-colors flex items-center justify-center gap-2 text-sm font-medium"
                            >
                                <Eye className="w-4 h-4" />
                                View Details
                            </button>
                        </div>
                    ))}
                </div>
            )}

            {/* Chart Detail Modal */}
            {selectedChart && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm overflow-y-auto">
                    <div className="bg-white w-full max-w-6xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-200">
                        {/* Header */}
                        <div className="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                            <div>
                                <h2 className="text-xl font-bold text-gray-900 flex items-center gap-3">
                                    <ClipboardList className="w-6 h-6 text-[var(--theme-primary)]" />
                                    Behavior Chart Details
                                </h2>
                                <p className="text-sm text-gray-600 mt-1">
                                    {selectedChart.resident?.first_name} {selectedChart.resident?.last_name} - {formatPacificDate(selectedChart.chart_date)}
                                </p>
                            </div>
                            <button 
                                onClick={handleCloseModal}
                                className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors"
                            >
                                <X className="w-6 h-6" />
                            </button>
                        </div>

                        {/* Content */}
                        <div className="flex-1 overflow-y-auto p-6 space-y-6">
                            {/* Chart Info */}
                            <div className="bg-gray-50 rounded-xl p-4">
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <p className="text-xs text-gray-500 mb-1">Status</p>
                                        <div>{getStatusBadge(selectedChart.status)}</div>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500 mb-1">Submitted By</p>
                                        <p className="text-sm font-medium text-gray-900">
                                            {selectedChart.caregiver?.name || 'N/A'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500 mb-1">Submitted At</p>
                                        <p className="text-sm font-medium text-gray-900">
                                            {selectedChart.submitted_at 
                                                ? new Date(selectedChart.submitted_at).toLocaleString()
                                                : 'N/A'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-500 mb-1">Total Items</p>
                                        <p className="text-sm font-medium text-gray-900">
                                            {selectedChart.items?.length || 0}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Behavior Items */}
                            <div>
                                <h3 className="text-lg font-bold text-[var(--theme-primary)] mb-4 flex items-center gap-2">
                                    <FileText className="w-5 h-5" />
                                    Behavior Checklist
                                </h3>
                                <div className="overflow-hidden border border-gray-200 rounded-xl shadow-sm">
                                    <table className="w-full text-left border-collapse">
                                        <thead className="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                                            <tr>
                                                <th className="px-4 py-3 font-bold border-b border-gray-200 border-r border-gray-200">Category</th>
                                                <th className="px-4 py-3 font-bold border-b border-gray-200 border-r border-gray-200">Behavior</th>
                                                <th className="px-4 py-3 font-bold border-b border-gray-200">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody className="text-gray-700">
                                            {selectedChart.items && selectedChart.items.length > 0 ? (
                                                (() => {
                                                    // Group items by category
                                                    const grouped = {};
                                                    selectedChart.items.forEach(item => {
                                                        const categoryName = item.definition?.category?.name || 'Other';
                                                        if (!grouped[categoryName]) {
                                                            grouped[categoryName] = [];
                                                        }
                                                        grouped[categoryName].push(item);
                                                    });

                                                    return Object.entries(grouped).map(([catName, items]) =>
                                                        items.map((item, idx) => (
                                                            <tr key={item.id} className="border-b border-gray-100 hover:bg-gray-50/50">
                                                                {idx === 0 && (
                                                                    <td 
                                                                        className="px-4 py-3 border-r border-gray-200 align-middle font-bold text-gray-900 bg-gray-50/30" 
                                                                        rowSpan={items.length}
                                                                    >
                                                                        {catName}
                                                                    </td>
                                                                )}
                                                                <td className="px-4 py-3 border-r border-gray-200 font-medium">
                                                                    {item.definition?.name || 'Unknown'}
                                                                </td>
                                                                <td className="px-4 py-3">
                                                                    <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium ${
                                                                        item.value 
                                                                            ? 'bg-green-100 text-green-800' 
                                                                            : 'bg-red-100 text-red-800'
                                                                    }`}>
                                                                        {item.value ? 'Yes' : 'No'}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        ))
                                                    );
                                                })()
                                            ) : (
                                                <tr>
                                                    <td colSpan="3" className="px-4 py-12 text-center text-gray-400 italic">
                                                        No behavior items recorded
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {/* Detailed Logs */}
                            {selectedChart.logs && selectedChart.logs.length > 0 && (
                                <div>
                                    <h3 className="text-lg font-bold text-[var(--theme-primary)] mb-4 flex items-center gap-2">
                                        <AlertCircle className="w-5 h-5" />
                                        Detailed Incident Logs
                                    </h3>
                                    <div className="overflow-x-auto border border-gray-200 rounded-xl shadow-sm">
                                        <table className="w-full text-left border-collapse min-w-[1000px]">
                                            <thead className="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                                                <tr>
                                                    <th className="px-4 py-3 font-bold border-b border-gray-200 border-r border-gray-200">Time Occurred</th>
                                                    <th className="px-4 py-3 font-bold border-b border-gray-200 border-r border-gray-200">Behavior Description</th>
                                                    <th className="px-4 py-3 font-bold border-b border-gray-200 border-r border-gray-200">Triggers</th>
                                                    <th className="px-4 py-3 font-bold border-b border-gray-200 border-r border-gray-200">Intervention</th>
                                                    <th className="px-4 py-3 font-bold border-b border-gray-200 border-r border-gray-200">Reported to Provider</th>
                                                    <th className="px-4 py-3 font-bold border-b border-gray-200">Outcome</th>
                                                </tr>
                                            </thead>
                                            <tbody className="text-gray-700">
                                                {selectedChart.logs.map((log, index) => (
                                                    <tr key={index} className="border-b border-gray-100 hover:bg-gray-50/50">
                                                        <td className="p-3 border-r border-gray-200">
                                                            {new Date(log.occurred_at).toLocaleString()}
                                                        </td>
                                                        <td className="p-3 border-r border-gray-200">
                                                            <p className="text-sm whitespace-pre-wrap">{log.behavior_description || '-'}</p>
                                                        </td>
                                                        <td className="p-3 border-r border-gray-200">
                                                            <p className="text-sm whitespace-pre-wrap">{log.triggers || '-'}</p>
                                                        </td>
                                                        <td className="p-3 border-r border-gray-200">
                                                            <p className="text-sm whitespace-pre-wrap">{log.caregiver_intervention || '-'}</p>
                                                        </td>
                                                        <td className="p-3 border-r border-gray-200">
                                                            <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium ${
                                                                log.reported_to_provider 
                                                                    ? 'bg-green-100 text-green-800' 
                                                                    : 'bg-gray-100 text-gray-800'
                                                            }`}>
                                                                {log.reported_to_provider ? 'Yes' : 'No'}
                                                            </span>
                                                        </td>
                                                        <td className="p-3">
                                                            <p className="text-sm whitespace-pre-wrap">{log.outcome || '-'}</p>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Footer */}
                        <div className="p-6 border-t border-gray-100 bg-gray-50/50 flex justify-end">
                            <button
                                onClick={handleCloseModal}
                                className="px-6 py-2.5 bg-[var(--theme-primary)] text-white rounded-lg font-semibold hover:bg-[var(--theme-primary-hover)] transition-colors"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

