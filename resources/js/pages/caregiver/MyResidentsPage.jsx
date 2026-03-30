import React from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { Users, Search, MapPin, Calendar, Phone, Activity, Edit, Eye } from 'lucide-react';
import api from '../../services/api';
import ResidentForm from '../../components/ResidentForm';
import Tooltip from '../../components/ui/Tooltip';
import EntityCardShell, { EntityCardHeader } from '../../components/ui/EntityCardShell';
import CardIconButton from '../../components/ui/CardIconButton';
import DataPill from '../../components/ui/DataPill';
import ResidentAvatarInline from '../../components/ui/ResidentAvatarInline';
import { isCaregiverRole } from '../../utils/userRoles';
import {
    formatPacificCalendarMedium,
    formatPacificDateTimeShort,
    calculateAgeFromPacificBirthDate,
} from '../../utils/pacificTime';

const initialStats = [
    { key: 'active', label: 'Active Residents', icon: Users },
    { key: 'inactive', label: 'Inactive Residents', icon: Activity },
];

export default function MyResidentsPage() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [search, setSearch] = React.useState('');
    const [debouncedSearch, setDebouncedSearch] = React.useState('');
    const [showForm, setShowForm] = React.useState(false);
    const [editing, setEditing] = React.useState(null);

    // Current user query (to get assigned branch)
    const { data: currentUser, isLoading: isLoadingUser } = useQuery({
        queryKey: ['current-user'],
        queryFn: async () => {
            const res = await api.get('/user');
            return res.data;
        },
    });

    const { data: branchesData } = useQuery({
        queryKey: ['branches-options'],
        queryFn: async () => (await api.get('/branches', { params: { per_page: 100 } })).data,
    });


    React.useEffect(() => {
        const timeout = setTimeout(() => {
            setDebouncedSearch(search.trim());
        }, 350);

        return () => clearTimeout(timeout);
    }, [search]);

    const { data, isLoading, error } = useQuery({
        queryKey: ['my-residents', debouncedSearch, currentUser?.assigned_branch_id],
        queryFn: async () => {
            const params = { 
                per_page: 50,
                show_all: true, // Show both active and inactive residents
            };
            if (debouncedSearch) {
                params.search = debouncedSearch;
            }
            // For caregivers, backend automatically filters by assigned branch
            // For administrators with assigned branch, explicitly pass branch_id to ensure correct filtering
            if (currentUser?.assigned_branch_id) {
                params.branch_id = currentUser.assigned_branch_id;
            }
            const response = await api.get('/residents', { params });
            return response.data;
        },
        enabled: !isLoadingUser, // Wait for user data to load first
    });

    const residents = React.useMemo(() => data?.data ?? [], [data?.data]);

    const canEditResidents = !isCaregiverRole(currentUser?.role);

    const stats = React.useMemo(() => {
        const totals = { active: 0, inactive: 0 };
        residents.forEach((resident) => {
            const activeValue = resident?.is_active;
            const isActive = activeValue === true || activeValue === 1 || activeValue === '1';
            if (isActive) {
                totals.active += 1;
            } else {
                totals.inactive += 1;
            }
        });

        return initialStats.map((item) => ({
            ...item,
            value: totals[item.key],
        }));
    }, [residents]);

    // Get the branch name from the user's assigned branch (primary source)
    // Fallback to residents list only if user doesn't have assigned_branch loaded
    const branchName = React.useMemo(() => {
        // First priority: Use the user's assigned branch directly
        if (currentUser?.assigned_branch?.name) {
            return currentUser.assigned_branch.name;
        }
        // Second priority: Try to get from user's assigned_branch_id if branch object isn't loaded
        if (currentUser?.assigned_branch_id && residents.length > 0) {
            const residentFromBranch = residents.find((r) => r?.branch_id === currentUser.assigned_branch_id);
            if (residentFromBranch?.branch?.name) {
                return residentFromBranch.branch.name;
            }
        }
        // Last resort: Get from any resident (shouldn't happen if backend filtering works correctly)
        const withBranch = residents.find((r) => r?.branch?.name);
        return withBranch?.branch?.name || null;
    }, [residents, currentUser?.assigned_branch?.name, currentUser?.assigned_branch_id]);

    const renderResidentCard = (resident) => {
        const isActive = resident?.is_active === true || resident?.is_active === 1 || resident?.is_active === '1';
        const fullName = [resident.first_name, resident.middle_names, resident.last_name].filter(Boolean).join(' ');
        const branchNameRes = resident?.branch?.name ?? 'Unassigned';
        const ageYears = calculateAgeFromPacificBirthDate(resident.date_of_birth);

        return (
            <EntityCardShell key={resident.id}>
                <EntityCardHeader
                    left={
                        <div className="flex flex-wrap items-start gap-3">
                            <ResidentAvatarInline resident={resident} className="h-10 w-10 text-xs" />
                            <div className="space-y-2">
                                <span className="font-mono text-xs font-bold tracking-wide text-slate-500">
                                    #{resident.id}
                                </span>
                                <div className="flex flex-wrap gap-1.5">
                                    <span
                                        className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ${
                                            isActive
                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                                                : 'border-amber-200 bg-amber-50 text-amber-800'
                                        }`}
                                    >
                                        {isActive ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    }
                    right={
                        <>
                            {canEditResidents ? (
                                <Tooltip content="Edit resident" position="top">
                                    <CardIconButton
                                        variant="edit"
                                        icon={Edit}
                                        aria-label="Edit resident"
                                        onClick={() => {
                                            setEditing(resident);
                                            setShowForm(true);
                                        }}
                                    />
                                </Tooltip>
                            ) : null}
                            <Tooltip content="View profile" position="top">
                                <CardIconButton
                                    variant="view"
                                    icon={Eye}
                                    aria-label="View profile"
                                    onClick={() => navigate(`/my-residents/${resident.id}`)}
                                />
                            </Tooltip>
                        </>
                    }
                />

                <h3 className="text-lg font-bold leading-snug text-slate-900 sm:text-xl">
                    {fullName || 'Unnamed Resident'}
                </h3>
                <p className="mt-1 text-sm text-slate-500">
                    Resident since {formatPacificCalendarMedium(resident.admission_date)}
                </p>

                <div className="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                    <DataPill icon={MapPin}>
                        <span className="font-normal text-slate-600">{branchNameRes}</span>
                    </DataPill>
                    <DataPill icon={Calendar}>
                        <span className="font-normal text-slate-600">
                            {formatPacificCalendarMedium(resident.date_of_birth)}
                            {ageYears !== null ? ` · ${ageYears} yrs` : ''}
                        </span>
                    </DataPill>
                    <DataPill icon={Phone}>
                        <span className="font-normal text-slate-600">
                            {resident.phone || resident.emergency_contact_phone || 'N/A'}
                        </span>
                    </DataPill>
                    <DataPill icon={Users}>
                        <span className="font-normal text-slate-600 truncate">
                            {resident.emergency_contact_name || 'Not provided'}
                        </span>
                    </DataPill>
                </div>

                <p className="mt-4 text-xs text-slate-400">
                    Last updated {formatPacificDateTimeShort(resident.updated_at)}
                </p>
            </EntityCardShell>
        );
    };

    const renderResidentsEmptyState = (title, description, IconComponent = Users) => (
        <div className="rounded-2xl border border-dashed border-gray-200 bg-white p-12 text-center shadow-sm">
            <IconComponent className="mx-auto h-12 w-12 text-[var(--theme-primary-light)]" />
            <h3 className="mt-4 text-lg font-semibold text-gray-900">{title}</h3>
            <p className="mt-2 text-sm text-gray-500">{description}</p>
        </div>
    );

    return (
        <div className="space-y-6">
            <header className="rounded-2xl bg-gradient-to-r from-[var(--theme-primary)] via-[var(--theme-primary-light)] to-[var(--theme-primary-lighter)] p-6 text-white shadow-lg">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p className="text-sm font-medium uppercase tracking-wide text-white/90">Caregiver Console</p>
                        <h1 className="text-2xl font-semibold md:text-3xl">My Residents</h1>
                        <p className="mt-2 max-w-xl text-sm text-white/90">
                            Review key details for the residents assigned to you. Quickly navigate to a resident&apos;s full
                            profile to view care plans, medication records, vitals, and more.
                        </p>
                    </div>
                    <div className="rounded-xl bg-white/10 px-4 py-3 text-sm text-white shadow-inner backdrop-blur">
                        <span className="text-2xl font-semibold">{residents.length}</span>{' '}
                        <span className="text-white/90">total residents</span>
                    </div>
                </div>
                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {stats.map(({ key, label, icon: Icon, value }) => (
                        <div
                            key={key}
                            className="rounded-2xl border border-white/10 bg-white/15 p-4 shadow-sm backdrop-blur transition hover:bg-white/20"
                        >
                            <div className="flex items-center justify-between">
                                <dt className="text-sm font-medium text-white/90">{label}</dt>
                                <span className="rounded-full bg-white/20 p-2">
                                    <Icon className="h-4 w-4 text-white" />
                                </span>
                            </div>
                            <dd className="mt-3 text-2xl font-semibold text-white">{value}</dd>
                        </div>
                    ))}
                    {branchName ? (
                        <div className="rounded-2xl border border-white/10 bg-white/15 p-4 shadow-sm backdrop-blur transition hover:bg-white/20">
                            <div className="flex items-center justify-between">
                                <dt className="text-sm font-medium text-white/90">Branch</dt>
                                <span className="rounded-full bg-white/20 p-2">
                                    <MapPin className="h-4 w-4 text-white" />
                                </span>
                            </div>
                            <dd className="mt-3 text-xl font-semibold text-white">{branchName}</dd>
                        </div>
                    ) : null}
                </div>
            </header>

            <section className="rounded-2xl bg-white p-6 shadow-sm">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900">Resident Directory</h2>
                        <p className="text-sm text-gray-500">
                            Search by name, room, or contact information to quickly find a resident.
                        </p>
                    </div>
                    <div className="relative w-full md:w-72">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                        <input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search residents..."
                            className="w-full rounded-lg border border-gray-200 bg-white py-2 pl-11 pr-4 text-sm shadow-sm focus:border-[var(--theme-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--theme-primary-bg)]"
                        />
                    </div>
                </div>
            </section>

            {error ? (
                <div className="rounded-2xl border border-red-200 bg-red-50 p-6 text-sm text-red-700">
                    Unable to load residents. {error.response?.data?.message || error.message}
                </div>
            ) : null}

            {isLoading ? (
                <div className="flex min-h-[200px] items-center justify-center rounded-2xl bg-white shadow-sm">
                    <div className="flex flex-col items-center gap-4">
                        <div className="h-10 w-10 animate-spin rounded-full border-2 border-[var(--theme-primary-bg)] border-t-[var(--theme-primary)]" />
                        <p className="text-sm text-gray-500">Loading residents...</p>
                    </div>
                </div>
            ) : residents.length === 0 ? (
                renderResidentsEmptyState(
                    'No residents found',
                    'Residents assigned to your branch will appear here. Try adjusting your search query.'
                )
            ) : (
                <section className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    {residents.map(renderResidentCard)}
                </section>
            )}

            {canEditResidents && showForm && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-md p-4 animate-in fade-in duration-300">
                    <div className="w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden animate-in zoom-in-95 duration-300">
                        <ResidentForm
                            record={editing}
                            branches={branchesData?.data || []}
                            selectedBranchId={currentUser?.assigned_branch_id}
                            onClose={() => {
                                setShowForm(false);
                                setEditing(null);
                            }}
                            onSuccess={() => {
                                setShowForm(false);
                                setEditing(null);
                                queryClient.invalidateQueries(['my-residents']);
                            }}
                        />
                    </div>
                </div>
            )}
        </div>
    );
}


