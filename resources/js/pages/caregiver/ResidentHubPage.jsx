import React from 'react';
import { useParams, useSearchParams, useNavigate, Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
    LayoutDashboard,
    Pill,
    FileText,
    ClipboardList,
    FolderOpen,
    Heart,
    Calendar,
    User,
    ArrowLeft,
    MapPin,
    AlertCircle,
    Activity,
    Save,
    Edit,
    X,
    Stethoscope,
    Phone,
    Building2,
    Moon,
} from 'lucide-react';
import api from '../../services/api';
import {
    calculateAgeFromPacificBirthDate,
    formatPacificCalendarMedium,
} from '../../utils/pacificTime';
import Breadcrumbs from '../../components/ui/Breadcrumbs';
import ResidentDocuments from '../../components/ResidentDocuments';
import ResidentMedicationsPage from './ResidentMedicationsPage';
import { isCaregiverRole } from '../../utils/userRoles';

// ─── Tab definitions ──────────────────────────────────────────────────────────

const HUB_TABS = [
    { id: 'overview',     label: 'Overview',     icon: LayoutDashboard },
    { id: 'medications',  label: 'Medications',  icon: Pill            },
    { id: 'notes',        label: 'Notes',        icon: FileText        },
    { id: 'care',         label: 'Care Plan',    icon: ClipboardList   },
    { id: 'documents',    label: 'Documents',    icon: FolderOpen      },
    { id: 'vitals',       label: 'Vitals',       icon: Heart           },
    { id: 'appointments', label: 'Appointments', icon: Calendar        },
    { id: 'profile',      label: 'Profile',      icon: User            },
];

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatCalDate(value) {
    if (!value) return null;
    try { return formatPacificCalendarMedium(value); } catch { return value; }
}

function formatPhone(value) {
    if (!value) return null;
    const cleaned = String(value).replace(/[^\d+]/g, '');
    if (cleaned.length === 10) return `(${cleaned.slice(0, 3)}) ${cleaned.slice(3, 6)}-${cleaned.slice(6)}`;
    return value;
}

function computeLengthOfStay(admissionDate) {
    if (!admissionDate) return null;
    const days = Math.round((new Date() - new Date(admissionDate)) / 86400000);
    if (days < 30) return `${days}d`;
    const months = Math.floor(days / 30);
    if (months < 12) return `${months}mo`;
    return `${Math.floor(months / 12)}yr`;
}

const codeStatusColor = (status = '') => {
    const s = status.toLowerCase();
    if (s.includes('full')) return 'bg-red-500';
    if (s.includes('dnr')) return 'bg-amber-500';
    if (s.includes('comfort')) return 'bg-blue-500';
    return 'bg-gray-400';
};

// ─── Left profile panel ───────────────────────────────────────────────────────

