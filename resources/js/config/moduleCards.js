import {
  Users, Pill, Heart, Calendar, ClipboardList, Moon, Building2,
  BarChart3, Activity, AlertCircle, FileText, ShoppingCart, DollarSign,
  Flame, Briefcase, Package
} from 'lucide-react';

export const MODULE_CARDS = {
  residents: {
    id: 'residents',
    name: 'Residents',
    description: 'Comprehensive resident management with profiles, medical history, and care plans',
    icon: Users,
    color: 'from-blue-500 to-cyan-500',
    route: '/modules/residents/dashboard',
  },
  medications: {
    id: 'medications',
    name: 'Medications',
    description: 'Medication administration tracking with schedules and compliance monitoring',
    icon: Pill,
    color: 'from-purple-500 to-pink-500',
    route: '/modules/medications/dashboard',
  },
  vitals: {
    id: 'vitals',
    name: 'Vitals',
    description: 'Track vital signs with customizable ranges, alerts, and reporting',
    icon: Heart,
    color: 'from-red-500 to-rose-500',
    route: '/modules/vitals/dashboard',
  },
  appointments: {
    id: 'appointments',
    name: 'Appointments',
    description: 'Schedule and manage healthcare provider appointments with reminders',
    icon: Calendar,
    color: 'from-green-500 to-emerald-500',
    route: '/modules/appointments/dashboard',
  },
  assessments: {
    id: 'assessments',
    name: 'Assessments',
    description: 'Conduct comprehensive resident assessments with customizable forms',
    icon: ClipboardList,
    color: 'from-indigo-500 to-blue-500',
    route: '/modules/assessments/dashboard',
  },
  sleep: {
    id: 'sleep',
    name: 'Sleep Records',
    description: 'Track sleep patterns and quality for comprehensive care monitoring',
    icon: Moon,
    color: 'from-slate-500 to-gray-500',
    route: '/modules/sleep/dashboard',
  },
  housekeeping: {
    id: 'housekeeping',
    name: 'Housekeeping',
    description: 'Manage cleaning schedules, tasks, and assignments with quality tracking',
    icon: Building2,
    color: 'from-teal-500 to-cyan-500',
    route: '/modules/housekeeping/dashboard',
  },
  incidents: {
    id: 'incidents',
    name: 'Incidents',
    description: 'Document and track incidents with detailed reporting and workflows',
    icon: AlertCircle,
    color: 'from-orange-500 to-red-500',
    route: '/modules/incidents/dashboard',
  },
  fire_drills: {
    id: 'fire_drills',
    name: 'Fire Drills',
    description: 'Schedule and track fire drills with compliance monitoring',
    icon: Flame,
    color: 'from-red-600 to-orange-500',
    route: '/modules/fire_drills/dashboard',
  },
  grocery_status: {
    id: 'grocery_status',
    name: 'Grocery Status',
    description: 'Track grocery status updates and weekly completion',
    icon: ShoppingCart,
    color: 'from-amber-500 to-yellow-500',
    route: '/modules/grocery_status/dashboard',
  },
  leave_requests: {
    id: 'leave_requests',
    name: 'Leave Requests',
    description: 'Manage staff leave requests with approval workflows',
    icon: Briefcase,
    color: 'from-violet-500 to-purple-500',
    route: '/modules/leave_requests/dashboard',
  },
  employee_documents: {
    id: 'employee_documents',
    name: 'Employee Documents',
    description: 'Manage employee documents with expiration tracking',
    icon: FileText,
    color: 'from-gray-500 to-slate-500',
    route: '/modules/employee_documents/dashboard',
  },
  pharmacy: {
    id: 'pharmacy',
    name: 'Pharmacy',
    description: 'Manage medication inventory, orders, suppliers, and deliveries',
    icon: Package,
    color: 'from-blue-600 to-indigo-500',
    route: '/modules/pharmacy/dashboard',
  },
  billing_expenses: {
    id: 'billing_expenses',
    name: 'Billing & Expenses',
    description: 'Track expenses, generate invoices, and manage financial records',
    icon: DollarSign,
    color: 'from-green-600 to-emerald-500',
    route: '/modules/billing_expenses/dashboard',
  },
  behaviors: {
    id: 'behaviors',
    name: 'Behaviors',
    description: 'Track resident behaviors and intervention effectiveness',
    icon: Activity,
    color: 'from-pink-500 to-rose-500',
    route: '/modules/behaviors/dashboard',
  },
};

export function getModuleCard(moduleId) {
  return MODULE_CARDS[moduleId] || null;
}

export function getAllModuleCards() {
  return Object.values(MODULE_CARDS);
}

export function getModuleCardsForUser(enabledModules = []) {
  if (!enabledModules || enabledModules.length === 0) {
    return getAllModuleCards();
  }
  return getAllModuleCards().filter(card => enabledModules.includes(card.id));
}

