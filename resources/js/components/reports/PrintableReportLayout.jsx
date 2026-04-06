import React from 'react';
import { Printer } from 'lucide-react';
import ReportBrandedBanner from './ReportBrandedBanner';

/**
 * Print button to place beside Export/Refresh on report pages.
 */
export function ReportPrintButton() {
    return (
        <button
            type="button"
            onClick={() => window.print()}
            className="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition no-print"
        >
            <Printer className="h-4 w-4" />
            Print
        </button>
    );
}

/**
 * Standard layout for printable reports and charts: facility branding banner + content.
 * Uses the same palette rules as PDF exports (App\Support\ReportBranding).
 */
export default function PrintableReportLayout({ title, subtitle, branchName, children }) {
    return (
        <div className="print-report min-h-screen bg-slate-50/50">
            <div className="max-w-[1600px] mx-auto px-4 py-6 print:px-2 print:py-3">
                <ReportBrandedBanner reportTitle={title} subtitle={subtitle} branchName={branchName} />
                <div className="print-report-content bg-white rounded-xl shadow-sm border border-slate-200/80 p-4 sm:p-6 print:shadow-none print:border-0 print:p-0 print:bg-transparent">
                    {children}
                </div>
            </div>
        </div>
    );
}
