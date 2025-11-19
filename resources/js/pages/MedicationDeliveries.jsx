import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../services/api';
import { Truck, Plus, Search, Filter, Edit, Trash2, Calendar, Package, User } from 'lucide-react';
import SectionCard from '../components/SectionCard';
import Card from '../components/Card';

export default function MedicationDeliveries() {
    const queryClient = useQueryClient();
    const [search, setSearch] = useState('');
    const [branchFilter, setBranchFilter] = useState('');
    const [typeFilter, setTypeFilter] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
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

    // Fetch branches
    const { data: branchesData } = useQuery({
        queryKey: ['branches-options'],
        queryFn: async () => (await api.get('/branches', { params: { per_page: 100 } })).data,
    });

    // Fetch residents
    const { data: residentsData } = useQuery({
        queryKey: ['residents-list'],
        queryFn: async () => (await api.get('/residents', { params: { per_page: 100 } })).data,
    });

    // Fetch medications
    const { data: medicationsData } = useQuery({
        queryKey: ['medications-list'],
        queryFn: async () => (await api.get('/medications', { params: { per_page: 1000 } })).data,
    });

    // Build query params
    const queryParams = React.useMemo(() => {
        const params = { per_page: 50 };
        if (branchFilter) params.branch_id = branchFilter;
        if (typeFilter) params.delivery_type = typeFilter;
        if (statusFilter) params.status = statusFilter;
        return params;
    }, [branchFilter, typeFilter, statusFilter]);

    // Fetch deliveries
    const { data, isLoading, refetch } = useQuery({
        queryKey: ['medication-deliveries', queryParams],
        queryFn: async () => (await api.get('/medication-deliveries', { params: queryParams })).data,
    });

    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            await api.delete(`/medication-deliveries/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['medication-deliveries']);
        },
    });

    const deliveries = data?.data || [];
    const branches = branchesData?.data || [];
    const residents = residentsData?.data || [];
    const medications = medicationsData?.data || [];

    // Filter deliveries by search
    const filteredDeliveries = React.useMemo(() => {
        if (!search) return deliveries;
        const searchLower = search.toLowerCase();
        return deliveries.filter(d => 
            d.pharmacy_name?.toLowerCase().includes(searchLower) ||
            d.resident?.name?.toLowerCase().includes(searchLower) ||
            d.medication?.name?.toLowerCase().includes(searchLower) ||
            d.branch?.name?.toLowerCase().includes(searchLower)
        );
    }, [deliveries, search]);

    const handleDelete = (id) => {
        if (window.confirm('Are you sure you want to delete this medication delivery?')) {
            deleteMutation.mutate(id);
        }
    };

    const handleCloseForm = () => {
        setShowForm(false);
        setEditing(null);
    };

    const handleEdit = (delivery) => {
        setEditing(delivery);
        setShowForm(true);
    };

    const getStatusBadge = (status) => {
        const styles = {
            received: 'bg-yellow-100 text-yellow-800',
            verified: 'bg-blue-100 text-blue-800',
            stored: 'bg-green-100 text-green-800',
        };
        return (
            <span className={`px-2 py-1 rounded-full text-xs font-medium ${styles[status] || 'bg-gray-100 text-gray-800'}`}>
                {status ? status.charAt(0).toUpperCase() + status.slice(1) : 'N/A'}
            </span>
        );
    };

    const getTypeBadge = (type) => {
        const styles = {
            individual: 'bg-purple-100 text-purple-800',
            batch: 'bg-indigo-100 text-indigo-800',
        };
        return (
            <span className={`px-2 py-1 rounded-full text-xs font-medium ${styles[type] || 'bg-gray-100 text-gray-800'}`}>
                {type ? type.charAt(0).toUpperCase() + type.slice(1) : 'N/A'}
            </span>
        );
    };

    return (
        <div>
            <SectionCard>
                <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900 mb-2">Medication Deliveries</h2>
                        <p className="text-gray-600">Track medication deliveries from pharmacy.</p>
                    </div>
                    <button
                        onClick={() => {
                            setEditing(null);
                            setShowForm(true);
                        }}
                        className="w-full sm:w-auto px-4 py-2 bg-[#25603E] text-white rounded-lg hover:bg-[#1B402D] transition-colors flex items-center justify-center space-x-2"
                    >
                        <Plus className="w-4 h-4" />
                        <span>Add Delivery</span>
                    </button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input
                            type="text"
                            placeholder="Search deliveries..."
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
                        value={typeFilter}
                        onChange={(e) => setTypeFilter(e.target.value)}
                        className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                    >
                        <option value="">All Types</option>
                        <option value="individual">Individual</option>
                        <option value="batch">Batch</option>
                    </select>

                    <select
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                        className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                    >
                        <option value="">All Status</option>
                        <option value="received">Received</option>
                        <option value="verified">Verified</option>
                        <option value="stored">Stored</option>
                    </select>
                </div>

                {isLoading ? (
                    <div className="text-center py-12">
                        <p className="text-gray-500">Loading deliveries...</p>
                    </div>
                ) : filteredDeliveries.length === 0 ? (
                    <div className="text-center py-12">
                        <Truck className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-500">No medication deliveries found.</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-4">
                        {filteredDeliveries.map((delivery) => (
                            <Card key={delivery.id} className="p-4">
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2 mb-2">
                                            <Truck className="w-5 h-5 text-[#25603E]" />
                                            <h3 className="font-semibold text-gray-900">{delivery.pharmacy_name}</h3>
                                            {getTypeBadge(delivery.delivery_type)}
                                            {getStatusBadge(delivery.status)}
                                        </div>
                                        <div className="grid grid-cols-2 gap-2 text-sm text-gray-600">
                                            <div>
                                                <span className="font-medium">Branch:</span> {delivery.branch?.name || 'N/A'}
                                            </div>
                                            {delivery.delivery_type === 'individual' && (
                                                <>
                                                    <div>
                                                        <span className="font-medium">Resident:</span> {delivery.resident?.name || 'N/A'}
                                                    </div>
                                                    <div>
                                                        <span className="font-medium">Medication:</span> {delivery.medication?.name || 'N/A'}
                                                    </div>
                                                </>
                                            )}
                                            <div>
                                                <span className="font-medium">Quantity:</span> {delivery.quantity_received}
                                            </div>
                                            <div>
                                                <span className="font-medium">Received:</span> {new Date(delivery.received_date).toLocaleDateString()} {delivery.received_time}
                                            </div>
                                            <div>
                                                <span className="font-medium">Received By:</span> {delivery.received_by?.name || 'N/A'}
                                            </div>
                                        </div>
                                        {delivery.notes && (
                                            <div className="mt-2 text-sm text-gray-600">
                                                <span className="font-medium">Notes:</span> {delivery.notes}
                                            </div>
                                        )}
                                    </div>
                                    <div className="flex items-center gap-2 ml-4">
                                        <button
                                            onClick={() => handleEdit(delivery)}
                                            className="p-2 text-gray-600 hover:text-[#25603E] transition-colors"
                                            title="Edit"
                                        >
                                            <Edit className="w-4 h-4" />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(delivery.id)}
                                            className="p-2 text-gray-600 hover:text-red-600 transition-colors"
                                            title="Delete"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>
                )}
            </SectionCard>

            {showForm && (
                <MedicationDeliveryForm
                    record={editing}
                    branches={branches}
                    residents={residents}
                    medications={medications}
                    isCaregiver={isCaregiver}
                    caregiverBranchId={currentUser?.assigned_branch_id}
                    onClose={handleCloseForm}
                    onSuccess={() => {
                        queryClient.invalidateQueries(['medication-deliveries']);
                        handleCloseForm();
                    }}
                />
            )}
        </div>
    );
}

function MedicationDeliveryForm({ record, branches, residents, medications, isCaregiver, caregiverBranchId, onClose, onSuccess }) {
    const [formData, setFormData] = useState({
        branch_id: record?.branch_id || caregiverBranchId || '',
        delivery_type: record?.delivery_type || 'individual',
        resident_id: record?.resident_id || '',
        medication_id: record?.medication_id || '',
        pharmacy_name: record?.pharmacy_name || '',
        quantity_received: record?.quantity_received || '',
        received_date: record?.received_date || new Date().toISOString().split('T')[0],
        received_time: record?.received_time || new Date().toTimeString().slice(0, 5),
        status: record?.status || 'received',
        notes: record?.notes || '',
    });

    const [errors, setErrors] = useState({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Filter residents by branch
    const filteredResidents = React.useMemo(() => {
        if (!formData.branch_id) return [];
        return residents.filter(r => r.branch_id == formData.branch_id);
    }, [residents, formData.branch_id]);

    // Fetch medications dynamically based on branch and resident
    const { data: medicationsQueryData } = useQuery({
        queryKey: ['medications-for-delivery', formData.branch_id, formData.resident_id],
        queryFn: async () => {
            if (!formData.branch_id || !formData.resident_id || formData.delivery_type !== 'individual') {
                return { data: [] };
            }
            const params = {
                branch_id: formData.branch_id,
                resident_id: formData.resident_id,
                active_only: 'true',
                per_page: 1000
            };
            const response = await api.get('/medications', { params });
            return response.data;
        },
        enabled: formData.delivery_type === 'individual' && !!formData.branch_id && !!formData.resident_id,
    });

    // Use dynamically fetched medications or fallback to passed medications
    const availableMedications = medicationsQueryData?.data || medications || [];
    
    // Filter medications by resident and branch
    const filteredMedications = React.useMemo(() => {
        if (!formData.resident_id || formData.delivery_type !== 'individual') return [];
        return availableMedications.filter(m => 
            m.resident_id == formData.resident_id && 
            (!formData.branch_id || m.branch_id == formData.branch_id)
        );
    }, [availableMedications, formData.resident_id, formData.branch_id, formData.delivery_type]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setErrors({});
        setIsSubmitting(true);

        try {
            const payload = { ...formData };
            if (formData.delivery_type === 'batch') {
                payload.resident_id = null;
                payload.medication_id = null;
            }

            if (record) {
                await api.put(`/medication-deliveries/${record.id}`, payload);
            } else {
                await api.post('/medication-deliveries', payload);
            }

            onSuccess();
        } catch (error) {
            console.error('Error saving medication delivery:', error);
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            } else {
                setErrors({ general: [error.response?.data?.message || 'Failed to save medication delivery'] });
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
                            {record ? 'Edit Medication Delivery' : 'Add Medication Delivery'}
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
                                    onChange={(e) => setFormData({ ...formData, branch_id: e.target.value, resident_id: '', medication_id: '' })}
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
                                <label className="block text-sm font-medium text-gray-700 mb-1">Delivery Type *</label>
                                <select
                                    value={formData.delivery_type}
                                    onChange={(e) => setFormData({ ...formData, delivery_type: e.target.value, resident_id: '', medication_id: '' })}
                                    required
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                >
                                    <option value="individual">Individual Medication</option>
                                    <option value="batch">Batch Delivery</option>
                                </select>
                                {errors.delivery_type && <p className="text-xs text-red-600 mt-1">{errors.delivery_type[0]}</p>}
                            </div>

                            {formData.delivery_type === 'individual' && (
                                <>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Resident</label>
                                        <select
                                            value={formData.resident_id}
                                            onChange={(e) => setFormData({ ...formData, resident_id: e.target.value, medication_id: '' })}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                        >
                                            <option value="">Select Resident</option>
                                            {filteredResidents.map(resident => (
                                                <option key={resident.id} value={resident.id}>{resident.name}</option>
                                            ))}
                                        </select>
                                        {errors.resident_id && <p className="text-xs text-red-600 mt-1">{errors.resident_id[0]}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Medication *</label>
                                        <select
                                            value={formData.medication_id}
                                            onChange={(e) => setFormData({ ...formData, medication_id: e.target.value })}
                                            required
                                            disabled={!formData.resident_id}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        >
                                            <option value="">
                                                {!formData.resident_id ? 'Select Resident First' : filteredMedications.length === 0 ? 'No medications found' : 'Select Medication'}
                                            </option>
                                            {filteredMedications.map(medication => {
                                                const drugName = medication.drug?.name || medication.name || 'Unknown Drug';
                                                const displayName = medication.name || drugName;
                                                return (
                                                    <option key={medication.id} value={medication.id}>
                                                        {displayName} {medication.drug?.name && medication.name !== medication.drug.name ? `(${medication.drug.name})` : ''}
                                                    </option>
                                                );
                                            })}
                                        </select>
                                        {errors.medication_id && <p className="text-xs text-red-600 mt-1">{errors.medication_id[0]}</p>}
                                    </div>
                                </>
                            )}

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Pharmacy Name *</label>
                                <input
                                    type="text"
                                    value={formData.pharmacy_name}
                                    onChange={(e) => setFormData({ ...formData, pharmacy_name: e.target.value })}
                                    required
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                />
                                {errors.pharmacy_name && <p className="text-xs text-red-600 mt-1">{errors.pharmacy_name[0]}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Quantity Received *</label>
                                <input
                                    type="text"
                                    value={formData.quantity_received}
                                    onChange={(e) => setFormData({ ...formData, quantity_received: e.target.value })}
                                    required
                                    placeholder="e.g., 30 tablets, 2 bottles"
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                />
                                {errors.quantity_received && <p className="text-xs text-red-600 mt-1">{errors.quantity_received[0]}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Received Date *</label>
                                <input
                                    type="date"
                                    value={formData.received_date}
                                    onChange={(e) => setFormData({ ...formData, received_date: e.target.value })}
                                    required
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                />
                                {errors.received_date && <p className="text-xs text-red-600 mt-1">{errors.received_date[0]}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Received Time *</label>
                                <input
                                    type="time"
                                    value={formData.received_time}
                                    onChange={(e) => setFormData({ ...formData, received_time: e.target.value })}
                                    required
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                />
                                {errors.received_time && <p className="text-xs text-red-600 mt-1">{errors.received_time[0]}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                                <select
                                    value={formData.status}
                                    onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                                    required
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#25603E] focus:border-transparent"
                                >
                                    <option value="received">Received</option>
                                    <option value="verified">Verified</option>
                                    <option value="stored">Stored</option>
                                </select>
                                {errors.status && <p className="text-xs text-red-600 mt-1">{errors.status[0]}</p>}
                            </div>
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

