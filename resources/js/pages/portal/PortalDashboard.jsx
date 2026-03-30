import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../../services/api';
import { format } from 'date-fns';
import { FileText, Pill, Calendar, Heart, ChevronRight, MapPin, AlertCircle } from 'lucide-react';

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

  const residents = data?.residents ?? [];
  const linkedIds = data?.linked_resident_ids;
  const tLogs = data?.t_logs ?? [];
  const meds = data?.medication_administrations ?? [];
  const appointments = data?.appointments ?? [];
  const vitals = data?.vitals_summary ?? [];
  // Prefer server-linked IDs from ResidentContact; "residents" payload can be empty if FacilityScope hid them before the fix.
  const notLinked = Array.isArray(linkedIds) ? linkedIds.length === 0 : residents.length === 0;

  return (
    <div className="max-w-4xl mx-auto">
      <h1 className="text-2xl font-bold text-gray-900 mb-2">Dashboard</h1>
      <p className="text-gray-600 mb-6">Summary of care updates for your loved one(s).</p>

      {notLinked ? (
        <div className="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 flex gap-3 text-sm text-amber-950">
          <AlertCircle className="w-5 h-5 shrink-0 text-amber-600" />
          <div>
            <p className="font-medium">No resident linked to this account yet</p>
            <p className="text-amber-900/90 mt-1">
              Ask your care home to send a family portal invite to your email, or accept the invite link if you already received one. Until you are linked, medications and appointments will not appear here.
            </p>
          </div>
        </div>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 mb-8">
          {residents.map((r) => (
            <div
              key={r.id}
              className="flex gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
            >
              <div className="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-gray-100">
                {r.profile_image_url ? (
                  <img src={r.profile_image_url} alt="" className="h-full w-full object-cover" />
                ) : (
                  <div className="flex h-full w-full items-center justify-center text-2xl font-semibold text-gray-400">
                    {(r.first_name?.[0] || r.name?.[0] || '?').toUpperCase()}
                  </div>
                )}
              </div>
              <div className="min-w-0 flex-1">
                <p className="font-semibold text-gray-900 truncate">{r.name}</p>
                {(r.room || r.room_number) && (
                  <p className="mt-1 flex items-center gap-1 text-sm text-gray-600">
                    <MapPin className="w-3.5 h-3.5 shrink-0" />
                    Room {r.room_number || r.room}
                  </p>
                )}
                {r.branch_name && <p className="text-sm text-gray-500">{r.branch_name}</p>}
                {r.admission_date && (
                  <p className="text-xs text-gray-400 mt-1">Admitted {r.admission_date}</p>
                )}
                {(r.dietary_restrictions || r.special_instructions) && (
                  <div className="mt-2 text-xs text-gray-600 space-y-1">
                    {r.dietary_restrictions ? (
                      <p>
                        <span className="font-medium text-gray-700">Diet: </span>
                        {r.dietary_restrictions}
                      </p>
                    ) : null}
                    {r.special_instructions ? (
                      <p>
                        <span className="font-medium text-gray-700">Notes: </span>
                        {r.special_instructions}
                      </p>
                    ) : null}
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

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
                <li key={i} className="text-sm flex justify-between gap-2">
                  <span className="text-gray-900 min-w-0">
                    {residents.length > 1 && (
                      <span className="text-gray-500 block text-xs">
                        {residents.find((x) => x.id === m.resident_id)?.name || 'Resident'}
                      </span>
                    )}
                    {m.medication_name}
                  </span>
                  <span className="text-gray-500 shrink-0">{m.administered_at ? format(new Date(m.administered_at), 'h:mm a') : ''}</span>
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
                <li key={a.id} className="text-sm border-b border-gray-50 pb-2 last:border-0 last:pb-0">
                  <span className="font-medium text-gray-900">{a.title || a.appointment_type || 'Appointment'}</span>
                  {a.status && (
                    <span className="ml-2 rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 capitalize">{a.status}</span>
                  )}
                  <div className="text-gray-600 mt-0.5">
                    <span className="font-medium text-gray-800">{a.resident_name}</span>
                    <span className="text-gray-500">
                      {' '}
                      — {a.appointment_date}
                      {a.appointment_time ? ` at ${String(a.appointment_time).slice(0, 5)}` : ''}
                    </span>
                    {a.appointment_type && a.title !== a.appointment_type && (
                      <span className="text-gray-500"> · {a.appointment_type}</span>
                    )}
                  </div>
                  {a.provider_name && <span className="text-gray-500 text-xs">Provider: {a.provider_name}</span>}
                  {a.location && <span className="text-gray-500 text-xs block">{a.location}</span>}
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