function ResidentLeftPanel({ resident, residentId, navigate }) {
    if (!resident) return null;

    const fullName = [resident.first_name, resident.middle_names, resident.last_name]
        .filter(Boolean).join(' ');
    const initials = [resident.first_name?.[0], resident.last_name?.[0]]
        .filter(Boolean).join('').toUpperCase();
    const age = resident.date_of_birth
        ? calculateAgeFromPacificBirthDate(resident.date_of_birth) : null;
    const room = resident.room_number || resident.room;
    const allergies = Array.isArray(resident.allergies)
        ? resident.allergies.join(', ')
        : (resident.allergies || null);

    const isActive = resident.is_active !== false && resident.is_active !== 0;

    return (
        <aside
            className="bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] rounded-xl overflow-hidden flex-shrink-0 w-full lg:w-52 xl:w-56 lg:sticky lg:top-4 self-start"
            aria-label="Resident profile"
        >
            {/* Avatar section */}
            <div className="flex flex-col items-center px-4 pt-6 pb-4 border-b border-white/10">
                <div className="relative w-24 h-24 rounded-full overflow-hidden border-4 border-white/30 bg-white/10 mb-3">
                    {resident.profile_image_url || resident.profile_image ? (
                        <img
                            src={resident.profile_image_url || `/storage/${resident.profile_image}`}
                            alt={fullName}
                            className="w-full h-full object-cover"
                            onError={e => {
                                e.target.style.display = 'none';
                                e.target.nextElementSibling.style.display = 'flex';
                            }}
                        />
                    ) : null}
                    <div className={`absolute inset-0 ${resident.profile_image_url || resident.profile_image ? 'hidden' : 'flex'} items-center justify-center text-2xl font-bold text-[var(--theme-primary)] bg-white`}>
                        {initials || <User className="w-10 h-10" />}
                    </div>
                </div>

                {/* Status dot */}
                <div className={`w-2.5 h-2.5 rounded-full mb-2 ${isActive ? 'bg-emerald-400' : 'bg-amber-400'}`} aria-label={isActive ? 'Active' : 'Inactive'} />

                {/* Name */}
                <h2 className="text-center font-bold text-white text-sm leading-snug">
                    {fullName.toUpperCase()}
                </h2>
            </div>

            {/* Clinical info rows */}
            <dl className="px-4 py-3 space-y-3 text-[11px]">
                {resident.date_of_birth && (
                    <div>
                        <dt className="text-white/50 font-bold uppercase tracking-widest">Date of Birth</dt>
                        <dd className="text-white font-semibold mt-0.5">
                            {formatCalDate(resident.date_of_birth)}
                            {age !== null && <span className="text-white/70 ml-1">({age} y.o.)</span>}
                        </dd>
                    </div>
                )}

                <div className="grid grid-cols-2 gap-2">
                    {resident.gender && (
                        <div>
                            <dt className="text-white/50 font-bold uppercase tracking-widest">Gender</dt>
                            <dd className="text-white font-semibold mt-0.5">{resident.gender}</dd>
                        </div>
                    )}
                    {room && (
                        <div>
                            <dt className="text-white/50 font-bold uppercase tracking-widest">Room #</dt>
                            <dd className="text-white font-semibold mt-0.5">{room}</dd>
                        </div>
                    )}
                </div>

                {resident.code_status && (
                    <div>
                        <dt className="text-white/50 font-bold uppercase tracking-widest">Code Status</dt>
                        <dd className="flex items-center gap-1.5 mt-0.5">
                            <span className={`w-2 h-2 rounded-full shrink-0 ${codeStatusColor(resident.code_status)}`} aria-hidden="true" />
                            <span className="text-white font-semibold">{resident.code_status}</span>
                        </dd>
                    </div>
                )}

                {allergies && (
                    <div>
                        <dt className="text-white/50 font-bold uppercase tracking-widest">Allergies</dt>
                        <dd className="text-red-300 font-semibold mt-0.5 leading-relaxed">{allergies}</dd>
                    </div>
                )}

                {(resident.diet || resident.dietary_restrictions) && (
                    <div>
                        <dt className="text-white/50 font-bold uppercase tracking-widest">Diet</dt>
                        <dd className="text-white font-semibold mt-0.5">{resident.diet || resident.dietary_restrictions}</dd>
                    </div>
                )}

                {(resident.about_me || resident.notes) && (
                    <div>
                        <dt className="text-white/50 font-bold uppercase tracking-widest">About Me</dt>
                        <dd className="text-white/80 mt-0.5 leading-relaxed line-clamp-3">
                            {resident.about_me || resident.notes || 'None'}
                        </dd>
                    </div>
                )}

                {!resident.about_me && !resident.notes && (
                    <div>
                        <dt className="text-white/50 font-bold uppercase tracking-widest">About Me</dt>
                        <dd className="text-white/40 mt-0.5">None</dd>
                    </div>
                )}
            </dl>

            {/* Stay info footer */}
            {computeLengthOfStay(resident.admission_date) && (
                <div className="px-4 pb-4 border-t border-white/10 pt-3">
                    <p className="text-white/50 text-[10px] font-bold uppercase tracking-widest">Length of Stay</p>
                    <p className="text-white font-bold text-sm mt-0.5">{computeLengthOfStay(resident.admission_date)}</p>
                </div>
            )}
        </aside>
    );
}

// ─── Main hub page ────────────────────────────────────────────────────────────

