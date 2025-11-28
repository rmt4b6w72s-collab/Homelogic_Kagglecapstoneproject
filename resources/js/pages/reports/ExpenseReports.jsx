import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { BarChart3, TrendingUp, DollarSign, Calendar, CheckCircle, AlertCircle } from 'lucide-react';
import ModuleProtectedRoute from '../../components/ModuleProtectedRoute';

function ExpenseReports() {
  const [startDate, setStartDate] = useState(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0]);
  const [endDate, setEndDate] = useState(new Date().toISOString().split('T')[0]);

  const { data: summary } = useQuery({
    queryKey: ['expense-reports-summary', startDate, endDate],
    queryFn: async () => {
      const res = await api.get('/billing/reports/summary', { params: { start_date: startDate, end_date: endDate } });
      return res.data;
    },
  });

  const { data: byCategory } = useQuery({
    queryKey: ['expense-reports-category', startDate, endDate],
    queryFn: async () => {
      const res = await api.get('/billing/reports/by-category', { params: { start_date: startDate, end_date: endDate } });
      return res.data;
    },
  });

  const { data: byDateRange } = useQuery({
    queryKey: ['expense-reports-daterange', startDate, endDate],
    queryFn: async () => {
      const res = await api.get('/billing/reports/by-date-range', { params: { start_date: startDate, end_date: endDate, group_by: 'day' } });
      return res.data;
    },
  });

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount || 0);
  };

  return (
    <div>
      <div className="bg-white rounded-lg shadow p-6 mb-6">
        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
          <div>
            <h2 className="text-xl font-semibold text-gray-900 mb-2">Expense Reports</h2>
            <p className="text-gray-600">View expense analytics and reports.</p>
          </div>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
            <input
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)]"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">End Date</label>
            <input
              type="date"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)]"
            />
          </div>
        </div>
      </div>

      {summary?.data && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Expenses</p>
                <p className="text-2xl font-bold text-gray-900 mt-2">{formatCurrency(summary.data.total_expenses)}</p>
              </div>
              <DollarSign className="w-8 h-8 text-blue-500" />
            </div>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Paid</p>
                <p className="text-2xl font-bold text-green-600 mt-2">{formatCurrency(summary.data.total_paid)}</p>
              </div>
              <CheckCircle className="w-8 h-8 text-green-500" />
            </div>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Pending</p>
                <p className="text-2xl font-bold text-yellow-600 mt-2">{formatCurrency(summary.data.total_pending)}</p>
              </div>
              <Calendar className="w-8 h-8 text-yellow-500" />
            </div>
          </div>
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Overdue</p>
                <p className="text-2xl font-bold text-red-600 mt-2">{formatCurrency(summary.data.total_overdue)}</p>
              </div>
              <AlertCircle className="w-8 h-8 text-red-500" />
            </div>
          </div>
        </div>
      )}

      {byCategory?.data && (
        <div className="bg-white rounded-lg shadow p-6 mb-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Expenses by Category</h3>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Count</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {byCategory.data.map((item, idx) => (
                  <tr key={idx} className="hover:bg-gray-50">
                    <td className="px-6 py-4 text-sm font-medium text-gray-900">{item.category_name}</td>
                    <td className="px-6 py-4 text-sm text-gray-500">{item.category_type}</td>
                    <td className="px-6 py-4 text-sm text-right font-medium text-gray-900">{formatCurrency(item.total_amount)}</td>
                    <td className="px-6 py-4 text-sm text-right text-gray-500">{item.count}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}

export default function ExpenseReportsPage() {
  return (
    <ModuleProtectedRoute module="billing_expenses">
      <ExpenseReports />
    </ModuleProtectedRoute>
  );
}

