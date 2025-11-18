import React from 'react';
import { LucideIcon } from 'lucide-react';

export default function EmptyState({
    icon: Icon,
    title,
    description,
    action,
    secondaryAction,
    className = '',
}) {
    return (
        <div className={`flex flex-col items-center justify-center py-12 px-4 text-center ${className}`}>
            {Icon && (
                <div className="mb-4 p-4 bg-gray-100 rounded-full">
                    <Icon className="w-12 h-12 text-gray-400" />
                </div>
            )}
            {title && (
                <h3 className="text-lg font-semibold text-gray-900 mb-2">
                    {title}
                </h3>
            )}
            {description && (
                <p className="text-sm text-gray-600 max-w-md mb-6">
                    {description}
                </p>
            )}
            <div className="flex flex-col sm:flex-row gap-3">
                {action && (
                    <div>
                        {action}
                    </div>
                )}
                {secondaryAction && (
                    <div>
                        {secondaryAction}
                    </div>
                )}
            </div>
        </div>
    );
}



