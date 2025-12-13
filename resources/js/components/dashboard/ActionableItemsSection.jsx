import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
    ClipboardList, Calendar, Pill, Flame, AlertTriangle, 
    Clock, ArrowRight, CheckCircle, X, AlertCircle, Info
} from 'lucide-react';

/**
 * ActionableItemsSection - Main section displaying items requiring action
 * Matches design: cards with colored left border, icon, title, description, View link, and dismiss button
 */
export default function ActionableItemsSection({ items = [], onItemClick, onDismiss }) {
    const navigate = useNavigate();
    const [dismissedItems, setDismissedItems] = useState(new Set());

    if (!items || items.length === 0) {
        return null;
    }

    // Filter out dismissed items
    const visibleItems = items.filter(item => !dismissedItems.has(item.id));

    if (visibleItems.length === 0) {
        return null;
    }

    const getItemIcon = (type, priority) => {
        const icons = {
            assessment: ClipboardList,
            appointment: Calendar,
            medication: Clock, // Clock icon for medication due
            fire_drill: Flame,
            incident: AlertTriangle,
            leave_request: Info,
            inventory: AlertTriangle,
        };
        return icons[type] || AlertCircle;
    };

    const getItemStyles = (priority, type) => {
        if (type === 'medication') {
            return {
                border: 'border-l-4 border-pink-500',
                bg: 'bg-white',
                iconBg: 'bg-pink-100',
                iconColor: 'text-pink-600',
            };
        }
        if (type === 'inventory') {
            return {
                border: 'border-l-4 border-orange-500',
                bg: 'bg-white',
                iconBg: 'bg-orange-100',
                iconColor: 'text-orange-600',
            };
        }
        if (priority === 'urgent') {
            return {
                border: 'border-l-4 border-red-500',
                bg: 'bg-white',
                iconBg: 'bg-red-100',
                iconColor: 'text-red-600',
            };
        }
        if (priority === 'soon') {
            return {
                border: 'border-l-4 border-yellow-500',
                bg: 'bg-white',
                iconBg: 'bg-yellow-100',
                iconColor: 'text-yellow-600',
            };
        }
        // Default blue for info/pending approvals
        return {
            border: 'border-l-4 border-blue-500',
            bg: 'bg-white',
            iconBg: 'bg-blue-100',
            iconColor: 'text-blue-600',
        };
    };

    const handleDismiss = (e, item) => {
        e.stopPropagation();
        setDismissedItems(prev => new Set([...prev, item.id]));
        if (onDismiss) {
            onDismiss(item);
        }
    };

    const handleView = (e, item) => {
        e.stopPropagation();
        if (onItemClick) {
            onItemClick(item);
        } else if (item.link) {
            navigate(item.link);
        }
    };

    return (
        <div className="space-y-3">
            {visibleItems.map((item, index) => {
                const Icon = getItemIcon(item.type, item.priority);
                const styles = getItemStyles(item.priority, item.type);
                const handleCardClick = () => {
                    if (onItemClick) {
                        onItemClick(item);
                    } else if (item.link) {
                        navigate(item.link);
                    }
                };

                return (
                    <div
                        key={item.id || index}
                        className={`${styles.bg} ${styles.border} rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer ${styles.border.replace('border-l-4', '')}`}
                        onClick={handleCardClick}
                    >
                        <div className="p-4">
                            <div className="flex items-start justify-between gap-4">
                                {/* Icon */}
                                <div className={`${styles.iconBg} ${styles.iconColor} rounded-full p-3 flex-shrink-0`}>
                                    <Icon className="w-5 h-5" />
                                </div>
                                
                                {/* Content */}
                                <div className="flex-1 min-w-0">
                                    <h3 className="text-sm font-semibold text-gray-900 mb-1">
                                        {item.title}
                                    </h3>
                                    {item.description && (
                                        <p className="text-xs text-gray-600">
                                            {item.description}
                                        </p>
                                    )}
                                </div>
                                
                                {/* Actions */}
                                <div className="flex items-center gap-2 flex-shrink-0">
                                    {item.link && (
                                        <button
                                            onClick={(e) => handleView(e, item)}
                                            className="text-sm font-medium text-blue-600 hover:text-blue-700"
                                        >
                                            View
                                        </button>
                                    )}
                                    <button
                                        onClick={(e) => handleDismiss(e, item)}
                                        className="text-gray-400 hover:text-gray-600 transition-colors p-1"
                                        aria-label="Dismiss"
                                    >
                                        <X className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

