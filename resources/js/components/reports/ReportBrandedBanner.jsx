import React from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { buildReportPalette } from '../../utils/reportBranding';

/**
 * Facility-branded header card: logo, name, address, phone, branch.
 * Used by printable reports and key report pages for consistent branding.
 */
export default function ReportBrandedBanner({
    reportTitle,
    subtitle,
    branchName: branchNameProp,
    className = '',
}) {
    const { data: user } = useQuery({
        queryKey: ['current-user'],
        queryFn: async () => (await api.get('/user')).data,
    });

    const branding = user?.facility_branding;
    const ctx = user?.report_context;
    const p = buildReportPalette(branding || {});

    const facilityName = ctx?.facility_name || branding?.name || 'Facility';
    const defaultBranch =
        branchNameProp !== undefined && branchNameProp !== null && branchNameProp !== ''
            ? branchNameProp
            : ctx?.branch_name || user?.assigned_branch?.name || 'All branches';
    const address = ctx?.facility_address || '';
    const phone = ctx?.facility_phone || '';
    const logoSrc = branding?.logo || '/images/logonew.png';

    const generatedAt = new Date().toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });

    return (
        <div
            className={`report-brand-card rounded-lg border overflow-hidden mb-6 print:mb-4 ${className}`}
            style={{
                background: p.headerTint,
                borderColor: p.brandBorder,
                borderLeftWidth: '4px',
                borderLeftStyle: 'solid',
                borderLeftColor: p.primaryColor,
                WebkitPrintColorAdjust: 'exact',
                printColorAdjust: 'exact',
            }}
        >
            <div className="flex flex-col sm:flex-row sm:items-stretch gap-4 p-4 sm:p-5">
                <div className="flex-shrink-0 flex justify-center sm:justify-start">
                    <img
                        src={logoSrc}
                        alt=""
                        className="h-14 w-auto max-w-[160px] object-contain"
                        onError={(e) => {
                            e.target.style.display = 'none';
                        }}
                    />
                </div>
                <div className="flex-1 min-w-0 text-center sm:text-left">
                    {reportTitle && (
                        <h1
                            className="text-lg sm:text-xl font-bold tracking-tight mb-1"
                            style={{ color: p.primaryColor }}
                        >
                            {reportTitle}
                        </h1>
                    )}
                    <p className="text-sm font-semibold" style={{ color: p.primaryColor }}>
                        {facilityName}
                        {defaultBranch && defaultBranch !== 'All branches' ? ` — ${defaultBranch}` : ''}
                    </p>
                    {address ? <p className="text-xs text-slate-600 mt-1">{address}</p> : null}
                    {phone ? <p className="text-xs text-slate-600">Phone: {phone}</p> : null}
                    {subtitle && <p className="text-xs text-slate-700 mt-2 font-medium">{subtitle}</p>}
                    <div className="mt-3 flex flex-wrap items-center justify-center sm:justify-start gap-2 text-xs text-slate-500">
                        <span
                            className="inline-block px-2.5 py-1 rounded font-semibold text-xs"
                            style={{
                                background: p.primaryColor,
                                color: p.accentColor,
                                border: `1px solid ${p.secondaryColor}`,
                                WebkitPrintColorAdjust: 'exact',
                                printColorAdjust: 'exact',
                            }}
                        >
                            Generated {generatedAt}
                        </span>
                        {user?.name && <span>By {user.name}</span>}
                    </div>
                </div>
            </div>
        </div>
    );
}
