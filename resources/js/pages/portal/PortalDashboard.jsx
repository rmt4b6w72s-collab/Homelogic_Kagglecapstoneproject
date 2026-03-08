import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../services/api';
import { format } from 'date-fns';
import { FileText, Pill, Calendar, Heart, ChevronRight } from 'lucide-react';

export default function PortalDashboard() {
  const { data, isLoading } = useQuery({
    queryKey: ['family-care-updates'],
    queryFn: async () => {
      const res = await api.get('/family/care-updates');
      return res.data;
    },
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--theme-primary)]" />
      </div>
    );
  }

  const tLogs = data?.t_logs ?? [];
  const meds = data?.medication_administrations ?? [];
  const appointments = data?.appointments ?? [];
  const vitals = data?.vitals_summary ?? [];

  return (
    <div className="max-w-4xl mx-auto">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">Dashboard</h1>
      <p className="text-gray-600 mb-8">Summary of care updates for your loved one(s).</p>

      <div className="grid gap-6">
        <section className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <FileText className="w-5 h-5" />
              Recent care notes
            </h2>
            <Link to="/portal/care-updates" className="text-sm text-[var(--theme-primary)] hover:underline flex items-center gap-1">
              View all <ChevronRight className="w-4 h-4" />
            </Link>
          </div>
          {tLogs.length === 0 ? (
            <p className="text-gray-500 text-sm">No recent care notes.</p>
          ) : (
            <ul className="space-y-3">
              {tLogs.slice(0, 5).map((t) => (
                <li key={t.id} className="text-sm border-b border-gray-100 pb-2 last:border-0">
                  <span className="text-gray-500">{t.reported_on ? format(new Date(t.reported_on), 'MMM d, h:mm a') : ''}</span>
                  <p className="text-gray-900 mt-0.5">{t.summary || '—'}</p>
                </li>
              ))}
            </ul>
          )}
        </section>

        <section className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <Pill className="w-5 h-5" />
              Today&apos;s medications
            </h2>
            <Link to="/portal/care-updates" className="text-sm text-[var(--theme-primary)] hover:underline flex items-center gap-1">
              View all <ChevronRight className="w-4 h-4" />
            </Link>
          </div>
          {meds.length === 0 ? (
            <p className="text-gray-500 text-sm">No medications recorded for today.</p>
          ) : (
            <ul className="space-y-2">
              {meds.slice(0, 8).map((m, i) => (
                <li key={i} className="text-sm flex justify-between">
                  <span className="text-gray-900">{m.medication_name}</span>
                  <span className="text-gray-500">{m.administered_at ? format(new Date(m.administered_at), 'h:mm a') : ''}</span>
                </li>
              ))}
            </ul>
          )}
        </section>

        <section className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <Calendar className="w-5 h-5" />
              Upcoming appointments
            </h2>
            <Link to="/portal/care-updates" className="text-sm text-[var(--theme-primary)] hover:underline flex items-center gap-1">
              View all <ChevronRight className="w-4 h-4" />
            </Link>
          </div>
          {appointments.length === 0 ? (
            <p className="text-gray-500 text-sm">No upcoming appointments.</p>
          ) : (
            <ul className="space-y-2">
              {appointments.slice(0, 5).map((a) => (
                <li key={a.id} className="text-sm">
                  <span className="font-medium text-gray-900">{a.resident_name}</span>
                  <span className="text-gray-500"> — {a.appointment_date} {a.appointment_time ? String(a.appointment_time).slice(0, 5) : ''}</span>
                  {a.provider_name && <span className="text-gray-500"> with {a.provider_name}</span>}
                </li>
              ))}
            </ul>
          )}
        </section>

        <section className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
            <Heart className="w-5 h-5" />
            Recent vitals
          </h2>
          {vitals.length === 0 ? (
            <p className="text-gray-500 text-sm">No recent vitals.</p>
          ) : (
            <ul className="space-y-2 text-sm">
              {vitals.slice(0, 5).map((v, i) => (
                <li key={i} className="text-gray-700">
                  {v.recorded_at ? format(new Date(v.recorded_at), 'MMM d, h:mm a') : ''}
                  {v.blood_pressure_systolic != null && ` — BP ${v.blood_pressure_systolic}/${v.blood_pressure_diastolic ?? '—'}`}
                  {v.heart_rate != null && ` — HR ${v.heart_rate}`}
                  {v.temperature != null && ` — Temp ${v.temperature}`}
                </li>
              ))}
            </ul>
          )}
        </section>
      </div>
    </div>
  );
}
