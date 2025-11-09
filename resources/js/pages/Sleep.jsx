import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../services/api';
import { Moon, Plus, Search, Calendar, Clock, User, Edit, Trash2, Filter, ChevronDown } from 'lucide-react';

export default function Sleep() {
    const queryClient = useQueryClient();
    const [dateFilter, setDateFilter] = useState('all');
    const [residentFilter, setResidentFilter] = useState('');
    const [search, setSearch] = useState('');
    const [showForm, setShowForm] = useState(false);
    const [editingRecord, setEditingRecord] = useState(null);

    const { data: currentUser } = useQuery({
        queryKey: ['current-user'],
        queryFn: async () => {
            const response = await api.get('/user');
            if (response?.data && typeof response.data === 'object') {
                if (response.data.user) {
                    return response.data.user;
                }
                if (response.data.data) {
                    return response.data.data;
                }
                return response.data;
            }
            return null;
        },
        staleTime: 5 * 60 * 1000,
    });

    const isCaregiver = React.useMemo(() => {
        if (!currentUser) {
            return false;
        }

        const truthyValues = [
            currentUser.is_caregiver,
            currentUser.isCaregiver,
            currentUser.caregiver,
            currentUser.is_care_giver,
        ];

        const normalizeToBoolean = (value) => {
            if (typeof value === 'boolean') return value;
            if (typeof value === 'number') return value === 1;
            if (typeof value === 'string') {
                const normalized = value.trim().toLowerCase();
                return ['1', 'true', 'yes', 'y', 'caregiver', 'care_giver'].includes(normalized);
            }
            return false;
        };

        if (truthyValues.some(normalizeToBoolean)) {
            return true;
        }

        const candidateValues = [];
        const collectCandidate = (value) => {
            if (value !== null && value !== undefined && value !== '') {
                candidateValues.push(String(value));
            }
        };

        collectCandidate(currentUser.role);
        collectCandidate(currentUser.position);
        collectCandidate(currentUser.primary_role);
        collectCandidate(currentUser.job_title);
        collectCandidate(currentUser.primaryRole);
        collectCandidate(currentUser.title);

        const roles = currentUser.roles;
        if (Array.isArray(roles)) {
            roles.forEach((roleItem) => {
                if (!roleItem) return;
                if (typeof roleItem === 'string') {
                    collectCandidate(roleItem);
                } else {
                    collectCandidate(roleItem.name);
                    collectCandidate(roleItem.title);
                    if (roleItem?.pivot?.role_name) {
                        collectCandidate(roleItem.pivot.role_name);
                    }
                }
            });
        } else if (roles?.data && Array.isArray(roles.data)) {
            roles.data.forEach((roleItem) => {
                if (!roleItem) return;
                if (typeof roleItem === 'string') {
                    collectCandidate(roleItem);
                } else {
                    collectCandidate(roleItem.name);
                    collectCandidate(roleItem.title);
                    if (roleItem?.pivot?.role_name) {
                        collectCandidate(roleItem.pivot.role_name);
                    }
                }
            });
        }

        return candidateValues.some((value) => {
            const lower = value.toLowerCase().trim();
            if (!lower) {
                return false;
            }
            const normalized = lower.replace(/[\s_-]/g, '');
            if (normalized === 'caregiver') {
                return true;
            }
            return lower.includes('care') && lower.includes('giver');
        });
    }, [currentUser]);

    const caregiverBranchId = React.useMemo(() => {
        if (!isCaregiver) {
            return '';
        }
        const assignedId = currentUser?.assigned_branch_id;
        return assignedId ? String(assignedId) : '';
    }, [isCaregiver, currentUser?.assigned_branch_id]);

    // Fetch residents for filter
    const { data: residentsData } = useQuery({
        queryKey: ['residents-list', isCaregiver ? caregiverBranchId || 'none' : 'all'],
        queryFn: async () => {
            const params = { per_page: 100 };
            if (isCaregiver && caregiverBranchId) {
                params.branch_id = caregiverBranchId;
            }
            const response = await api.get('/residents', { params });
            return response.data;
        },
    });

    const residentOptions = React.useMemo(() => {
        const residents = residentsData?.data || [];
        if (isCaregiver && caregiverBranchId) {
            return residents.filter((resident) => String(resident.branch_id) === String(caregiverBranchId));
        }
        return residents;
    }, [residentsData?.data, isCaregiver, caregiverBranchId]);

    const caregiverBranchName = React.useMemo(() => {
        if (!isCaregiver || !caregiverBranchId) {
            return '';
        }

        const residentMatch = residentOptions.find(
            (resident) => String(resident.branch_id) === String(caregiverBranchId)
        );
        if (residentMatch?.branch?.name) {
            return residentMatch.branch.name;
        }

        if (currentUser?.assigned_branch?.name) {
            return currentUser.assigned_branch.name;
        }

        if (currentUser?.assigned_branch_name) {
            return currentUser.assigned_branch_name;
        }

        return '';
    }, [isCaregiver, caregiverBranchId, residentOptions, currentUser]);

    // Fetch sleep records
    const { data, isLoading } = useQuery({
        queryKey: ['sleep-records', dateFilter, residentFilter, search],
        queryFn: async () => {
            const params = { per_page: 20 };
            
            if (dateFilter === 'today') {
                params.today = 'true';
            } else if (dateFilter === 'week') {
                const weekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
                params.date_from = weekAgo.toISOString().split('T')[0];
            } else if (dateFilter === 'month') {
                const monthAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);
                params.date_from = monthAgo.toISOString().split('T')[0];
            }
            
            if (residentFilter) {
                params.resident_id = residentFilter;
            }

            if (search) {
                params.search = search;
            }

            if (isCaregiver && caregiverBranchId) {
                params.branch_id = caregiverBranchId;
            }

            const response = await api.get('/sleep-records', { params });
            return response.data;
        },
    });

    // Delete mutation
    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            await api.delete(`/sleep-records/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['sleep-records']);
        },
    });

    const formatTime = (timeString) => {
        if (!timeString) return 'N/A';
        try {
            const time = new Date(`2000-01-01T${timeString}`);
            return time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
        } catch {
            return timeString;
        }
    };

    const getQualityColor = (quality) => {
        if (!quality) return 'gray';
        if (quality >= 8) return 'green';
        if (quality >= 6) return 'yellow';
        return 'red';
    };

    const getDurationColor = (hours) => {
        if (!hours) return 'gray';
        if (hours >= 8) return 'green';
        if (hours >= 6) return 'yellow';
        return 'red';
    };

    const handleEdit = (record) => {
        setEditingRecord(record);
        setShowForm(true);
    };

    const handleDelete = async (id) => {
        if (window.confirm('Are you sure you want to delete this sleep record?')) {
            deleteMutation.mutate(id);
        }
    };

    return (
        <div>
            {/* Filters */}
            <div className="bg-white rounded-lg shadow p-6 mb-6">
                <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900 mb-2">Sleep Records Management</h2>
                        <p className="text-gray-600">View and track resident sleep records.</p>
                    </div>
                    <button
                        onClick={() => {
                            setEditingRecord(null);
                            setShowForm(true);
                        }}
                        className="w-full sm:w-auto px-4 py-2 bg-[#25603E] text-white rounded-lg hover:bg-[#1B402D] transition-colors flex items-center justify-center space-x-2 text-sm md:text-base"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Add Sleep Record</span>
                    </button>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Date Range:</label>
                        <div className="flex flex-wrap gap-2">
                            {['all', 'today', 'week', 'month'].map((filter) => (
                                <button
                                    key={filter}
                                    onClick={() => setDateFilter(filter)}
                                    className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors capitalize ${
                                        dateFilter === filter
                                            ? 'bg-[#25603E] text-white'
                                            : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                                    }`}
                                >
                                    {filter}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Filter by Resident:</label>
                        <select
                            value={residentFilter}
                            onChange={(e) => setResidentFilter(e.target.value)}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                        >
                            <option value="">All Residents</option>
                            {residentOptions.map((resident) => (
                                <option key={resident.id} value={resident.id}>
                                    {resident.first_name} {resident.last_name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Search:</label>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                            <input
                                type="text"
                                placeholder="Search residents..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                            />
                        </div>
                    </div>
                </div>
            </div>

            {/* Sleep Records List */}
            {isLoading ? (
                <div className="text-center py-12">
                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[#25603E]"></div>
                    <p className="mt-4 text-gray-600">Loading sleep records...</p>
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {data?.data?.length > 0 ? (
                        data.data.map((record) => (
                            <div key={record.id} className="bg-white rounded-lg shadow p-6">
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <div className="flex items-center space-x-3 mb-3">
                                            <div>
                                                <h3 className="text-lg font-semibold text-gray-900">
                                                    {record.resident?.first_name} {record.resident?.last_name}
                                                </h3>
                                                <p className="text-sm text-gray-500">
                                                    {record.branch?.name} • {new Date(record.sleep_date).toLocaleDateString()}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                                            <div className="flex items-center space-x-2">
                                                <Clock className="w-4 h-4 text-gray-400" />
                                                <div>
                                                    <p className="text-xs text-gray-500">Sleep Time</p>
                                                    <p className="text-sm font-semibold text-gray-900">
                                                        {formatTime(record.sleep_time)}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="flex items-center space-x-2">
                                                <Clock className="w-4 h-4 text-gray-400" />
                                                <div>
                                                    <p className="text-xs text-gray-500">Wake Time</p>
                                                    <p className="text-sm font-semibold text-gray-900">
                                                        {formatTime(record.wake_time)}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="flex items-center space-x-2">
                                                <Moon className="w-4 h-4 text-gray-400" />
                                                <div>
                                                    <p className="text-xs text-gray-500">Duration</p>
                                                    <p className={`text-sm font-semibold ${
                                                        getDurationColor(record.total_sleep_hours) === 'green' ? 'text-green-600' :
                                                        getDurationColor(record.total_sleep_hours) === 'yellow' ? 'text-yellow-600' :
                                                        getDurationColor(record.total_sleep_hours) === 'red' ? 'text-red-600' :
                                                        'text-gray-600'
                                                    }`}>
                                                        {Number.isFinite(Number(record.total_sleep_hours)) ? Number(record.total_sleep_hours).toFixed(2) : 'N/A'} hrs
                                                    </p>
                                                </div>
                                            </div>

                                            {record.sleep_quality && (
                                                <div className="flex items-center space-x-2">
                                                    <Moon className="w-4 h-4 text-gray-400" />
                                                    <div>
                                                        <p className="text-xs text-gray-500">Quality</p>
                                                        <p className={`text-sm font-semibold ${
                                                            getQualityColor(record.sleep_quality) === 'green' ? 'text-green-600' :
                                                            getQualityColor(record.sleep_quality) === 'yellow' ? 'text-yellow-600' :
                                                            getQualityColor(record.sleep_quality) === 'red' ? 'text-red-600' :
                                                            'text-gray-600'
                                                        }`}>
                                                            {record.sleep_quality}/10
                                                        </p>
                                                    </div>
                                                </div>
                                            )}

                                            {record.restlessness_episodes !== null && (
                                                <div className="flex items-center space-x-2">
                                                    <Moon className="w-4 h-4 text-gray-400" />
                                                    <div>
                                                        <p className="text-xs text-gray-500">Restlessness</p>
                                                        <p className="text-sm font-semibold text-gray-900">
                                                            {record.restlessness_episodes} episodes
                                                        </p>
                                                    </div>
                                                </div>
                                            )}
                                        </div>

                                        {record.notes && (
                                            <div className="mt-4 p-3 bg-gray-50 rounded-lg">
                                                <p className="text-sm text-gray-700">
                                                    <span className="font-medium">Notes: </span>
                                                    {record.notes}
                                                </p>
                                            </div>
                                        )}

                                        {record.created_by && (
                                            <p className="text-xs text-gray-500 mt-2">
                                                Recorded by: {record.created_by?.name || 'Unknown'}
                                            </p>
                                        )}
                                    </div>

                                    <div className="flex space-x-2 ml-4">
                                        <button
                                            onClick={() => handleEdit(record)}
                                            className="p-2 text-[#25603E] hover:bg-green-50 rounded-lg transition-colors"
                                            title="Edit"
                                        >
                                            <Edit className="w-4 h-4" />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(record.id)}
                                            className="p-2 text-[#8B4513] hover:bg-amber-50 rounded-lg transition-colors"
                                            title="Delete"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="bg-white rounded-lg shadow p-12 text-center">
                            <Moon className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                            <p className="text-gray-600 text-lg font-medium">No sleep records found</p>
                            <p className="text-gray-500 text-sm mt-2">
                                {dateFilter === 'today' 
                                    ? 'No sleep records recorded today.' 
                                    : 'Try adjusting your filters or add a new sleep record.'}
                            </p>
                        </div>
                    )}
                </div>
            )}

            {/* Create/Edit Form Modal */}
            {showForm && (
                <SleepRecordForm
                    record={editingRecord}
                    residents={residentOptions}
                    isCaregiver={isCaregiver}
                    caregiverBranchId={caregiverBranchId}
                    caregiverBranchName={caregiverBranchName}
                    onClose={() => {
                        setShowForm(false);
                        setEditingRecord(null);
                    }}
                    onSuccess={() => {
                        setShowForm(false);
                        setEditingRecord(null);
                        queryClient.invalidateQueries(['sleep-records']);
                    }}
                />
            )}
        </div>
    );
}

// Sleep Record Form Component
function SleepRecordForm({ record, residents, isCaregiver, caregiverBranchId, caregiverBranchName, onClose, onSuccess }) {
    const [formData, setFormData] = useState({
        resident_id: record?.resident_id || '',
        branch_id: record?.branch_id || caregiverBranchId || '',
        sleep_date: record?.sleep_date || new Date().toISOString().split('T')[0],
        sleep_time: record?.sleep_time || '',
        wake_time: record?.wake_time || '',
        total_sleep_hours: record?.total_sleep_hours || '',
        sleep_quality: record?.sleep_quality || '',
        restlessness_episodes: record?.restlessness_episodes || 0,
        notes: record?.notes || '',
    });

    const [errors, setErrors] = useState({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Get branches from residents
    const branches = React.useMemo(() => {
        const branchMap = new Map();
        residents.forEach(resident => {
            if (resident.branch && !branchMap.has(resident.branch.id)) {
                branchMap.set(resident.branch.id, resident.branch);
            }
        });
        return Array.from(branchMap.values());
    }, [residents]);

    React.useEffect(() => {
        if (isCaregiver && caregiverBranchId && formData.branch_id !== caregiverBranchId) {
            setFormData((prev) => ({
                ...prev,
                branch_id: caregiverBranchId,
            }));
        }
    }, [isCaregiver, caregiverBranchId, formData.branch_id]);

    // Filter residents by selected branch
    const filteredResidents = React.useMemo(() => {
        if (isCaregiver && caregiverBranchId) {
            return residents.filter(r => String(r.branch_id) === String(caregiverBranchId));
        }
        if (!formData.branch_id) return residents;
        return residents.filter(r => r.branch_id == formData.branch_id);
    }, [isCaregiver, caregiverBranchId, formData.branch_id, residents]);

    React.useEffect(() => {
        if (formData.sleep_time && formData.wake_time) {
            const sleepTime = new Date(`2000-01-01T${formData.sleep_time}`);
            const wakeTime = new Date(`2000-01-01T${formData.wake_time}`);
            
            let calculatedWakeTime = wakeTime;
            if (calculatedWakeTime < sleepTime) {
                calculatedWakeTime = new Date(calculatedWakeTime.getTime() + 24 * 60 * 60 * 1000);
            }
            
            const diffMs = calculatedWakeTime - sleepTime;
            const diffHours = diffMs / (1000 * 60 * 60);
            setFormData(prev => ({...prev, total_sleep_hours: diffHours.toFixed(2)}));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [formData.sleep_time, formData.wake_time]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setErrors({});
        setIsSubmitting(true);

        try {
            const payload = {
                ...formData,
                resident_id: parseInt(formData.resident_id),
                branch_id: formData.branch_id ? parseInt(formData.branch_id) : null,
            };

            if (record) {
                await api.put(`/sleep-records/${record.id}`, payload);
            } else {
                await api.post('/sleep-records', payload);
            }
            onSuccess();
        } catch (error) {
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            } else {
                setErrors({ general: error.response?.data?.message || 'Failed to save sleep record' });
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="fixed inset-0 backdrop-blur-sm flex items-center justify-center z-50 p-4" style={{ backgroundColor: 'rgba(0, 0, 0, 0.1)' }}>
            <div className="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <div className="p-6">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4 md:mb-6">
                        <h2 className="text-2xl font-bold text-gray-900">
                            {record ? 'Edit Sleep Record' : 'Add Sleep Record'}
                        </h2>
                        <button
                            onClick={onClose}
                            className="text-gray-400 hover:text-gray-600"
                        >
                            ×
                        </button>
                    </div>

                    {errors.general && (
                        <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p className="text-sm text-red-800">{errors.general}</p>
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Branch *
                                </label>
                                {isCaregiver ? (
                                    <>
                                        <div className="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-900 min-h-[42px] flex items-center">
                                            <span>{caregiverBranchName || 'No branch assigned'}</span>
                                        </div>
                                        <input type="hidden" value={formData.branch_id} />
                                    </>
                                ) : (
                                    <select
                                        value={formData.branch_id}
                                        onChange={(e) => setFormData({...formData, branch_id: e.target.value, resident_id: ''})}
                                        required
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                    >
                                        <option value="">Select Branch</option>
                                        {branches.map(branch => (
                                            <option key={branch.id} value={branch.id}>{branch.name}</option>
                                        ))}
                                    </select>
                                )}
                                {errors.branch_id && <p className="text-xs text-red-600 mt-1">{errors.branch_id[0]}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Resident *
                                </label>
                                <select
                                    value={formData.resident_id}
                                    onChange={(e) => setFormData({...formData, resident_id: e.target.value})}
                                    required
                                    disabled={!formData.branch_id}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent disabled:bg-gray-100"
                                >
                                    <option value="">Select Resident</option>
                                    {filteredResidents.map(resident => (
                                        <option key={resident.id} value={resident.id}>
                                            {resident.first_name} {resident.last_name}
                                        </option>
                                    ))}
                                </select>
                                {errors.resident_id && <p className="text-xs text-red-600 mt-1">{errors.resident_id[0]}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Sleep Date *
                            </label>
                            <input
                                type="date"
                                value={formData.sleep_date}
                                onChange={(e) => setFormData({...formData, sleep_date: e.target.value})}
                                required
                                max={new Date().toISOString().split('T')[0]}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                            />
                            {errors.sleep_date && <p className="text-xs text-red-600 mt-1">{errors.sleep_date[0]}</p>}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Sleep Time *
                                </label>
                                <TimePicker
                                    value={formData.sleep_time}
                                    onChange={(value) => setFormData({...formData, sleep_time: value})}
                                    className={errors.sleep_time ? 'border-red-300' : ''}
                                />
                                {errors.sleep_time && <p className="text-xs text-red-600 mt-1">{errors.sleep_time[0]}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Wake Time *
                                </label>
                                <TimePicker
                                    value={formData.wake_time}
                                    onChange={(value) => setFormData({...formData, wake_time: value})}
                                    className={errors.wake_time ? 'border-red-300' : ''}
                                />
                                {errors.wake_time && <p className="text-xs text-red-600 mt-1">{errors.wake_time[0]}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Total Sleep Hours
                                </label>
                                <input
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    max="24"
                                    value={formData.total_sleep_hours}
                                    readOnly
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Sleep Quality (1-10)
                                </label>
                                <select
                                    value={formData.sleep_quality}
                                    onChange={(e) => setFormData({...formData, sleep_quality: e.target.value})}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                >
                                    <option value="">Select Quality</option>
                                    {[1,2,3,4,5,6,7,8,9,10].map(num => (
                                        <option key={num} value={num}>{num}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Restlessness Episodes
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    value={formData.restlessness_episodes}
                                    onChange={(e) => setFormData({...formData, restlessness_episodes: e.target.value})}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Notes
                            </label>
                            <textarea
                                value={formData.notes}
                                onChange={(e) => setFormData({...formData, notes: e.target.value})}
                                rows={3}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                placeholder="Additional notes about the sleep session..."
                            />
                        </div>

                        <div className="flex justify-end space-x-3 pt-4 border-t">
                            <button
                                type="button"
                                onClick={onClose}
                                className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={isSubmitting}
                                className="px-4 py-2 bg-[#25603E] text-white rounded-lg hover:bg-[#1B402D] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {isSubmitting ? 'Saving...' : (record ? 'Update' : 'Create')}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}

// TimePicker Component
function TimePicker({ value, onChange, className = '' }) {
    const [isOpen, setIsOpen] = useState(false);
    const [hours, setHours] = useState(() => {
        if (value) {
            const [h] = value.split(':');
            return parseInt(h) || 12;
        }
        return 12;
    });
    const [minutes, setMinutes] = useState(() => {
        if (value) {
            const [, m] = value.split(':');
            return parseInt(m) || 0;
        }
        return 0;
    });
    const [period, setPeriod] = useState(() => {
        if (value) {
            const [h] = value.split(':');
            const hour = parseInt(h) || 0;
            return hour >= 12 ? 'PM' : 'AM';
        }
        return 'AM';
    });

    React.useEffect(() => {
        if (value) {
            const [h, m] = value.split(':');
            const hour = parseInt(h) || 0;
            const min = parseInt(m) || 0;
            setHours(hour % 12 || 12);
            setMinutes(min);
            setPeriod(hour >= 12 ? 'PM' : 'AM');
        }
    }, [value]);

    const formatTime = (h, m, p) => {
        let hour24 = h;
        if (p === 'PM' && h !== 12) hour24 = h + 12;
        if (p === 'AM' && h === 12) hour24 = 0;
        return `${hour24.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
    };

    const handleTimeChange = (newHours, newMinutes, newPeriod) => {
        const timeValue = formatTime(newHours, newMinutes, newPeriod);
        onChange(timeValue);
        setIsOpen(false);
    };

    const hourOptions = Array.from({ length: 12 }, (_, i) => i + 1);
    const minuteOptions = Array.from({ length: 60 }, (_, i) => i);

    const displayValue = value 
        ? `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')} ${period}`
        : '--:-- --';

    return (
        <div className="relative">
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className={`w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent bg-white text-left flex items-center justify-between ${className}`}
            >
                <span className={value ? 'text-gray-900' : 'text-gray-400'}>
                    {displayValue}
                </span>
                <ChevronDown className={`w-4 h-4 text-gray-400 transition-transform ${isOpen ? 'transform rotate-180' : ''}`} />
            </button>
            
            {isOpen && (
                <>
                    <div 
                        className="fixed inset-0 z-10" 
                        onClick={() => setIsOpen(false)}
                    ></div>
                    <div className="absolute z-20 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-4 w-full">
                        <div className="flex items-center justify-center gap-2 mb-4">
                            {/* Hours */}
                            <select
                                value={hours}
                                onChange={(e) => {
                                    const newHours = parseInt(e.target.value);
                                    handleTimeChange(newHours, minutes, period);
                                }}
                                className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent text-center text-lg font-semibold"
                                onClick={(e) => e.stopPropagation()}
                            >
                                {hourOptions.map(h => (
                                    <option key={h} value={h}>{h.toString().padStart(2, '0')}</option>
                                ))}
                            </select>
                            
                            <span className="text-2xl font-bold text-gray-700">:</span>
                            
                            {/* Minutes */}
                            <select
                                value={minutes}
                                onChange={(e) => {
                                    const newMinutes = parseInt(e.target.value);
                                    handleTimeChange(hours, newMinutes, period);
                                }}
                                className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent text-center text-lg font-semibold"
                                onClick={(e) => e.stopPropagation()}
                            >
                                {minuteOptions.map(m => (
                                    <option key={m} value={m}>{m.toString().padStart(2, '0')}</option>
                                ))}
                            </select>
                            
                            {/* AM/PM */}
                            <select
                                value={period}
                                onChange={(e) => {
                                    const newPeriod = e.target.value;
                                    handleTimeChange(hours, minutes, newPeriod);
                                }}
                                className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent text-center text-lg font-semibold"
                                onClick={(e) => e.stopPropagation()}
                            >
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
