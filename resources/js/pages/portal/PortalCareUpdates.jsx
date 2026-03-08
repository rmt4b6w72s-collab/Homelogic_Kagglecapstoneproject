import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../services/api';
import { format } from 'date-fns';
import { FileText, Pill, Calendar, Heart } from 'lucide-react';

export default function PortalCareUpdates() {
  const [dateFrom, setDateFrom] = useState(format(new Date(Date.now() - 7 * 24 * 60 * 60 * 1000), 'yyyy-MM-dd'));
  const [dateTo, setDateTo] = useState(format(new Date(), 'yyyy-MM-dd'));

  const { data, isLoading } = useQuery({
    queryKey: ['family-care-updates', dateFrom, dateTo],
    queryFn: async () => {
      const res = await api.get('/family/care-updates', { params: { date_from: dateFrom, date_to: dateTo } });
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
      <h1 className="text-2xl font-bold text-gray-900 mb-2">Care Updates</h1>
      <p className="text-gray-600 mb-6">View care notes, medications, appointments, and vitals.</p>

      <div className="flex flex-wrap gap-4 mb-6">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">From</label>
          <input
            type="date"
            value={dateFrom}
            onChange={(e) => setDateFrom(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">To</label>
          <input
            type="date"
            value={dateTo}
            onChange={(e) => setDateTo(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2"
          />
        </div>
      </div>

      <div className="space-y-8">
        <section className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
            <FileText className="w-5 h-5" />
            Care notes
          </h2>
          {tLogs.length === 0 ? (
            <p className="text-gray-500 text-sm">No care notes in this range.</p>
          ) : (
            <ul className="space-y-4">
              {tLogs.map((t) => (
                <li key={t.id} className="border-b border-gray-100 pb-4 last:border-0">
                  <span className="text-gray-500 text-sm">{t.reported_on ? format(new Date(t.reported_on), 'MMM d, yyyy h:mm a') : ''}</span>
                  <p className="text-gray-900 mt-1">{t.summary || '—'}</p>
                </li>
              ))}
            </ul>
          )}
        </section>

        <section className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
            <Pill className="w-5 h-5" />
            Medication administrations (today)
          </h2>
          {meds.length === 0 ? (
            <p className="text-gray-500 text-sm">None recorded for today.</p>
          ) : (
            <ul className="space-y-2">
              {meds.map((m, i) => (
                <li key={i} className="flex justify-between text-sm">
                  <span>{m.medication_name}</span>
                  <span className="text-gray-500">{m.administered_at ? format(new Date(m.administered_at), 'h:mm a') : m.status}</span>
                </li>
              ))}
            </ul>
          )}
        </section>

        <section className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
            <Calendar className="w-5 h-5" />
            Upcoming appointments
          </h2>
          {appointments.length === 0 ? (
            <p className="text-gray-500 text-sm">No upcoming appointments.</p>
          ) : (
            <ul className="space-y-3">
              {appointments.map((a) => (
                <li key={a.id} className="text-sm">
                  <span className="font-medium">{a.resident_name}</span> — {a.appointment_date}
                  {a.appointment_time && ` at ${String(a.appointment_time).slice(0, 5)}`}
                  {a.provider_name && ` · ${a.provider_name}`}
                  {a.location && ` · ${a.location}`}
                </li>
              ))}
            </ul>
          )}
        </section>

        <section className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 className="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
            <Heart className="w-5 h-5" />
            Vitals (last 14 days)
          </h2>
          {vitals.length === 0 ? (
            <p className="text-gray-500 text-sm">No vitals in this period.</p>
          ) : (
            <ul className="space-y-2 text-sm">
              {vitals.map((v, i) => (
                <li key={i}>
                  {v.recorded_at ? format(new Date(v.recorded_at), 'MMM d, h:mm a') : ''}
                  {v.blood_pressure_systolic != null && ` · BP ${v.blood_pressure_systolic}/${v.blood_pressure_diastolic ?? '—'}`}
                  {v.heart_rate != null && ` · HR ${v.heart_rate}`}
                  {v.temperature != null && ` · Temp ${v.temperature}`}
                </li>
              ))}
            </ul>
          )}
        </section>
      </div>
    </div>
  );
}
