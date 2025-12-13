import React from 'react';
import { NavLink } from 'react-router-dom';
import { useLocation } from 'react-router-dom';

export default function ModuleSidebar({ moduleId, items = [] }) {
  const location = useLocation();

  return (
    <nav className="space-y-1">
      {items.map((item) => {
        const isActive = location.pathname === item.path || 
                        (item.path !== `/${moduleId}/dashboard` && location.pathname.startsWith(item.path));
        
        return (
          <NavLink
            key={item.path}
            to={item.path}
            className={`
              flex items-center space-x-3 px-4 py-3 rounded-lg transition-colors
              ${isActive 
                ? 'bg-blue-50 text-blue-700 font-medium' 
                : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
              }
            `}
          >
            {item.icon && <item.icon className="w-5 h-5" />}
            <span>{item.label}</span>
          </NavLink>
        );
      })}
    </nav>
  );
}