export default function ResidentHubPage() {
    const { residentId } = useParams();
    const [searchParams, setSearchParams] = useSearchParams();
    const navigate = useNavigate();
    const activeTab = searchParams.get('tab') || 'overview';

    const setTab = (id) => setSearchParams({ tab: id }, { replace: true });

    const { data: currentUser } = useQuery({
        queryKey: ['current-user'],
        queryFn: async () => (await api.get('/user')).data,
    });

    const { data: resident, isLoading, error } = useQuery({
        queryKey: ['resident-hub', residentId],
        queryFn: async () => {
            const res = await api.get(`/residents/${residentId}`);
            return res.data?.data ?? res.data;
        },
        enabled: !!residentId,
    });

    const fullName = resident
        ? [resident.first_name, resident.middle_names, resident.last_name].filter(Boolean).join(' ')
        : '';

    const activeTabLabel = HUB_TABS.find(t => t.id === activeTab)?.label ?? 'Overview';

    if (isLoading) {
        return (
            <div>
                <Breadcrumbs items={[{ label: 'My Residents', path: '/my-residents' }, { label: 'Loading…', path: '' }]} />
                <div className="flex gap-4 mt-4 animate-pulse">
                    <div className="w-52 h-80 rounded-xl bg-gray-200 shrink-0" />
                    <div className="flex-1 h-80 rounded-xl bg-gray-100" />
                </div>
            </div>
        );
    }

    if (error || !resident) {
        return (
            <div>
                <Breadcrumbs items={[{ label: 'My Residents', path: '/my-residents' }, { label: 'Not Found', path: '' }]} />
                <div className="flex flex-col items-center justify-center py-24 text-center">
                    <AlertCircle className="w-12 h-12 text-gray-300 mb-3" />
                    <h3 className="text-lg font-bold text-gray-900">Resident not found</h3>
                    <button onClick={() => navigate('/my-residents')} className="mt-4 text-sm font-bold text-[var(--theme-primary)] hover:underline">
                        ← Back to My Residents
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            <Breadcrumbs items={[
                { label: 'My Residents', path: '/my-residents' },
                { label: fullName || 'Resident', path: '' },
            ]} />

            {/* ── 2-column layout: left profile + right content ─────────────── */}
            <div className="flex flex-col lg:flex-row gap-4 items-start">

                {/* ── LEFT: Resident profile panel (sticky on desktop) ── */}
                <ResidentLeftPanel
                    resident={resident}
                    residentId={residentId}
                    navigate={navigate}
                />

                {/* ── RIGHT: Tabs + content ── */}
                <div className="flex-1 min-w-0 space-y-0 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">

                    {/* Content title bar */}
                    <div className="flex items-center justify-between px-5 py-3 border-b border-gray-100">
                        <div className="flex items-center gap-3">
                            <button
                                type="button"
                                onClick={() => navigate('/my-residents')}
                                className="p-1.5 hover:bg-gray-100 rounded-full transition-colors"
                                aria-label="Back to residents"
                            >
                                <ArrowLeft className="w-4 h-4 text-gray-400" strokeWidth={2.25} />
                            </button>
                            <h1 className="text-base font-bold text-gray-900">{activeTabLabel}</h1>
                        </div>
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() => setTab('medications')}
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] hover:opacity-90 transition-opacity"
                            >
                                <Pill className="w-3.5 h-3.5" aria-hidden="true" />
                                Administer Meds
                            </button>
                            <button
                                type="button"
                                onClick={() => window.print()}
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors"
                            >
                                Print
                            </button>
                        </div>
                    </div>

                    {/* ── Tab bar (icon above label, scrollable) ── */}
                    <div
                        className="flex overflow-x-auto border-b border-gray-100 bg-gray-50/60"
                        style={{ scrollbarWidth: 'none' }}
                        role="tablist"
                        aria-label="Resident sections"
                    >
                        {HUB_TABS.map(({ id, label, icon: Icon }) => {
                            const isActive = activeTab === id;
                            return (
                                <button
                                    key={id}
                                    type="button"
                                    role="tab"
                                    aria-selected={isActive}
                                    onClick={() => setTab(id)}
                                    className={`relative flex flex-col items-center gap-1 px-4 py-3 min-w-[72px] whitespace-nowrap transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[var(--theme-primary)] ${
                                        isActive
                                            ? 'bg-white text-[var(--theme-primary)]'
                                            : 'text-gray-500 hover:text-gray-800 hover:bg-white/70'
                                    }`}
                                >
                                    <Icon className={`w-5 h-5 shrink-0 ${isActive ? 'text-[var(--theme-primary)]' : 'text-gray-400'}`} aria-hidden="true" />
                                    <span className={`text-[10px] font-bold tracking-wide ${isActive ? 'text-[var(--theme-primary)]' : 'text-gray-500'}`}>
                                        {label}
                                    </span>
                                    {isActive && (
                                        <span className="absolute bottom-0 left-3 right-3 h-0.5 rounded-full bg-[var(--theme-primary)]" aria-hidden="true" />
                                    )}
                                </button>
                            );
                        })}
                    </div>

                    {/* ── Tab content ── */}
                    <div className="min-h-[500px]">
                        {activeTab === 'overview'     && <OverviewTab resident={resident} residentId={residentId} navigate={navigate} setTab={setTab} />}
                        {activeTab === 'medications'  && <ResidentMedicationsPage embedded={true} />}
                        {activeTab === 'notes'        && <NotesTab residentId={residentId} />}
                        {activeTab === 'care'         && <CarePlanTab resident={resident} residentId={residentId} currentUser={currentUser} />}
                        {activeTab === 'documents'    && <ResidentDocuments residentId={residentId} />}
                        {activeTab === 'vitals'       && <VitalsTab residentId={residentId} resident={resident} navigate={navigate} />}
                        {activeTab === 'appointments' && <AppointmentsTab residentId={residentId} navigate={navigate} />}
                        {activeTab === 'profile'      && <ProfileTab resident={resident} />}
                    </div>
                </div>
            </div>
        </div>
    );
}

