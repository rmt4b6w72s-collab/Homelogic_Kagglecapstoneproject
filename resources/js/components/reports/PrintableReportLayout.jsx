import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { Printer } from 'lucide-react';

/**
 * Standard layout for printable reports and charts.
 * Renders facility/branch header, report title, generated date, and a Print button.
 * Use with @media print styles so only this content prints (sidebar/nav hidden).
 */
export default function PrintableReportLayout({ title, subtitle, branchName, children }) {
    const { data: user } = useQuery({
        queryKey: ['current-user'],
        queryFn: async () => {
            const res = await api.get('/user');
            return res.data;
        },
    });

    const ctx = user?.report_context;
    const facilityName = ctx?.facility_name || user?.facility_branding?.name || 'Facility';
    const facilityAddress = ctx?.facility_address || user?.facility?.address || '';
    const facilityPhone = ctx?.facility_phone || user?.facility?.phone || '';
    const defaultBranchName = ctx?.branch_name || user?.assigned_branch?.name;
    const displayBranch = branchName !== undefined && branchName !== null && branchName !== ''
        ? branchName
        : (defaultBranchName || 'All branches');
    const generatedAt = new Date().toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });

    const handlePrint = () => {
        window.print();
    };

    return (
        <div className="print-report min-h-screen">
            {/* Standard report header - visible on screen and in print */}
            <header className="print-report-header bg-white border-b border-gray-200 px-4 py-4 mb-6 rounded-lg shadow-sm">
                <div className="max-w-7xl mx-auto flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div className="flex-1">
                        {user?.facility_branding?.logo && (
                            <img
                                src={user.facility_branding.logo}
                                alt=""
                                className="h-10 w-auto mb-2 print-report-logo"
                                style={{ maxHeight: 40 }}
                            />
                        )}
                        <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                            {facilityName}
                        </h2>
                        {(facilityAddress || facilityPhone) && (
                            <p className="text-xs text-gray-500 mt-0.5">
                                {[facilityAddress, facilityPhone].filter(Boolean).join(' • ')}
                            </p>
                        )}
                        <p className="text-xs text-gray-600 mt-1">
                            Branch: <span className="font-medium">{displayBranch}</span>
                        </p>
                    </div>
                    <div className="text-left sm:text-right">
                        <h1 className="text-xl font-bold text-gray-900">{title}</h1>
                        {subtitle && (
                            <p className="text-sm text-gray-600 mt-0.5">{subtitle}</p>
                        )}
                        <p className="text-xs text-gray-500 mt-2">
                            Generated: {generatedAt}
                        </p>
                    </div>
                </div>
                <div className="max-w-7xl mx-auto mt-4 flex justify-end no-print">
                    <button
                        type="button"
                        onClick={handlePrint}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] rounded-lg text-sm font-medium hover:bg-[var(--theme-primary-hover)] transition"
                    >
                        <Printer className="h-4 w-4" />
                        Print
                    </button>
                </div>
            </header>

            <div className="print-report-content">
                {children}
            </div>
        </div>
    );
}
