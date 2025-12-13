import React from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { Home, ChevronRight } from 'lucide-react';

export default function ModuleLayout({ 
  moduleName, 
  moduleId, 
  sidebar, 
  children,
  headerActions 
}) {
  const navigate = useNavigate();
  const location = useLocation();

  const handleBackToHome = () => {
    navigate('/modules');
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="flex">
        {/* Sidebar */}
        {sidebar && (
          <aside className="w-64 bg-white border-r border-gray-200 min-h-screen fixed left-0 top-0 pt-16">
            <div className="p-4">
              {sidebar}
            </div>
          </aside>
        )}

        {/* Main Content */}
        <div className={`flex-1 ${sidebar ? 'ml-64' : ''}`}>
          {/* Header */}
          <header className="bg-white border-b border-gray-200 sticky top-0 z-10">
            <div className="px-6 py-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-4">
                  <button
                    onClick={handleBackToHome}
                    className="flex items-center space-x-2 text-gray-600 hover:text-gray-900 transition-colors"
                  >
                    <Home className="w-5 h-5" />
                    <span className="font-medium">Back to Modules</span>
                  </button>
                  <ChevronRight className="w-4 h-4 text-gray-400" />
                  <h1 className="text-2xl font-bold text-gray-900">{moduleName}</h1>
                </div>
                {headerActions && (
                  <div className="flex items-center space-x-2">
                    {headerActions}
                  </div>
                )}
              </div>
            </div>
          </header>

          {/* Page Content */}
          <main className="p-6">
            {children}
          </main>
        </div>
      </div>
    </div>
  );
}

