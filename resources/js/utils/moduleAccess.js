// Module mapping for navigation items
// Maps navigation paths to their corresponding modules
export const MODULE_MAP = {
  // Pharmacy module
  '/pharmacy': 'pharmacy',
  '/pharmacy/suppliers': 'pharmacy',
  '/pharmacy/inventory': 'pharmacy',
  '/pharmacy/orders': 'pharmacy',
  
  // Medications module
  '/medications': 'medications',
  '/medication-deliveries': 'medications',
  '/medication-history': 'medications',
  '/medications/residents': 'medications',
  
  // Vitals module
  '/vitals': 'vitals',
  '/view-vitals': 'vitals',
  
  // Appointments module
  '/appointments': 'appointments',
  
  // Assessments module
  '/assessments': 'assessments',
  
  // Sleep module
  '/sleep': 'sleep',
  '/sleep-patterns': 'sleep',
  
  // Housekeeping module
  '/housekeeping': 'housekeeping',
  '/housekeeping/dashboard': 'housekeeping',
  '/housekeeping/schedule': 'housekeeping',
  
  // Reports module
  '/reports': 'reports',
  '/reports/charts': 'reports',
  '/reports/resident-charts': 'reports',
  '/reports/vitals-charts': 'reports',
  '/reports/vitals-reports': 'reports',
  '/reports/assessment-charts': 'reports',
  '/reports/appointments-charts': 'reports',
  '/reports/vitals-history': 'reports',
  '/reports/sleep-charts': 'reports',
  '/reports/staff-charts': 'reports',
  
  // Residents module
  '/administration/residents': 'residents',
  '/my-residents': 'residents',
  
  // Grocery Status module
  '/grocery-status': 'grocery_status',
  
  // Fire Drills module
  '/fire-drills': 'fire_drills',
  
  // Leave Requests module
  '/leave-requests': 'leave_requests',
  '/administration/leave-requests': 'leave_requests',
};

/**
 * Check if a navigation item should be visible based on module access
 */
export function hasModuleAccess(path, enabledModules, isSuperAdmin) {
  // Super admins have access to everything
  if (isSuperAdmin) {
    return true;
  }

  // If no enabled modules, deny access (unless super admin)
  if (!enabledModules || enabledModules.length === 0) {
    return false;
  }

  // Check if path requires a specific module
  const requiredModule = MODULE_MAP[path];
  
  // If path doesn't require a module, allow access
  if (!requiredModule) {
    return true;
  }

  // Check if the required module is enabled
  return enabledModules.includes(requiredModule);
}

/**
 * Filter navigation items based on module access
 */
export function filterNavigationByModuleAccess(navigationItems, enabledModules, isSuperAdmin) {
  return navigationItems
    .map(item => {
      // Check if main item has module access
      const hasAccess = hasModuleAccess(item.path, enabledModules, isSuperAdmin);
      
      // Filter children if they exist
      let filteredChildren = null;
      if (item.children && Array.isArray(item.children)) {
        filteredChildren = item.children.filter(child => 
          hasModuleAccess(child.path, enabledModules, isSuperAdmin)
        );
      }

      // If item has no access and no accessible children, exclude it
      if (!hasAccess && (!filteredChildren || filteredChildren.length === 0)) {
        return null;
      }

      // Return item with filtered children
      return {
        ...item,
        children: filteredChildren,
      };
    })
    .filter(item => item !== null);
}