// ─── Overview Tab ─────────────────────────────────────────────────────────────

function StatCard({ icon: Icon, label, value, onClick }) {
    return (
        <div
            className={`rounded-xl border border-gray-100 bg-white p-4 flex items-center gap-3 ${onClick ? 'cursor-pointer hover:border-[var(--theme-primary)]/30 hover:shadow-sm transition-all' : ''}`}
            onClick={onClick}
            role={onClick ? 'button' : undefined}
        >
            <div className="w-9 h-9 rounded-lg bg-[var(--theme-primary)]/10 flex items-center justify-center shrink-0">
                <Icon className="w-4.5 h-4.5 text-[var(--theme-primary)]" aria-hidden="true" />
            </div>
            <div>
                <p className="text-[10px] font-bold uppercase tracking-widest text-gray-400">{label}</p>
                <p className="text-lg font-bold text-gray-900 mt-0.5">{value}</p>
            </div>
        </div>
    );
}

function OverviewTab({ resident, residentId, navigate, setTab }) {
    const { data: medsData } = useQuery({
        queryKey: ['overview-meds', residentId],
        queryFn: async () => (await api.get('/medications', { params: { resident_id: residentId, active_only: 'true', per_page: 100 } })).data,
        enabled: !!residentId,
    });

    const { data: notesData } = useQuery({
        queryKey: ['overview-notes', residentId],
        queryFn: async () => (await api.get('/t-logs', { params: { resident_id: residentId, per_page: 3 } })).data,
        enabled: !!residentId,
    });

    const { data: apptData } = useQuery({
        queryKey: ['overview-appts', residentId],
        queryFn: async () => (await api.get('/appointments', { params: { resident_id: residentId, per_page: 5 } })).data,
        enabled: !!residentId,
    });

    const medsCount  = (medsData?.data ?? medsData ?? []).length;
    const recentNotes = notesData?.data ?? (Array.isArray(notesData) ? notesData : []);
    const allAppts = apptData?.data ?? (Array.isArray(apptData) ? apptData : []);
    const upcomingAppts = allAppts.filter(a => a.appointment_date && new Date(a.appointment_date) >= new Date());
    const vitalSigns = resident?.vital_signs ?? resident?.vitalSigns ?? [];
    const latestVital = Array.isArray(vitalSigns) ? vitalSigns[0] : null;

    return (
        <div className="p-5 space-y-5">
            {/* Quick stats */}
            <div className="grid grid-cols-2 xl:grid-cols-4 gap-3">
                <StatCard icon={Pill}     label="Active Meds"    value={medsCount}    onClick={() => setTab('medications')} />
                <StatCard icon={Calendar} label="Upcoming Appts" value={upcomingAppts.length} onClick={() => setTab('appointments')} />
                <StatCard icon={FileText} label="Recent Notes"   value={recentNotes.length} onClick={() => setTab('notes')} />
                <StatCard icon={Heart}    label="Vitals on File" value={Array.isArray(vitalSigns) ? vitalSigns.length : 0} onClick={() => setTab('vitals')} />
            </div>

            <div className="grid grid-cols-1 xl:grid-cols-2 gap-5">
                {/* Latest vitals */}
                <section className="rounded-xl border border-gray-100 overflow-hidden">
                    <div className="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-gray-50/50">
                        <div className="flex items-center gap-2">
                            <Activity className="w-3.5 h-3.5 text-[var(--theme-primary)]" aria-hidden="true" />
                            <span className="text-xs font-bold text-gray-900">Latest Vitals</span>
                        </div>
                        <button onClick={() => setTab('vitals')} className="text-[10px] font-bold text-[var(--theme-primary)] hover:underline">View All →</button>
                    </div>
                    {latestVital ? (
                        <div className="grid grid-cols-3 gap-2 p-4">
                            {[
                                { label: 'BP', value: latestVital.systolic ? `${latestVital.systolic}/${latestVital.diastolic}` : null },
                                { label: 'Pulse', value: latestVital.pulse ? `${latestVital.pulse} bpm` : null },
                                { label: 'Temp', value: latestVital.temperature ? `${parseFloat(latestVital.temperature).toFixed(1)}°F` : null },
                                { label: 'SpO₂', value: latestVital.oxygen_saturation ? `${latestVital.oxygen_saturation}%` : null },
                                { label: 'Pain', value: latestVital.pain_level != null ? `${latestVital.pain_level}/10` : null },
                                { label: 'Weight', value: latestVital.weight ? `${latestVital.weight} lbs` : null },
                            ].filter(v => v.value).map(({ label, value }) => (
                                <div key={label} className="bg-gray-50 rounded-lg p-2.5">
                                    <p className="text-[9px] font-bold uppercase tracking-widest text-gray-400">{label}</p>
                                    <p className="text-sm font-bold text-gray-900 mt-0.5">{value}</p>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="flex items-center justify-center py-8 text-gray-400 text-xs">No vitals recorded</div>
                    )}
                </section>

                {/* Recent notes */}
                <section className="rounded-xl border border-gray-100 overflow-hidden">
                    <div className="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-gray-50/50">
                        <div className="flex items-center gap-2">
                            <FileText className="w-3.5 h-3.5 text-[var(--theme-primary)]" aria-hidden="true" />
                            <span className="text-xs font-bold text-gray-900">Recent Notes</span>
                        </div>
                        <button onClick={() => setTab('notes')} className="text-[10px] font-bold text-[var(--theme-primary)] hover:underline">View All →</button>
                    </div>
                    {recentNotes.length > 0 ? (
                        <ul className="divide-y divide-gray-50">
                            {recentNotes.slice(0, 3).map(note => (
                                <li key={note.id} className="px-4 py-3">
                                    <p className="text-xs text-gray-800 line-clamp-2">{note.notes || note.content || '—'}</p>
                                    <p className="text-[10px] text-gray-400 mt-1">
                                        {note.created_at ? new Date(note.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', timeZone: 'America/Los_Angeles' }) : ''}
                                        {note.user?.name ? ` · ${note.user.name}` : ''}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="flex items-center justify-center py-8 text-gray-400 text-xs">No notes recorded</div>
                    )}
                </section>
            </div>

            {/* Clinical snapshot */}
            <section className="rounded-xl border border-gray-100 overflow-hidden">
                <div className="flex items-center gap-2 px-4 py-2.5 border-b border-gray-100 bg-gray-50/50">
                    <Stethoscope className="w-3.5 h-3.5 text-[var(--theme-primary)]" aria-hidden="true" />
                    <span className="text-xs font-bold text-gray-900">Clinical Snapshot</span>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">
                    {[
                        { label: 'Diagnosis', value: resident.diagnosis },
                        { label: 'Pharmacy', value: resident.pharmacy?.name || resident.pharmacy_name },
                        { label: 'Med Instructions', value: resident.general_medication_instructions },
                    ].map(({ label, value }) => (
                        <div key={label} className="px-4 py-3">
                            <p className="text-[10px] font-bold uppercase tracking-widest text-gray-400">{label}</p>
                            <p className="text-xs font-medium text-gray-800 mt-1 line-clamp-2">{value || <span className="text-gray-400 italic">Not recorded</span>}</p>
                        </div>
                    ))}
                </div>
            </section>
        </div>
    );
}

// ─── Notes Tab ────────────────────────────────────────────────────────────────

function NotesTab({ residentId }) {
    const [page, setPage] = React.useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['hub-notes', residentId, page],
        queryFn: async () => (await api.get('/t-logs', { params: { resident_id: residentId, per_page: 10, page } })).data,
        enabled: !!residentId,
        keepPreviousData: true,
    });

    const notes = data?.data ?? (Array.isArray(data) ? data : []);
    const totalPages = data?.last_page ?? 1;

    if (isLoading) return <TabSkeleton />;

    return (
        <div>
            <div className="flex items-center justify-between px-5 py-3 border-b border-gray-100 bg-gray-50/40">
                <span className="text-xs font-bold text-gray-500">Progress Notes / T-Logs</span>
                <Link to={`/t-logs?resident_id=${residentId}`} className="text-xs font-semibold text-[var(--theme-primary)] hover:underline">Open Full View →</Link>
            </div>
            {notes.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-16">
                    <FileText className="w-10 h-10 text-gray-200 mb-2" />
                    <p className="text-sm text-gray-400">No notes recorded yet.</p>
                </div>
            ) : (
                <ul className="divide-y divide-gray-50">
                    {notes.map(note => (
                        <li key={note.id} className="flex items-start justify-between gap-4 px-5 py-4">
                            <p className="text-sm text-gray-800 leading-relaxed flex-1">{note.notes || note.content || '—'}</p>
                            <div className="text-right text-[11px] text-gray-400 shrink-0">
                                <p className="font-medium text-gray-600">{note.user?.name || 'Staff'}</p>
                                <p>{note.created_at ? new Date(note.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'America/Los_Angeles' }) : ''}</p>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
            {totalPages > 1 && (
                <div className="flex items-center justify-between px-5 py-3 border-t border-gray-100 bg-gray-50/50">
                    <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1} className="text-xs font-bold text-gray-500 disabled:opacity-40">← Prev</button>
                    <span className="text-xs text-gray-400">Page {page} of {totalPages}</span>
                    <button onClick={() => setPage(p => Math.min(totalPages, p + 1))} disabled={page === totalPages} className="text-xs font-bold text-gray-500 disabled:opacity-40">Next →</button>
                </div>
            )}
        </div>
    );
}

// ─── Care Plan Tab ────────────────────────────────────────────────────────────

function CarePlanTab({ resident, residentId, currentUser }) {
    const queryClient = useQueryClient();
    const canEdit = !isCaregiverRole(currentUser?.role);
    const [editing, setEditing] = React.useState(false);
    const [form, setForm] = React.useState({ care_plan: '', special_instructions: '', notes: '' });

    React.useEffect(() => {
        if (resident) setForm({ care_plan: resident.care_plan || '', special_instructions: resident.special_instructions || '', notes: resident.notes || '' });
    }, [resident]);

    const mutation = useMutation({
        mutationFn: (data) => api.put(`/residents/${residentId}`, data),
        onSuccess: () => { queryClient.invalidateQueries(['resident-hub', residentId]); setEditing(false); },
        onError: (err) => alert(err?.response?.data?.message || 'Failed to save.'),
    });

    return (
        <div className="p-5 space-y-4">
            <div className="flex items-center justify-between">
                <span className="text-xs font-bold uppercase tracking-widest text-gray-400">Care Plan</span>
                {canEdit && !editing && (
                    <button type="button" onClick={() => setEditing(true)} className="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--theme-primary)] hover:underline">
                        <Edit className="w-3.5 h-3.5" /> Edit
                    </button>
                )}
            </div>
            {[{ key: 'care_plan', label: 'Care Plan', rows: 5 }, { key: 'special_instructions', label: 'Special Instructions', rows: 3 }, { key: 'notes', label: 'Additional Notes', rows: 2 }].map(({ key, label, rows }) => (
                <div key={key}>
                    <label className="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-1.5">{label}</label>
                    {editing ? (
                        <textarea rows={rows} value={form[key]} onChange={e => setForm(f => ({ ...f, [key]: e.target.value }))}
                            className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent resize-y" />
                    ) : (
                        <div className="bg-gray-50 rounded-lg px-3 py-2.5 text-sm text-gray-800 whitespace-pre-wrap min-h-[52px]">
                            {form[key] || <span className="text-gray-400 italic">Not recorded</span>}
                        </div>
                    )}
                </div>
            ))}
            {editing && (
                <div className="flex gap-2 pt-1">
                    <button type="button" onClick={() => mutation.mutate(form)} disabled={mutation.isPending}
                        className="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] hover:opacity-90 disabled:opacity-50">
                        <Save className="w-3.5 h-3.5" /> {mutation.isPending ? 'Saving…' : 'Save'}
                    </button>
                    <button type="button" onClick={() => setEditing(false)}
                        className="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold border border-gray-200 text-gray-600 hover:bg-gray-50">
                        <X className="w-3.5 h-3.5" /> Cancel
                    </button>
                </div>
            )}
        </div>
    );
}

// ─── Vitals Tab ───────────────────────────────────────────────────────────────

function VitalsTab({ residentId, resident, navigate }) {
    const vitalSigns = resident?.vital_signs ?? resident?.vitalSigns ?? [];

    return (
        <div>
            <div className="flex items-center justify-between px-5 py-3 border-b border-gray-100 bg-gray-50/40">
                <span className="text-xs font-bold text-gray-500">Vital Signs</span>
                <div className="flex items-center gap-3">
                    <button onClick={() => navigate(`/vitals?resident=${residentId}`)} className="text-xs font-bold bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] px-3 py-1.5 rounded-lg hover:opacity-90">+ Record</button>
                    <button onClick={() => navigate(`/view-vitals?resident=${residentId}`)} className="text-xs font-semibold text-[var(--theme-primary)] hover:underline">Full History →</button>
                </div>
            </div>
            {Array.isArray(vitalSigns) && vitalSigns.length > 0 ? (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                            <tr>{['Date', 'BP', 'Pulse', 'Temp', 'SpO₂', 'Weight', 'Pain'].map(h => <th key={h} className="px-4 py-2.5 text-left">{h}</th>)}</tr>
                        </thead>
                        <tbody className="divide-y divide-gray-50">
                            {vitalSigns.slice(0, 10).map(v => (
                                <tr key={v.id} className="hover:bg-gray-50/60">
                                    <td className="px-4 py-2.5 text-xs text-gray-500">{v.recorded_at || v.created_at ? new Date(v.recorded_at || v.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', timeZone: 'America/Los_Angeles' }) : '—'}</td>
                                    <td className="px-4 py-2.5 font-medium">{v.systolic && v.diastolic ? `${v.systolic}/${v.diastolic}` : '—'}</td>
                                    <td className="px-4 py-2.5">{v.pulse ?? '—'}</td>
                                    <td className="px-4 py-2.5">{v.temperature ? `${parseFloat(v.temperature).toFixed(1)}°` : '—'}</td>
                                    <td className="px-4 py-2.5">{v.oxygen_saturation ? `${v.oxygen_saturation}%` : '—'}</td>
                                    <td className="px-4 py-2.5">{v.weight ?? '—'}</td>
                                    <td className="px-4 py-2.5">{v.pain_level ?? '—'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <div className="flex flex-col items-center justify-center py-16">
                    <Heart className="w-10 h-10 text-gray-200 mb-2" />
                    <p className="text-sm text-gray-400">No vitals recorded.</p>
                    <button onClick={() => navigate(`/vitals?resident=${residentId}`)} className="mt-3 text-xs font-bold text-[var(--theme-primary)] hover:underline">Record first vital →</button>
                </div>
            )}
        </div>
    );
}

// ─── Appointments Tab ─────────────────────────────────────────────────────────

function AppointmentsTab({ residentId, navigate }) {
    const { data, isLoading } = useQuery({
        queryKey: ['hub-appointments', residentId],
        queryFn: async () => (await api.get('/appointments', { params: { resident_id: residentId, per_page: 20 } })).data,
        enabled: !!residentId,
    });

    const appointments = data?.data ?? (Array.isArray(data) ? data : []);
    const now = new Date();
    const upcoming = appointments.filter(a => a.appointment_date && new Date(a.appointment_date) >= now);
    const past = appointments.filter(a => a.appointment_date && new Date(a.appointment_date) < now);

    if (isLoading) return <TabSkeleton />;

    const renderAppt = (appt) => (
        <li key={appt.id} className="flex items-start gap-3 px-5 py-3 hover:bg-gray-50/60">
            <div className="w-8 h-8 rounded-lg bg-[var(--theme-primary)]/10 flex items-center justify-center shrink-0 mt-0.5">
                <Calendar className="w-3.5 h-3.5 text-[var(--theme-primary)]" />
            </div>
            <div className="flex-1 min-w-0">
                <p className="text-sm font-semibold text-gray-900 truncate">{appt.title || appt.appointment_type || 'Appointment'}</p>
                <p className="text-xs text-gray-500 mt-0.5">{appt.appointment_date ? new Date(appt.appointment_date).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', timeZone: 'America/Los_Angeles' }) : '—'}</p>
            </div>
            {appt.status && (
                <span className={`text-[10px] font-bold uppercase px-2 py-0.5 rounded-full ${appt.status === 'completed' ? 'bg-green-100 text-green-700' : appt.status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'}`}>{appt.status}</span>
            )}
        </li>
    );

    return (
        <div>
            <div className="flex items-center justify-between px-5 py-3 border-b border-gray-100 bg-gray-50/40">
                <span className="text-xs font-bold text-gray-500">Appointments</span>
                <button onClick={() => navigate(`/appointments/create/${residentId}`)} className="text-xs font-bold bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] px-3 py-1.5 rounded-lg hover:opacity-90">+ Schedule</button>
            </div>
            {upcoming.length > 0 && <><p className="px-5 pt-3 pb-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">Upcoming</p><ul className="divide-y divide-gray-50">{upcoming.map(renderAppt)}</ul></>}
            {past.length > 0 && <><div className="border-t border-gray-100 mt-2" /><p className="px-5 pt-3 pb-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">Past</p><ul className="divide-y divide-gray-50">{past.slice(0, 5).map(renderAppt)}</ul></>}
            {appointments.length === 0 && (
                <div className="flex flex-col items-center justify-center py-16">
                    <Calendar className="w-10 h-10 text-gray-200 mb-2" />
                    <p className="text-sm text-gray-400">No appointments scheduled.</p>
                </div>
            )}
        </div>
    );
}

// ─── Profile Tab ──────────────────────────────────────────────────────────────

function ProfileTab({ resident }) {
    if (!resident) return null;
    const fullName = [resident.first_name, resident.middle_names, resident.last_name].filter(Boolean).join(' ');
    const age = resident.date_of_birth ? calculateAgeFromPacificBirthDate(resident.date_of_birth) : null;

    const sections = [
        { title: 'Personal', icon: User, fields: [
            { label: 'Full Name', value: fullName },
            { label: 'Date of Birth', value: formatCalDate(resident.date_of_birth) + (age != null ? ` (${age} y.o.)` : '') },
            { label: 'Gender', value: resident.gender },
            { label: 'Phone', value: formatPhone(resident.phone) },
            { label: 'Email', value: resident.email },
        ]},
        { title: 'Residence', icon: Building2, fields: [
            { label: 'Room', value: resident.room_number || resident.room },
            { label: 'Admission Date', value: formatCalDate(resident.admission_date) },
            { label: 'Branch', value: resident.branch?.name },
        ]},
        { title: 'Clinical', icon: Stethoscope, fields: [
            { label: 'Code Status', value: resident.code_status },
            { label: 'Allergies', value: Array.isArray(resident.allergies) ? resident.allergies.join(', ') : resident.allergies },
            { label: 'Diet', value: resident.diet || resident.dietary_restrictions },
            { label: 'Diagnosis', value: resident.diagnosis },
            { label: 'Pharmacy', value: resident.pharmacy?.name || resident.pharmacy_name },
        ]},
        { title: 'Emergency Contact', icon: Phone, fields: [
            { label: 'Name', value: resident.emergency_contact_name },
            { label: 'Phone', value: formatPhone(resident.emergency_contact_phone) },
            { label: 'Relationship', value: resident.emergency_contact_relationship },
        ]},
    ];

    return (
        <div className="p-5 space-y-5">
            {sections.map(({ title, icon: Icon, fields }) => {
                const filled = fields.filter(f => f.value);
                if (!filled.length) return null;
                return (
                    <section key={title} className="rounded-xl border border-gray-100 overflow-hidden">
                        <div className="flex items-center gap-2 px-4 py-2.5 bg-gray-50/60 border-b border-gray-100">
                            <Icon className="w-3.5 h-3.5 text-[var(--theme-primary)]" aria-hidden="true" />
                            <span className="text-xs font-bold text-gray-900">{title}</span>
                        </div>
                        <dl className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-px bg-gray-100">
                            {filled.map(({ label, value }) => (
                                <div key={label} className="bg-white px-4 py-3">
                                    <dt className="text-[10px] font-bold uppercase tracking-widest text-gray-400">{label}</dt>
                                    <dd className="mt-1 text-sm font-medium text-gray-900">{value}</dd>
                                </div>
                            ))}
                        </dl>
                    </section>
                );
            })}
        </div>
    );
}

// ─── Skeleton ─────────────────────────────────────────────────────────────────

function TabSkeleton() {
    return (
        <div className="p-5 space-y-3" aria-busy="true">
            {[1, 2, 3, 4].map(i => <div key={i} className="h-14 rounded-lg bg-gray-50 animate-pulse" />)}
        </div>
    );
}
