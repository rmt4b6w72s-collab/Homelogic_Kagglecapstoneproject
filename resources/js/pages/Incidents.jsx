import React, { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { useForm, FormProvider } from 'react-hook-form';
import api from '../services/api';
import { 
    AlertTriangle, Plus, Edit, Trash2, Eye, Filter, X, 
    CheckCircle, Lock, Clock, User, MapPin, Calendar,
    ChevronDown, Search, FileText, Image as ImageIcon
} from 'lucide-react';
import Card from '../components/Card';
import SectionCard from '../components/SectionCard';
import FormInput from '../components/forms/FormInput';
import FormTextarea from '../components/forms/FormTextarea';
import FormSelect from '../components/forms/FormSelect';
import { toast } from 'sonner';

const SEVERITY_COLORS = {
    critical: 'bg-red-100 text-red-800 border-red-300',
    high: 'bg-orange-100 text-orange-800 border-orange-300',
    medium: 'bg-yellow-100 text-yellow-800 border-yellow-300',
    low: 'bg-green-100 text-green-800 border-green-300',
};

const PRIORITY_COLORS = {
    critical: 'bg-red-100 text-red-800 border-red-300',
    high: 'bg-orange-100 text-orange-800 border-orange-300',
    medium: 'bg-yellow-100 text-yellow-800 border-yellow-300',
    low: 'bg-blue-100 text-blue-800 border-blue-300',
};

const STATUS_COLORS = {
    open: 'bg-yellow-100 text-yellow-800 border-yellow-300',
    in_progress: 'bg-blue-100 text-blue-800 border-blue-300',
    resolved: 'bg-green-100 text-green-800 border-green-300',
    closed: 'bg-gray-100 text-gray-800 border-gray-300',
    on_hold: 'bg-red-100 text-red-800 border-red-300',
};

const INCIDENT_TYPES = [
    'Fall',
    'Medication Error',
    'Behavioral Incident',
    'Medical Emergency',
    'Equipment Malfunction',
    'Security Breach',
    'Fire/Safety',
    'Food Safety',
    'Infection Control',
    'Transportation',
    'Communication Error',
    'Environmental Hazard',
    'Staff Injury',
    'Resident Injury',
    'Property Damage',
];

export default function Incidents() {
    const queryClient = useQueryClient();
    const navigate = useNavigate();
    const [searchParams, setSearchParams] = useSearchParams();
    
    const [showForm, setShowForm] = useState(false);
    const [showViewModal, setShowViewModal] = useState(false);
    const [selectedIncident, setSelectedIncident] = useState(null);
    const [viewMode, setViewMode] = useState('list');
    const [filters, setFilters] = useState({
        status: searchParams.get('status') || 'all',
        priority: searchParams.get('priority') || 'all',
        severity: searchParams.get('severity') || 'all',
        incident_type: searchParams.get('incident_type') || 'all',
        resident_id: searchParams.get('resident_id') || '',
        branch_id: searchParams.get('branch_id') || '',
        assigned_to: searchParams.get('assigned_to') || 'all',
        search: searchParams.get('search') || '',
        date_from: searchParams.get('date_from') || '',
        date_to: searchParams.get('date_to') || '',
    });
    const [showFilters, setShowFilters] = useState(false);
    const [attachments, setAttachments] = useState([]);
    
    // Initialize react-hook-form
    const methods = useForm({
        defaultValues: {
            resident_id: '',
            branch_id: '',
            incident_type: '',
            description: '',
            incident_date: new Date().toISOString().slice(0, 16),
            location: '',
            severity: 'low',
            priority: 'medium',
            status: 'open',
            action_taken: '',
            witnesses: '',
            follow_up: '',
            assigned_to: '',
        },
    });

    // Fetch incidents
    const { data, isLoading, error, refetch } = useQuery({
        queryKey: ['incidents', filters],
        queryFn: async () => {
            const params = { per_page: 50 };
            Object.keys(filters).forEach(key => {
                if (filters[key] && filters[key] !== 'all') {
                    params[key] = filters[key];
                }
            });
            const response = await api.get('/incidents', { params });
            return response.data;
        },
        retry: 1,
    });

    // Watch branch_id from form to fetch residents and reset resident when branch changes
    const branchId = methods.watch('branch_id');
    
    useEffect(() => {
        if (branchId) {
            methods.setValue('resident_id', '');
        }
    }, [branchId, methods]);
    
    // Fetch residents
    const { data: residentsData } = useQuery({
        queryKey: ['residents-list', branchId],
        queryFn: async () => {
            const params = { per_page: 100 };
            if (branchId) params.branch_id = branchId;
            return (await api.get('/residents', { params })).data;
        },
        enabled: !!branchId, // Only fetch when branch is selected
    });

    // Fetch branches
    const { data: branchesData } = useQuery({
        queryKey: ['branches-list'],
        queryFn: async () => {
            const response = await api.get('/branches', { params: { per_page: 100 } });
            const branches = response.data?.data || response.data || [];
            return {
                ...response.data,
                data: branches.filter(b => b.is_active !== false)
            };
        },
    });

    // Fetch users for assignment
    const { data: usersData } = useQuery({
        queryKey: ['users-list'],
        queryFn: async () => {
            return (await api.get('/users', { params: { per_page: 100 } })).data;
        },
    });

    const createMutation = useMutation({
        mutationFn: async (formDataToSend) => {
            return await api.post('/incidents', formDataToSend, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['incidents']);
            handleCloseForm();
            toast.success('Incident created successfully');
        },
        onError: (error) => {
            console.error('Error creating incident:', error);
            toast.error(error.response?.data?.message || 'Failed to create incident');
        },
    });

    const updateMutation = useMutation({
        mutationFn: async ({ id, data }) => {
            return await api.put(`/incidents/${id}`, data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['incidents']);
            handleCloseForm();
            toast.success('Incident updated successfully');
        },
        onError: (error) => {
            console.error('Error updating incident:', error);
            toast.error(error.response?.data?.message || 'Failed to update incident');
        },
    });

    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            return await api.delete(`/incidents/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['incidents']);
            toast.success('Incident deleted successfully');
        },
        onError: (error) => {
            console.error('Error deleting incident:', error);
            toast.error(error.response?.data?.message || 'Failed to delete incident');
        },
    });

    const markResolvedMutation = useMutation({
        mutationFn: async ({ id, notes }) => {
            return await api.post(`/incidents/${id}/mark-resolved`, { notes });
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['incidents']);
            toast.success('Incident marked as resolved');
        },
    });

    const markClosedMutation = useMutation({
        mutationFn: async ({ id, notes }) => {
            return await api.post(`/incidents/${id}/mark-closed`, { notes });
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['incidents']);
            toast.success('Incident marked as closed');
        },
    });

    const handleOpenForm = (incident = null) => {
        if (incident) {
            setSelectedIncident(incident);
            methods.reset({
                resident_id: incident.resident_id || '',
                branch_id: incident.branch_id || '',
                incident_type: incident.incident_type || '',
                description: incident.description || '',
                incident_date: incident.incident_date ? new Date(incident.incident_date).toISOString().slice(0, 16) : new Date().toISOString().slice(0, 16),
                location: incident.location || '',
                severity: incident.severity || 'low',
                priority: incident.priority || 'medium',
                status: incident.status || 'open',
                action_taken: incident.action_taken || '',
                witnesses: incident.witnesses || '',
                follow_up: incident.follow_up || '',
                assigned_to: incident.assigned_to || '',
            });
        } else {
            setSelectedIncident(null);
            methods.reset({
                resident_id: '',
                branch_id: '',
                incident_type: '',
                description: '',
                incident_date: new Date().toISOString().slice(0, 16),
                location: '',
                severity: 'low',
                priority: 'medium',
                status: 'open',
                action_taken: '',
                witnesses: '',
                follow_up: '',
                assigned_to: '',
            });
        }
        setAttachments([]);
        setShowForm(true);
    };

    const handleCloseForm = () => {
        setShowForm(false);
        setSelectedIncident(null);
        methods.reset();
        setAttachments([]);
    };

    const handleSubmit = (data) => {
        if (selectedIncident) {
            updateMutation.mutate({ id: selectedIncident.id, data });
        } else {
            // For create, we need to handle file uploads
            const formDataToSend = new FormData();
            
            Object.keys(data).forEach(key => {
                if (data[key] && key !== 'attachments') {
                    formDataToSend.append(key, data[key]);
                }
            });

            // Add attachments
            attachments.forEach((file, index) => {
                if (file instanceof File) {
                    formDataToSend.append(`attachments[${index}][file]`, file);
                    formDataToSend.append(`attachments[${index}][file_type]`, file.type.startsWith('image/') ? 'photo' : 'document');
                }
            });

            createMutation.mutate(formDataToSend);
        }
    };

    const handleFilterChange = (key, value) => {
        const newFilters = { ...filters, [key]: value };
        setFilters(newFilters);
        
        // Update URL params
        const newParams = new URLSearchParams();
        Object.keys(newFilters).forEach(k => {
            if (newFilters[k] && newFilters[k] !== 'all') {
                newParams.set(k, newFilters[k]);
            }
        });
        setSearchParams(newParams);
    };

    const clearFilters = () => {
        const clearedFilters = {
            status: 'all',
            priority: 'all',
            severity: 'all',
            incident_type: 'all',
            resident_id: '',
            branch_id: '',
            assigned_to: 'all',
            search: '',
            date_from: '',
            date_to: '',
        };
        setFilters(clearedFilters);
        setSearchParams({});
    };

    const incidents = data?.data || [];
    const residents = residentsData?.data || [];
    const branches = branchesData?.data || [];
    const users = usersData?.data || [];

    // If form is open, show form as full page (like Expenses form)
    if (showForm) {
        return (
            <IncidentForm
                record={selectedIncident}
                branches={branches}
                residents={residents}
                users={users}
                attachments={attachments}
                setAttachments={setAttachments}
                onClose={handleCloseForm}
                onSuccess={() => {
                    handleCloseForm();
                    queryClient.invalidateQueries(['incidents']);
                }}
                createMutation={createMutation}
                updateMutation={updateMutation}
                methods={methods}
                branchId={branchId}
            />
        );
    }

    return (
        <div className="p-6 space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-2">
                        <AlertTriangle className="w-8 h-8 text-red-600" />
                        Incidents
                    </h1>
                    <p className="text-gray-600 mt-1">Manage and track facility incidents</p>
                </div>
                <button
                    onClick={() => handleOpenForm()}
                    className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                    <Plus className="w-5 h-5" />
                    New Incident
                </button>
            </div>

            {/* Filters */}
            <Card>
                <div className="flex items-center justify-between mb-4">
                    <button
                        onClick={() => setShowFilters(!showFilters)}
                        className="flex items-center gap-2 text-gray-700 hover:text-gray-900"
                    >
                        <Filter className="w-5 h-5" />
                        Filters
                        {showFilters && <ChevronDown className="w-4 h-4" />}
                    </button>
                    {(filters.search || filters.status !== 'all' || filters.priority !== 'all' || 
                      filters.severity !== 'all' || filters.incident_type !== 'all') && (
                        <button
                            onClick={clearFilters}
                            className="text-sm text-red-600 hover:text-red-700"
                        >
                            Clear Filters
                        </button>
                    )}
                </div>

                {showFilters && (
                    <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 pt-4 border-t">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input
                                    type="text"
                                    value={filters.search}
                                    onChange={(e) => handleFilterChange('search', e.target.value)}
                                    placeholder="Search incidents..."
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                value={filters.status}
                                onChange={(e) => handleFilterChange('status', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="all">All Statuses</option>
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                                <option value="on_hold">On Hold</option>
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                            <select
                                value={filters.priority}
                                onChange={(e) => handleFilterChange('priority', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="all">All Priorities</option>
                                <option value="critical">Critical</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                            <select
                                value={filters.severity}
                                onChange={(e) => handleFilterChange('severity', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="all">All Severities</option>
                                <option value="critical">Critical</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Incident Type</label>
                            <select
                                value={filters.incident_type}
                                onChange={(e) => handleFilterChange('incident_type', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="all">All Types</option>
                                {INCIDENT_TYPES.map(type => (
                                    <option key={type} value={type}>{type}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                            <input
                                type="date"
                                value={filters.date_from}
                                onChange={(e) => handleFilterChange('date_from', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                            <input
                                type="date"
                                value={filters.date_to}
                                onChange={(e) => handleFilterChange('date_to', e.target.value)}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                    </div>
                )}
            </Card>

            {/* Incidents List */}
            {isLoading ? (
                <div className="text-center py-12">
                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p className="mt-2 text-gray-600">Loading incidents...</p>
                </div>
            ) : error ? (
                <Card>
                    <div className="text-center py-12">
                        <AlertTriangle className="w-12 h-12 text-red-500 mx-auto mb-4" />
                        <p className="text-red-600">Failed to load incidents</p>
                        <button
                            onClick={() => refetch()}
                            className="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        >
                            Retry
                        </button>
                    </div>
                </Card>
            ) : incidents.length === 0 ? (
                <Card>
                    <div className="text-center py-12">
                        <AlertTriangle className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-600">No incidents found</p>
                        <button
                            onClick={() => handleOpenForm()}
                            className="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        >
                            Create First Incident
                        </button>
                    </div>
                </Card>
            ) : (
                <div className="space-y-4">
                    {incidents.map((incident) => (
                        <Card key={incident.id} className="hover:shadow-lg transition">
                            <div className="flex items-start justify-between">
                                <div className="flex-1">
                                    <div className="flex items-center gap-3 mb-2">
                                        <span className="font-mono text-sm font-semibold text-blue-600">
                                            {incident.incident_number}
                                        </span>
                                        <span className={`px-2 py-1 rounded text-xs font-medium border ${SEVERITY_COLORS[incident.severity] || SEVERITY_COLORS.low}`}>
                                            {incident.severity?.toUpperCase()}
                                        </span>
                                        <span className={`px-2 py-1 rounded text-xs font-medium border ${PRIORITY_COLORS[incident.priority] || PRIORITY_COLORS.medium}`}>
                                            {incident.priority?.toUpperCase()}
                                        </span>
                                        <span className={`px-2 py-1 rounded text-xs font-medium border ${STATUS_COLORS[incident.status] || STATUS_COLORS.open}`}>
                                            {incident.status?.replace('_', ' ').toUpperCase()}
                                        </span>
                                    </div>

                                    <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                        {incident.incident_type}
                                    </h3>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-600 mb-3">
                                        <div className="flex items-center gap-2">
                                            <User className="w-4 h-4" />
                                            <span>
                                                {incident.resident?.first_name} {incident.resident?.last_name}
                                            </span>
                                        </div>
                                        {incident.location && (
                                            <div className="flex items-center gap-2">
                                                <MapPin className="w-4 h-4" />
                                                <span>{incident.location}</span>
                                            </div>
                                        )}
                                        <div className="flex items-center gap-2">
                                            <Calendar className="w-4 h-4" />
                                            <span>
                                                {new Date(incident.incident_date).toLocaleString()}
                                            </span>
                                        </div>
                                        {incident.assigned_to && incident.assigned_to_user && (
                                            <div className="flex items-center gap-2">
                                                <User className="w-4 h-4" />
                                                <span>Assigned to: {incident.assigned_to_user.name}</span>
                                            </div>
                                        )}
                                    </div>

                                    <p className="text-gray-700 text-sm line-clamp-2 mb-3">
                                        {incident.description}
                                    </p>

                                    {incident.attachments && incident.attachments.length > 0 && (
                                        <div className="flex items-center gap-2 text-sm text-gray-600 mb-3">
                                            <FileText className="w-4 h-4" />
                                            <span>{incident.attachments.length} attachment(s)</span>
                                        </div>
                                    )}
                                </div>

                                <div className="flex items-center gap-2 ml-4">
                                    <button
                                        onClick={() => {
                                            setSelectedIncident(incident);
                                            setShowViewModal(true);
                                        }}
                                        className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                        title="View"
                                    >
                                        <Eye className="w-5 h-5" />
                                    </button>
                                    <button
                                        onClick={() => handleOpenForm(incident)}
                                        className="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
                                        title="Edit"
                                    >
                                        <Edit className="w-5 h-5" />
                                    </button>
                                    {incident.status !== 'resolved' && incident.status !== 'closed' && (
                                        <button
                                            onClick={() => {
                                                if (incident.status === 'resolved') {
                                                    markClosedMutation.mutate({ id: incident.id, notes: '' });
                                                } else {
                                                    markResolvedMutation.mutate({ id: incident.id, notes: '' });
                                                }
                                            }}
                                            className="p-2 text-green-600 hover:bg-green-50 rounded-lg transition"
                                            title={incident.status === 'resolved' ? 'Mark Closed' : 'Mark Resolved'}
                                        >
                                            {incident.status === 'resolved' ? (
                                                <Lock className="w-5 h-5" />
                                            ) : (
                                                <CheckCircle className="w-5 h-5" />
                                            )}
                                        </button>
                                    )}
                                    <button
                                        onClick={() => {
                                            if (window.confirm('Are you sure you want to delete this incident?')) {
                                                deleteMutation.mutate(incident.id);
                                            }
                                        }}
                                        className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                                        title="Delete"
                                    >
                                        <Trash2 className="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </Card>
                    ))}
                </div>
            )}

            {/* View Incident Modal */}
            {showViewModal && selectedIncident && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-xl">
                        <SectionCard>
                            <div className="flex items-center justify-between mb-6">
                                <h2 className="text-xl font-semibold text-gray-900">
                                    Incident Details
                                </h2>
                                <button
                                    onClick={() => {
                                        setShowViewModal(false);
                                        setSelectedIncident(null);
                                    }}
                                    className="text-gray-500 hover:text-gray-700"
                                >
                                    <X className="w-6 h-6" />
                                </button>
                            </div>

                            <div className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Incident Number</label>
                                        <p className="text-gray-900 font-mono">{selectedIncident.incident_number}</p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <span className={`inline-block px-2 py-1 rounded text-xs font-medium border ${STATUS_COLORS[selectedIncident.status] || STATUS_COLORS.open}`}>
                                            {selectedIncident.status?.replace('_', ' ').toUpperCase()}
                                        </span>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                                        <span className={`inline-block px-2 py-1 rounded text-xs font-medium border ${SEVERITY_COLORS[selectedIncident.severity] || SEVERITY_COLORS.low}`}>
                                            {selectedIncident.severity?.toUpperCase()}
                                        </span>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                        <span className={`inline-block px-2 py-1 rounded text-xs font-medium border ${PRIORITY_COLORS[selectedIncident.priority] || PRIORITY_COLORS.medium}`}>
                                            {selectedIncident.priority?.toUpperCase()}
                                        </span>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Incident Type</label>
                                        <p className="text-gray-900">{selectedIncident.incident_type}</p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Incident Date & Time</label>
                                        <p className="text-gray-900">{new Date(selectedIncident.incident_date).toLocaleString()}</p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Resident</label>
                                        <p className="text-gray-900">
                                            {selectedIncident.resident?.first_name} {selectedIncident.resident?.last_name}
                                        </p>
                                    </div>
                                    {selectedIncident.location && (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                            <p className="text-gray-900">{selectedIncident.location}</p>
                                        </div>
                                    )}
                                    {selectedIncident.assigned_to_user && (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                                            <p className="text-gray-900">{selectedIncident.assigned_to_user.name}</p>
                                        </div>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <p className="text-gray-900 whitespace-pre-wrap">{selectedIncident.description}</p>
                                </div>

                                {selectedIncident.action_taken && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Action Taken</label>
                                        <p className="text-gray-900 whitespace-pre-wrap">{selectedIncident.action_taken}</p>
                                    </div>
                                )}

                                {selectedIncident.witnesses && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Witnesses</label>
                                        <p className="text-gray-900 whitespace-pre-wrap">{selectedIncident.witnesses}</p>
                                    </div>
                                )}

                                {selectedIncident.follow_up && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Follow-up Actions</label>
                                        <p className="text-gray-900 whitespace-pre-wrap">{selectedIncident.follow_up}</p>
                                    </div>
                                )}

                                {selectedIncident.attachments && selectedIncident.attachments.length > 0 && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Attachments</label>
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                                            {selectedIncident.attachments.map((attachment, index) => (
                                                <div key={index} className="border rounded-lg p-2">
                                                    {attachment.file_type === 'photo' ? (
                                                        <img 
                                                            src={attachment.file_url} 
                                                            alt={`Attachment ${index + 1}`}
                                                            className="w-full h-32 object-cover rounded"
                                                        />
                                                    ) : (
                                                        <div className="flex items-center justify-center h-32 bg-gray-100 rounded">
                                                            <FileText className="w-8 h-8 text-gray-400" />
                                                        </div>
                                                    )}
                                                    <p className="text-xs text-gray-600 mt-1 truncate">{attachment.file_name}</p>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                <div className="flex justify-end gap-3 pt-4 border-t">
                                    <button
                                        onClick={() => {
                                            setShowViewModal(false);
                                            handleOpenForm(selectedIncident);
                                        }}
                                        className="px-4 py-2 bg-[var(--theme-primary)] text-white rounded-lg hover:bg-[var(--theme-primary-hover)]"
                                    >
                                        Edit Incident
                                    </button>
                                    <button
                                        onClick={() => {
                                            setShowViewModal(false);
                                            setSelectedIncident(null);
                                        }}
                                        className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                                    >
                                        Close
                                    </button>
                                </div>
                            </div>
                        </SectionCard>
                    </div>
                </div>
            )}
        </div>
    );
}

// Incident Form Component (Full Page Form like Expenses)
function IncidentForm({ record, branches, residents, users, attachments, setAttachments, onClose, onSuccess, createMutation, updateMutation, methods, branchId }) {
    const handleSubmit = (data) => {
        if (record) {
            updateMutation.mutate({ id: record.id, data });
        } else {
            // For create, we need to handle file uploads
            const formDataToSend = new FormData();
            
            Object.keys(data).forEach(key => {
                if (data[key] && key !== 'attachments') {
                    formDataToSend.append(key, data[key]);
                }
            });

            // Add attachments
            attachments.forEach((file, index) => {
                if (file instanceof File) {
                    formDataToSend.append(`attachments[${index}][file]`, file);
                    formDataToSend.append(`attachments[${index}][file_type]`, file.type.startsWith('image/') ? 'photo' : 'document');
                }
            });

            createMutation.mutate(formDataToSend);
        }
    };

    return (
        <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-xl font-semibold text-gray-900">
                    {record ? 'Edit Incident' : 'Add Incident'}
                </h2>
                <button
                    onClick={onClose}
                    className="text-gray-400 hover:text-gray-600"
                >
                    <X className="w-6 h-6" />
                </button>
            </div>

            <FormProvider {...methods}>
                <form onSubmit={methods.handleSubmit(handleSubmit)} className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <FormSelect
                                    name="branch_id"
                                    label="Branch"
                                    required
                                    placeholder="Select Branch"
                                    options={branches.map(branch => ({ value: branch.id, label: branch.name }))}
                                />

                                <FormSelect
                                    name="resident_id"
                                    label="Resident"
                                    required
                                    placeholder="Select Resident"
                                    options={residents
                                        .filter(r => !branchId || r.branch_id == branchId)
                                        .map(resident => ({ 
                                            value: resident.id, 
                                            label: `${resident.first_name} ${resident.last_name}` 
                                        }))}
                                    disabled={!branchId}
                                />

                                <FormSelect
                                    name="incident_type"
                                    label="Incident Type"
                                    required
                                    placeholder="Select Type"
                                    options={INCIDENT_TYPES.map(type => ({ value: type, label: type }))}
                                />

                                <FormInput
                                    name="incident_date"
                                    label="Incident Date & Time"
                                    type="datetime-local"
                                    required
                                />

                                <FormInput
                                    name="location"
                                    label="Location"
                                    placeholder="e.g., Room 101, Main Hallway"
                                />

                                <FormSelect
                                    name="severity"
                                    label="Severity"
                                    required
                                    options={[
                                        { value: 'low', label: 'Low' },
                                        { value: 'medium', label: 'Medium' },
                                        { value: 'high', label: 'High' },
                                        { value: 'critical', label: 'Critical' },
                                    ]}
                                />

                                <FormSelect
                                    name="priority"
                                    label="Priority"
                                    required
                                    options={[
                                        { value: 'low', label: 'Low' },
                                        { value: 'medium', label: 'Medium' },
                                        { value: 'high', label: 'High' },
                                        { value: 'critical', label: 'Critical' },
                                    ]}
                                />

                                <FormSelect
                                    name="status"
                                    label="Status"
                                    required
                                    options={[
                                        { value: 'open', label: 'Open' },
                                        { value: 'in_progress', label: 'In Progress' },
                                        { value: 'resolved', label: 'Resolved' },
                                        { value: 'closed', label: 'Closed' },
                                        { value: 'on_hold', label: 'On Hold' },
                                    ]}
                                />

                                <FormSelect
                                    name="assigned_to"
                                    label="Assigned To"
                                    placeholder="Unassigned"
                                    options={[
                                        { value: '', label: 'Unassigned' },
                                        ...users
                                            .filter(u => u.is_active !== false)
                                            .map(user => ({ value: user.id, label: user.name }))
                                    ]}
                                />
                            </div>

                    <FormTextarea
                        name="description"
                        label="Description"
                        required
                        rows={4}
                        placeholder="Provide a detailed description of the incident..."
                    />

                    <FormTextarea
                        name="action_taken"
                        label="Action Taken"
                        rows={3}
                        placeholder="Describe the immediate actions taken..."
                    />

                    <FormTextarea
                        name="witnesses"
                        label="Witnesses"
                        rows={2}
                        placeholder="List any witnesses (names and roles)..."
                    />

                    <FormTextarea
                        name="follow_up"
                        label="Follow-up Actions"
                        rows={3}
                        placeholder="Describe planned or completed follow-up actions..."
                    />

                    {!record && (
                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-2">
                                Attachments
                            </label>
                            <input
                                type="file"
                                multiple
                                accept="image/*,.pdf,.doc,.docx"
                                onChange={(e) => setAttachments(Array.from(e.target.files))}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent"
                            />
                            {attachments.length > 0 && (
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {attachments.map((file, index) => (
                                        <span key={index} className="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">
                                            {file.name}
                                        </span>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    <div className="flex justify-end gap-3 pt-4 border-t">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={createMutation.isPending || updateMutation.isPending}
                            className="px-4 py-2 bg-[var(--theme-primary)] text-white rounded-lg hover:bg-[var(--theme-primary-hover)] disabled:opacity-50"
                        >
                            {createMutation.isPending || updateMutation.isPending
                                ? 'Saving...'
                                : record
                                ? 'Update Incident'
                                : 'Create Incident'}
                        </button>
                    </div>
                </form>
            </FormProvider>
        </div>
    );
}

