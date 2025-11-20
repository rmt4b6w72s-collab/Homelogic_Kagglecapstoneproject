import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { Building2, Clock, CheckCircle, Users, Plus, AlertCircle, TrendingUp } from 'lucide-react';
import api from '../services/api';
import { DashboardSkeleton } from '../components/ui/SkeletonLoader';

export default function SuperAdminDashboard() {
  const navigate = useNavigate();
  
  const { data: stats, isLoading } = useQuery({
    queryKey: ['super-admin-stats'],
    queryFn: async () => {
      const [facilitiesRes, registrationsRes, usersRes] = await Promise.all([
        api.get('/facilities'),
        api.get('/facility-registrations?status=pending'),
        api.get('/users?per_page=1'), // Just to get total count
      ]);
      
      const totalFacilities = facilitiesRes.data.total || facilitiesRes.data.data?.length || 0;
      const activeFacilities = facilitiesRes.data.data?.filter(f => f.is_active)?.length || 0;
      const pendingRegistrations = registrationsRes.data.data?.length || 0;
      const totalUsers = usersRes.data.total || usersRes.data.data?.length || 0;
      
      return {
        totalFacilities,
        activeFacilities,
        pendingRegistrations,
        totalUsers,
      };
    },
  });

  if (isLoading) {
    return <DashboardSkeleton />;
  }

  const statCards = [
    {
      title: 'Total Facilities',
      value: stats?.totalFacilities || 0,
      description: 'All registered facilities',
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
      color: 'bg-amber-600',
      hoverColor: 'hover:bg-amber-700',
      link: '/super-admin/facility-registrations',
      highlight: stats?.pendingRegistrations > 0,
    },
    {
      title: 'Active Facilities',
      value: stats?.activeFacilities || 0,
      description: 'Currently active',
      icon: CheckCircle,
      color: 'bg-green-600',
      hoverColor: 'hover:bg-green-700',
    },
    {
      title: 'Total System Users',
      value: stats?.totalUsers || 0,
      description: 'All facility users',
      icon: Users,
      color: 'bg-blue-600',
      hoverColor: 'hover:bg-blue-700',
    },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Super Admin Dashboard</h1>
            <p className="text-gray-600 mt-1">Manage facilities and system-wide operations</p>
          </div>
          <div className="flex gap-3">
            <button
              onClick={() => navigate('/super-admin/facility-registrations')}
              className="px-4 py-2 bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] rounded-lg hover:bg-[var(--theme-primary-hover)] transition-colors flex items-center gap-2"
            >
              <AlertCircle className="w-4 h-4" />
              View Registrations
            </button>
            <button
              onClick={() => navigate('/super-admin/facilities/create')}
              className="px-4 py-2 bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] rounded-lg hover:bg-[var(--theme-primary-hover)] transition-colors flex items-center gap-2"
            >
              <Plus className="w-4 h-4" />
              Add Facility
            </button>
          </div>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {statCards.map((stat, index) => {
          const Icon = stat.icon;
          return (
            <div
              key={index}
              onClick={() => stat.link && navigate(stat.link)}
              className={`bg-white rounded-lg shadow-md hover:shadow-lg transition-all ${
                stat.link ? 'cursor-pointer' : ''
              } ${stat.highlight ? 'ring-2 ring-amber-400' : ''}`}
            >
              <div className="p-6">
                <div className="flex items-center justify-between mb-4">
                  <div className={`p-3 rounded-lg ${stat.color} text-white`}>
                    <Icon className="w-6 h-6" />
                  </div>
                  {stat.highlight && (
                    <span className="px-2 py-1 text-xs font-semibold bg-amber-100 text-amber-800 rounded-full">
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

      {/* Quick Actions */}
      <div className="bg-white rounded-lg shadow p-6">
        <h2 className="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <button
            onClick={() => navigate('/super-admin/facility-registrations')}
            className="p-4 border-2 border-gray-200 rounded-lg hover:border-[var(--theme-primary)] hover:bg-[var(--theme-primary-bg-light)] transition-colors text-left group"
          >
            <Clock className="w-8 h-8 text-[var(--theme-primary)] mb-2 group-hover:scale-110 transition-transform" />
            <h3 className="font-semibold text-gray-900 mb-1">Review Registrations</h3>
            <p className="text-sm text-gray-600">Approve or reject facility registration requests</p>
          </button>
          
          <button
            onClick={() => navigate('/super-admin/facilities/create')}
            className="p-4 border-2 border-gray-200 rounded-lg hover:border-[var(--theme-primary)] hover:bg-[var(--theme-primary-bg-light)] transition-colors text-left group"
          >
            <Plus className="w-8 h-8 text-[var(--theme-primary)] mb-2 group-hover:scale-110 transition-transform" />
            <h3 className="font-semibold text-gray-900 mb-1">Create Facility</h3>
            <p className="text-sm text-gray-600">Add a new facility with custom branding</p>
          </button>
          
          <button
            onClick={() => navigate('/super-admin/facilities')}
            className="p-4 border-2 border-gray-200 rounded-lg hover:border-[var(--theme-primary)] hover:bg-[var(--theme-primary-bg-light)] transition-colors text-left group"
          >
            <Building2 className="w-8 h-8 text-[var(--theme-primary)] mb-2 group-hover:scale-110 transition-transform" />
            <h3 className="font-semibold text-gray-900 mb-1">Manage Facilities</h3>
            <p className="text-sm text-gray-600">View and edit all facilities</p>
          </button>
        </div>
      </div>
    </div>
  );
}

