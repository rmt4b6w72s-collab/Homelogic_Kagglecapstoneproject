import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { 
  Building2, 
  Clock, 
  CheckCircle, 
  Users, 
  Plus, 
  AlertCircle, 
  TrendingUp,
  MapPin,
  Calendar,
  Shield,
  Settings,
  Eye
} from 'lucide-react';
import api from '../services/api';
import { DashboardSkeleton } from '../components/ui/SkeletonLoader';

export default function SuperAdminDashboard() {
  const navigate = useNavigate();
  
  const { data: stats, isLoading } = useQuery({
    queryKey: ['super-admin-stats'],
    queryFn: async () => {
      const [facilitiesRes, registrationsRes, usersRes, branchesRes, residentsRes, appointmentsRes] = await Promise.all([
        api.get('/facilities'),
        api.get('/facility-registrations?status=pending'),
        api.get('/users?per_page=1'),
        api.get('/branches?per_page=1'),
        api.get('/residents?per_page=1'),
        api.get('/appointments?per_page=1'),
      ]);
      
      const facilities = facilitiesRes.data.data || facilitiesRes.data || [];
      const totalFacilities = facilitiesRes.data.total || facilities.length;
      const activeFacilities = facilities.filter(f => f.is_active).length;
      const pendingRegistrations = registrationsRes.data.data?.length || 0;
      const totalUsers = usersRes.data.total || usersRes.data.data?.length || 0;
      const totalBranches = branchesRes.data.total || branchesRes.data.data?.length || 0;
      const totalResidents = residentsRes.data.total || residentsRes.data.data?.length || 0;
      const totalAppointments = appointmentsRes.data.total || appointmentsRes.data.data?.length || 0;
      
      return {
        totalFacilities,
        activeFacilities,
        pendingRegistrations,
        totalUsers,
        totalBranches,
        totalResidents,
        totalAppointments,
        facilities: Array.isArray(facilities) ? facilities : [],
      };
    },
  });

  const { data: recentRegistrations, isLoading: registrationsLoading } = useQuery({
    queryKey: ['recent-registrations'],
    queryFn: async () => {
      const res = await api.get('/facility-registrations?per_page=10');
      return res.data.data || [];
    },
  });

  if (isLoading) {
    return <DashboardSkeleton />;
  }

  const primaryStats = [
    {
      title: 'Total Facilities',
      value: stats?.totalFacilities || 0,
      description: `${stats?.activeFacilities || 0} active`,
      icon: Building2,
      color: 'bg-[var(--theme-primary)]',
      hoverColor: 'hover:bg-[var(--theme-primary-hover)]',
      link: '/super-admin/facilities',
    },
    {
      title: 'Pending Registrations',
      value: stats?.pendingRegistrations || 0,
      description: 'Awaiting approval',
      icon: Clock,
      color: 'bg-[var(--theme-primary)]',
      hoverColor: 'hover:bg-[var(--theme-primary-hover)]',
      link: '/super-admin/facility-registrations',
      highlight: stats?.pendingRegistrations > 0,
    },
    {
      title: 'Total Branches',
      value: stats?.totalBranches || 0,
      description: 'Care locations',
      icon: MapPin,
      color: 'bg-[var(--theme-primary)]',
      hoverColor: 'hover:bg-[var(--theme-primary-hover)]',
    },
    {
      title: 'System Users',
      value: stats?.totalUsers || 0,
      description: 'All facility users',
      icon: Users,
      color: 'bg-[var(--theme-primary)]',
      hoverColor: 'hover:bg-[var(--theme-primary-hover)]',
      link: '/administration/users',
    },
  ];

  const systemOverviewStats = [
    {
      title: 'Total Residents',
      value: stats?.totalResidents || 0,
      description: 'Across all facilities',
      icon: Users,
      color: 'bg-[var(--theme-primary)]',
    },
    {
      title: 'Appointments',
      value: stats?.totalAppointments || 0,
      description: 'Scheduled',
      icon: Calendar,
      color: 'bg-[var(--theme-primary)]',
    },
  ];

  const quickActions = [
    {
      title: 'Manage Facilities',
      description: 'View and edit all facilities',
      icon: Building2,
      color: 'text-[var(--theme-primary)]',
      bgColor: 'bg-[var(--theme-primary-bg-light)]',
      hoverColor: 'hover:bg-[var(--theme-primary-bg-light)]',
      onClick: () => navigate('/super-admin/facilities'),
    },
    {
      title: 'Review Registrations',
      description: 'Approve or reject facility registration requests',
      icon: Clock,
      color: 'text-[var(--theme-primary)]',
      bgColor: 'bg-[var(--theme-primary-bg-light)]',
      hoverColor: 'hover:bg-[var(--theme-primary-bg-light)]',
      onClick: () => navigate('/super-admin/facility-registrations'),
    },
    {
      title: 'Manage Users',
      description: 'System users across all facilities',
      icon: Users,
      color: 'text-[var(--theme-primary)]',
      bgColor: 'bg-[var(--theme-primary-bg-light)]',
      hoverColor: 'hover:bg-[var(--theme-primary-bg-light)]',
      onClick: () => navigate('/administration/users'),
    },
    {
      title: 'Roles & Permissions',
      description: 'Access control and permissions',
      icon: Shield,
      color: 'text-[var(--theme-primary)]',
      bgColor: 'bg-[var(--theme-primary-bg-light)]',
      hoverColor: 'hover:bg-[var(--theme-primary-bg-light)]',
      onClick: () => navigate('/super-admin/permissions'),
    },
  ];

  return (
    <div className="space-y-6">
      {/* Hero Header */}
      <div className="bg-[var(--theme-primary)] rounded-xl shadow-lg p-6 text-[var(--theme-text-on-primary)]">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 className="text-3xl font-bold mb-1">Super Admin Dashboard</h1>
            <p className="opacity-90">System Administrator - Managing all facilities and system operations</p>
          </div>
        </div>
      </div>

      {/* Primary Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {primaryStats.map((stat, index) => {
          const Icon = stat.icon;
          return (
            <div
              key={index}
              onClick={() => stat.link && navigate(stat.link)}
              className={`bg-white rounded-lg shadow-md hover:shadow-lg transition-all ${
                stat.link ? 'cursor-pointer' : ''
              } ${stat.highlight ? 'ring-2 ring-[var(--theme-primary)]' : ''}`}
            >
              <div className="p-6">
                <div className="flex items-center justify-between mb-4">
                  <div className={`p-3 rounded-lg ${stat.color} text-white`}>
                    <Icon className="w-6 h-6" />
                  </div>
                  {stat.highlight && (
                    <span className="px-2 py-1 text-xs font-semibold bg-[var(--theme-secondary)] text-[var(--theme-primary)] rounded-full">
                      Action Needed
                    </span>
                  )}
                </div>
                <div>
                  <p className="text-3xl font-bold text-gray-900 mb-1">{stat.value}</p>
                  <p className="text-sm font-medium text-gray-900 mb-1">{stat.title}</p>
                  <p className="text-xs text-gray-600">{stat.description}</p>
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* System Overview Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {systemOverviewStats.map((stat, index) => {
          const Icon = stat.icon;
          return (
            <div
              key={index}
              className="bg-white rounded-lg shadow-md p-6"
            >
              <div className="flex items-center justify-between mb-4">
                <div className={`p-3 rounded-lg ${stat.color} text-white`}>
                  <Icon className="w-6 h-6" />
                </div>
              </div>
              <div>
                <p className="text-3xl font-bold text-gray-900 mb-1">{stat.value}</p>
                <p className="text-sm font-medium text-gray-900 mb-1">{stat.title}</p>
                <p className="text-xs text-gray-600">{stat.description}</p>
              </div>
            </div>
          );
        })}
      </div>

      {/* Quick Actions */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="flex items-center justify-between mb-4">
          <div>
            <h2 className="text-xl font-semibold text-gray-900">Quick Actions</h2>
            <p className="text-sm text-gray-600">Fast access to common tasks</p>
          </div>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {quickActions.map((action, index) => {
            const Icon = action.icon;
            return (
              <button
                key={index}
                onClick={action.onClick}
                className={`p-4 border-2 border-gray-200 rounded-lg ${action.hoverColor} transition-colors text-left group`}
              >
                <div className={`w-10 h-10 ${action.bgColor} rounded-lg flex items-center justify-center mb-3 group-hover:scale-110 transition-transform`}>
                  <Icon className={`w-5 h-5 ${action.color}`} />
                </div>
                <h3 className="font-semibold text-gray-900 mb-1">{action.title}</h3>
                <p className="text-sm text-gray-600">{action.description}</p>
              </button>
            );
          })}
        </div>
      </div>

      {/* Facilities Overview */}
      {stats?.facilities && stats.facilities.length > 0 && (
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h2 className="text-xl font-semibold text-gray-900">Facilities Overview</h2>
              <p className="text-sm text-gray-600">Recent facilities and their status</p>
            </div>
            <button
              onClick={() => navigate('/super-admin/facilities')}
              className="text-sm text-[var(--theme-primary)] hover:underline"
            >
              View All
            </button>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-gray-200">
                  <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">Facility Name</th>
                  <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">Status</th>
                  <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">Registered</th>
                  <th className="text-right py-3 px-4 text-sm font-semibold text-gray-700">Actions</th>
                </tr>
              </thead>
              <tbody>
                {stats.facilities.slice(0, 5).map((facility) => (
                  <tr key={facility.id} className="border-b border-gray-100 hover:bg-gray-50">
                    <td className="py-3 px-4">
                      <div className="flex items-center gap-2">
                        <Building2 className="w-4 h-4 text-[var(--theme-primary)]" />
                        <span className="font-medium text-gray-900">{facility.name}</span>
                      </div>
                    </td>
                    <td className="py-3 px-4">
                      <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                        facility.is_active 
                          ? 'bg-[var(--theme-secondary)] text-[var(--theme-primary)]' 
                          : 'bg-gray-100 text-gray-800'
                      }`}>
                        {facility.is_active ? 'Active' : 'Inactive'}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-sm text-gray-600">
                      {facility.created_at ? new Date(facility.created_at).toLocaleDateString() : 'N/A'}
                    </td>
                    <td className="py-3 px-4 text-right">
                      <button
                        onClick={() => navigate(`/super-admin/facilities?edit=${facility.id}`)}
                        className="text-[var(--theme-primary)] hover:underline flex items-center gap-1 ml-auto"
                      >
                        <Eye className="w-4 h-4" />
                        View
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Recent Registrations */}
      {recentRegistrations && recentRegistrations.length > 0 && (
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h2 className="text-xl font-semibold text-gray-900">Recent Facility Registrations</h2>
              <p className="text-sm text-gray-600">Latest registration requests</p>
            </div>
            <button
              onClick={() => navigate('/super-admin/facility-registrations')}
              className="text-sm text-[var(--theme-primary)] hover:underline"
            >
              View All
            </button>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-gray-200">
                  <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">Facility Name</th>
                  <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">Contact</th>
                  <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">Status</th>
                  <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">Submitted</th>
                  <th className="text-right py-3 px-4 text-sm font-semibold text-gray-700">Actions</th>
                </tr>
              </thead>
              <tbody>
                {recentRegistrations.slice(0, 5).map((registration) => (
                  <tr key={registration.id} className="border-b border-gray-100 hover:bg-gray-50">
                    <td className="py-3 px-4">
                      <div className="flex items-center gap-2">
                        <Building2 className="w-4 h-4 text-[var(--theme-primary)]" />
                        <span className="font-medium text-gray-900">{registration.facility_name}</span>
                      </div>
                    </td>
                    <td className="py-3 px-4 text-sm text-gray-600">
                      {registration.contact_name || registration.email || 'N/A'}
                    </td>
                    <td className="py-3 px-4">
                      <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                        registration.status === 'pending' 
                          ? 'bg-yellow-100 text-yellow-800' 
                          : registration.status === 'approved'
                          ? 'bg-[var(--theme-secondary)] text-[var(--theme-primary)]'
                          : 'bg-gray-100 text-gray-800'
                      }`}>
                        {registration.status || 'pending'}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-sm text-gray-600">
                      {registration.created_at ? new Date(registration.created_at).toLocaleDateString() : 'N/A'}
                    </td>
                    <td className="py-3 px-4 text-right">
                      <button
                        onClick={() => navigate(`/super-admin/facility-registrations?review=${registration.id}`)}
                        className="text-[var(--theme-primary)] hover:underline flex items-center gap-1 ml-auto"
                      >
                        <Eye className="w-4 h-4" />
                        Review
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}
