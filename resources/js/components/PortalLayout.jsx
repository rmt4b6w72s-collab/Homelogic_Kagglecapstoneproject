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
  const isFamilyPortalRole = user && ['family', 'family_member'].includes(user.role);
  const { data: careSummary } = useQuery({
    queryKey: ['family-care-updates'],
    queryFn: async () => (await api.get('/family/care-updates')).data,
    staleTime: 60 * 1000,
    enabled: !!isFamilyPortalRole,
  });
  useEffect(() => {
    if (user && !isFamilyPortalRole) {
      navigate('/dashboard', { replace: true });
    }
  }, [user, navigate, isFamilyPortalRole]);

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
    <div className="min-h-screen bg-gray-50 flex flex-col">
      <header className="min-h-14 bg-white border-b border-gray-200 flex items-center justify-between gap-4 px-4 py-2 shrink-0">
        <div className="min-w-0">
          <Link to="/portal" className="text-lg font-semibold text-gray-900 block truncate">
            Family Portal
          </Link>
          {(careSummary?.linked_resident_ids?.length ?? careSummary?.residents?.length) ? (
            <p className="text-xs text-gray-500 truncate">
              {careSummary?.residents?.length === 1
                ? careSummary.residents[0].name
                : careSummary?.residents?.length > 1
                  ? `${careSummary.residents.length} residents`
                  : careSummary?.linked_resident_ids?.length
                    ? `${careSummary.linked_resident_ids.length} linked resident(s)`
                    : null}
            </p>
          ) : null}
        </div>
        <button
          type="button"
          onClick={handleLogout}
          className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors"
        >
          <LogOut className="w-4 h-4" />
          Sign out
        </button>
      </header>
      <div className="flex flex-1 overflow-hidden">
      <aside className="w-56 bg-white border-r border-gray-200 flex flex-col shrink-0">
        <div className="p-4 border-b border-gray-200">
          <span className="text-sm text-gray-500">Menu</span>
        </div>
        <nav className="p-2 flex-1 overflow-y-auto">
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
          <p className="px-3 py-1 text-xs text-gray-500">
            Next time, sign in at the login page with your email and password.
          </p>
        </div>
      </aside>
      <main className="flex-1 p-6 overflow-auto">
        <Outlet />
      </main>
      </div>
    </div>
  );
}
