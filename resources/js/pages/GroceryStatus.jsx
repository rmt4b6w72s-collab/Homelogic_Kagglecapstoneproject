import React, { useState, useMemo } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../services/api';
import { ShoppingCart, Plus, Search, Filter, Edit, Trash2, Calendar, Clock, CheckCircle, AlertCircle, Package } from 'lucide-react';
import SectionCard from '../components/SectionCard';
import Card from '../components/Card';

export default function GroceryStatus() {
    const queryClient = useQueryClient();
    const [search, setSearch] = useState('');
    const [branchFilter, setBranchFilter] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [weekFilter, setWeekFilter] = useState('');
    const [showForm, setShowForm] = useState(false);
    const [editing, setEditing] = useState(null);
    const [currentUser, setCurrentUser] = useState(null);

    // Fetch current user
    React.useEffect(() => {
        const fetchUser = async () => {
            try {
                const response = await api.get('/user');
                setCurrentUser(response.data);
            } catch (err) {
                console.error('Failed to fetch current user:', err);
            }
        };
        fetchUser();
    }, []);

    // Check if user is a caregiver
    const isCaregiver = React.useMemo(() => {
        if (!currentUser) return false;
        const role = currentUser.role?.toLowerCase().trim() || '';
        const roleNormalized = role.replace(/[\s_]/g, '');
        return roleNormalized === 'caregiver' || (role.includes('care') && role.includes('giver'));
    }, [currentUser]);

    // Auto-set branch filter for caregivers
    React.useEffect(() => {
        if (isCaregiver && currentUser?.assigned_branch_id) {
            setBranchFilter(String(currentUser.assigned_branch_id));
        }
    }, [isCaregiver, currentUser?.assigned_branch_id]);

    // Fetch branches
    const { data: branchesData } = useQuery({
        queryKey: ['branches-options'],
        queryFn: async () => (await api.get('/branches', { params: { per_page: 100 } })).data,
    });

    // Build query params
    const queryParams = useMemo(() => {
        const params = { per_page: 50 };
        if (branchFilter) params.branch_id = branchFilter;
        if (statusFilter) params.status = statusFilter;
        if (weekFilter) params.week_start_date = weekFilter;
        return params;
    }, [branchFilter, statusFilter, weekFilter]);

    // Fetch grocery status updates
    const { data, isLoading, refetch } = useQuery({
        queryKey: ['grocery-status-updates', queryParams],
        queryFn: async () => (await api.get('/grocery-status-updates', { params: queryParams })).data,
    });

    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            await api.delete(`/grocery-status-updates/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['grocery-status-updates']);
        },
    });

    const updates = data?.data || [];
    const branches = branchesData?.data || [];

    // Get current week's Monday
    const getCurrentWeekMonday = () => {
        const today = new Date();
        const day = today.getDay();
        const diff = today.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
        const monday = new Date(today.setDate(diff));
        return monday.toISOString().split('T')[0];
    };

    // Filter updates by search
    const filteredUpdates = useMemo(() => {
        let filtered = updates;
        
        if (search) {
            const searchLower = search.toLowerCase();
            filtered = filtered.filter(u => 
                u.branch?.name?.toLowerCase().includes(searchLower) ||
                u.items_needed?.toLowerCase().includes(searchLower) ||
                u.items_received?.toLowerCase().includes(searchLower) ||
                u.notes?.toLowerCase().includes(searchLower)
            );
        }

        // Group by week
        const grouped = {};
        filtered.forEach(update => {
            const weekKey = update.week_start_date;
            if (!grouped[weekKey]) {
                grouped[weekKey] = [];
            }
            grouped[weekKey].push(update);
        });

        return grouped;
    }, [updates, search]);

    const handleDelete = (id) => {
        if (window.confirm('Are you sure you want to delete this grocery status update?')) {
            deleteMutation.mutate(id);
        }
    };

    const handleCloseForm = () => {
        setShowForm(false);
        setEditing(null);
    };

    const handleEdit = (update) => {
        setEditing(update);
        setShowForm(true);
    };

    const getStatusBadge = (status) => {
        const styles = {
            pending: 'bg-gray-100 text-gray-800',
            in_progress: 'bg-yellow-100 text-yellow-800',
            completed: 'bg-green-100 text-green-800',
            needs_attention: 'bg-red-100 text-red-800',
        };
        const icons = {
            pending: <Clock className="w-3 h-3" />,
            in_progress: <Package className="w-3 h-3" />,
            completed: <CheckCircle className="w-3 h-3" />,
            needs_attention: <AlertCircle className="w-3 h-3" />,
        };
        return (
            <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium ${styles[status] || 'bg-gray-100 text-gray-800'}`}>
                {icons[status]}
                {status ? status.replace('_', ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') : 'N/A'}
            </span>
        );
    };

    const formatWeekRange = (weekStartDate) => {
        const start = new Date(weekStartDate);
        const end = new Date(start);
        end.setDate(end.getDate() + 6);
        return `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
    };

    // Get latest status for current week
    const currentWeekMonday = getCurrentWeekMonday();
    const currentWeekUpdate = useMemo(() => {
        if (!updates.length) return null;
        const weekUpdates = updates.filter(u => u.week_start_date === currentWeekMonday);
        return weekUpdates.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0] || null;
    }, [updates, currentWeekMonday]);

    return (
        <div>
            <SectionCard>
                <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900 mb-2">Grocery Status Updates</h2>
                        <p className="text-gray-600">Track weekly grocery status updates for each branch.</p>
                    </div>
                    <button
                        onClick={() => {
                            setEditing(null);
                            setShowForm(true);
                        }}
                        className="w-full sm:w-auto px-4 py-2 bg-[#25603E] text-white rounded-lg hover:bg-[#1B402D] transition-colors flex items-center justify-center space-x-2"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Add Update</span>
                    </button>
                </div>

                {/* Current Week Status Highlight */}
                {currentWeekUpdate && (
                    <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="font-semibold text-blue-900 mb-1">Current Week Status</h3>
                                <p className="text-sm text-blue-700">
                                    Week of {formatWeekRange(currentWeekUpdate.week_start_date)} - {currentWeekUpdate.branch?.name}
                                </p>
                            </div>
                            {getStatusBadge(currentWeekUpdate.status)}
                        </div>
                    </div>
                )}

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input
                            type="text"
                            placeholder="Search updates..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                        />
                    </div>

                    {!isCaregiver && (
                        <select
                            value={branchFilter}
                            onChange={(e) => setBranchFilter(e.target.value)}
                            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                        >
                            <option value="">All Branches</option>
                            {branches.map(branch => (
                                <option key={branch.id} value={branch.id}>{branch.name}</option>
                            ))}
                        </select>
                    )}

                    <select
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                        className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                    >
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="needs_attention">Needs Attention</option>
                    </select>

                    <input
                        type="week"
                        value={weekFilter}
                        onChange={(e) => setWeekFilter(e.target.value ? new Date(e.target.value).toISOString().split('T')[0] : '')}
                        className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                        placeholder="Filter by week"
                    />
                </div>

                {isLoading ? (
                    <div className="text-center py-12">
                        <p className="text-gray-500">Loading grocery status updates...</p>
                    </div>
                ) : Object.keys(filteredUpdates).length === 0 ? (
                    <div className="text-center py-12">
                        <ShoppingCart className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-500">No grocery status updates found.</p>
                    </div>
                ) : (
                    <div className="space-y-6">
                        {Object.entries(filteredUpdates)
                            .sort(([a], [b]) => new Date(b) - new Date(a))
                            .map(([weekStart, weekUpdates]) => (
                                <div key={weekStart} className="border border-gray-200 rounded-lg p-4">
                                    <div className="flex items-center justify-between mb-4">
                                        <div className="flex items-center gap-2">
                                            <Calendar className="w-5 h-5 text-[#25603E]" />
                                            <h3 className="font-semibold text-gray-900">
                                                Week of {formatWeekRange(weekStart)}
                                            </h3>
                                            <span className="text-sm text-gray-500">
                                                ({weekUpdates.length} update{weekUpdates.length !== 1 ? 's' : ''})
                                            </span>
                                        </div>
                                        {weekUpdates[0]?.branch && (
                                            <span className="text-sm text-gray-600">
                                                {weekUpdates[0].branch.name}
                                            </span>
                                        )}
                                    </div>
                                    
                                    <div className="space-y-3">
                                        {weekUpdates
                                            .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
                                            .map((update) => (
                                                <Card key={update.id} className="p-4">
                                                    <div className="flex items-start justify-between">
                                                        <div className="flex-1">
                                                            <div className="flex items-center gap-2 mb-2">
                                                                {getStatusBadge(update.status)}
                                                                <span className="text-sm text-gray-500">
                                                                    Updated by {update.updated_by?.name || 'N/A'} on {new Date(update.created_at).toLocaleDateString()}
                                                                </span>
                                                            </div>
                                                            
                                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                                                {update.items_needed && (
                                                                    <div>
                                                                        <span className="font-medium text-gray-700">Items Needed:</span>
                                                                        <p className="text-gray-600 mt-1">{update.items_needed}</p>
                                                                    </div>
                                                                )}
                                                                {update.items_received && (
                                                                    <div>
                                                                        <span className="font-medium text-gray-700">Items Received:</span>
                                                                        <p className="text-gray-600 mt-1">{update.items_received}</p>
                                                                    </div>
                                                                )}
                                                            </div>
                                                            
                                                            {update.notes && (
                                                                <div className="mt-3">
                                                                    <span className="font-medium text-gray-700">Notes:</span>
                                                                    <p className="text-gray-600 mt-1">{update.notes}</p>
                                                                </div>
                                                            )}
                                                            
                                                            {update.completed_at && (
                                                                <div className="mt-2 text-xs text-gray-500">
                                                                    Completed: {new Date(update.completed_at).toLocaleString()}
                                                                </div>
                                                            )}
                                                        </div>
                                                        
                                                        <div className="flex items-center gap-2 ml-4">
                                                            <button
                                                                onClick={() => handleEdit(update)}
                                                                className="p-2 text-gray-600 hover:text-[#25603E] transition-colors"
                                                                title="Edit"
                                                            >
                                                                <Edit className="w-4 h-4" />
                                                            </button>
                                                            {(!isCaregiver || update.updated_by?.id === currentUser?.id) && (
                                                                <button
                                                                    onClick={() => handleDelete(update.id)}
                                                                    className="p-2 text-gray-600 hover:text-red-600 transition-colors"
                                                                    title="Delete"
                                                                >
                                                                    <Trash2 className="w-4 h-4" />
                                                                </button>
                                                            )}
                                                        </div>
                                                    </div>
                                                </Card>
                                            ))}
                                    </div>
                                </div>
                            ))}
                    </div>
                )}
            </SectionCard>

            {showForm && (
                <GroceryStatusForm
                    record={editing}
                    branches={branches}
                    isCaregiver={isCaregiver}
                    caregiverBranchId={currentUser?.assigned_branch_id}
                    onClose={handleCloseForm}
                    onSuccess={() => {
                        queryClient.invalidateQueries(['grocery-status-updates']);
                        handleCloseForm();
                    }}
                />
            )}
        </div>
    );
}

function GroceryStatusForm({ record, branches, isCaregiver, caregiverBranchId, onClose, onSuccess }) {
    // Get current Monday
    const getCurrentMonday = () => {
        const today = new Date();
        const day = today.getDay();
        const diff = today.getDate() - day + (day === 0 ? -6 : 1);
        const monday = new Date(today.setDate(diff));
        return monday.toISOString().split('T')[0];
    };

    const [formData, setFormData] = useState({
        branch_id: record?.branch_id || caregiverBranchId || '',
        week_start_date: record?.week_start_date || getCurrentMonday(),
        status: record?.status || 'pending',
        items_needed: record?.items_needed || '',
        items_received: record?.items_received || '',
        notes: record?.notes || '',
    });

    const [errors, setErrors] = useState({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setErrors({});
        setIsSubmitting(true);

        try {
            const payload = { ...formData };
            
            if (record) {
                await api.put(`/grocery-status-updates/${record.id}`, payload);
            } else {
                await api.post('/grocery-status-updates', payload);
            }

            onSuccess();
        } catch (error) {
            console.error('Error saving grocery status update:', error);
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            } else {
                setErrors({ general: [error.response?.data?.message || 'Failed to save grocery status update'] });
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div className="p-6">
                    <div className="flex items-center justify-between mb-6">
                        <h2 className="text-xl font-semibold text-gray-900">
                            {record ? 'Edit Grocery Status Update' : 'Add Grocery Status Update'}
                        </h2>
                        <button
                            onClick={onClose}
                            className="text-gray-400 hover:text-gray-600"
                        >
                            ×
                        </button>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        {errors.general && (
                            <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                                {errors.general[0]}
                            </div>
                        )}

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Branch *</label>
                                <select
                                    value={formData.branch_id}
                                    onChange={(e) => setFormData({ ...formData, branch_id: e.target.value })}
                                    required
                                    disabled={isCaregiver}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                >
                                    <option value="">Select Branch</option>
                                    {branches.map(branch => (
                                        <option key={branch.id} value={branch.id}>{branch.name}</option>
                                    ))}
                                </select>
                                {errors.branch_id && <p className="text-xs text-red-600 mt-1">{errors.branch_id[0]}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Week Start Date (Monday) *</label>
                                <input
                                    type="date"
                                    value={formData.week_start_date}
                                    onChange={(e) => setFormData({ ...formData, week_start_date: e.target.value })}
                                    required
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                />
                                <p className="text-xs text-gray-500 mt-1">Select any date in the week - it will be adjusted to Monday</p>
                                {errors.week_start_date && <p className="text-xs text-red-600 mt-1">{errors.week_start_date[0]}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                                <select
                                    value={formData.status}
                                    onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                                    required
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                >
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="needs_attention">Needs Attention</option>
                                </select>
                                {errors.status && <p className="text-xs text-red-600 mt-1">{errors.status[0]}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Items Needed</label>
                            <textarea
                                value={formData.items_needed}
                                onChange={(e) => setFormData({ ...formData, items_needed: e.target.value })}
                                rows={3}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                placeholder="List items that are needed..."
                            />
                            {errors.items_needed && <p className="text-xs text-red-600 mt-1">{errors.items_needed[0]}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Items Received</label>
                            <textarea
                                value={formData.items_received}
                                onChange={(e) => setFormData({ ...formData, items_received: e.target.value })}
                                rows={3}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                placeholder="List items that have been received..."
                            />
                            {errors.items_received && <p className="text-xs text-red-600 mt-1">{errors.items_received[0]}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea
                                value={formData.notes}
                                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                rows={3}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                placeholder="Enter any additional notes..."
                            />
                            {errors.notes && <p className="text-xs text-red-600 mt-1">{errors.notes[0]}</p>}
                        </div>

                        <div className="flex items-center justify-end space-x-3 pt-4 border-t">
                            <button
                                type="button"
                                onClick={onClose}
                                className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={isSubmitting}
                                className="px-4 py-2 bg-[#25603E] text-white rounded-lg hover:bg-[#1B402D] disabled:opacity-50"
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

