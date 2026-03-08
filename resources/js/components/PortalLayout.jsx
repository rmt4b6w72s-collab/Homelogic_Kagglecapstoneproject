import React, { useEffect } from 'react';
import { Outlet, Link, useLocation, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { LayoutDashboard, FileText, MessageSquare, LogOut } from 'lucide-react';
import api from '../services/api';

const nav = [
  { name: 'Dashboard', path: '/portal', icon: LayoutDashboard },
  { name: 'Care Updates', path: '/portal/care-updates', icon: FileText },
  { name: 'Messages', path: '/portal/messages', icon: MessageSquare },
];

export default function PortalLayout() {
  const location = useLocation();
  const navigate = useNavigate();
  const { data: user } = useQuery({
    queryKey: ['current-user'],
    queryFn: async () => (await api.get('/user')).data,
  });
  useEffect(() => {
    if (user && user.role !== 'family') {
      navigate('/dashboard', { replace: true });
    }
  }, [user, navigate]);

  const handleLogout = async () => {
    try {
      await api.post('/logout');
    } catch (_) {}
    localStorage.removeItem('auth_token');
    localStorage.removeItem('token');
    localStorage.removeItem('access_token');
    localStorage.removeItem('user_name');
    localStorage.removeItem('user_role');
    window.location.href = '/login';
  };

  return (
    <div className="min-h-screen bg-gray-50 flex">
      <aside className="w-56 bg-white border-r border-gray-200 flex flex-col">
        <div className="p-4 border-b border-gray-200">
          <Link to="/portal" className="text-lg font-semibold text-gray-900">
            Family Portal
          </Link>
        </div>
        <nav className="p-2 flex-1">
          {nav.map((item) => {
            const isActive = location.pathname === item.path || (item.path === '/portal' && location.pathname === '/portal');
            return (
              <Link
                key={item.path}
                to={item.path}
                className={`flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                  isActive ? 'bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)]' : 'text-gray-700 hover:bg-gray-100'
                }`}
              >
                <item.icon className="w-4 h-4" />
                {item.name}
              </Link>
            );
          })}
        </nav>
        <div className="p-2 border-t border-gray-200">
          <button
            type="button"
            onClick={handleLogout}
            className="flex items-center gap-2 w-full px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100"
          >
            <LogOut className="w-4 h-4" />
            Sign out
          </button>
        </div>
      </aside>
      <main className="flex-1 p-6 overflow-auto">
        <Outlet />
      </main>
    </div>
  );
}
