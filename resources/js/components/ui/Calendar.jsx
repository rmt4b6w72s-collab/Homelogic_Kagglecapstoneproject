import React, { useState, useMemo } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

/**
 * Reusable Calendar Component
 * 
 * @param {Object} props
 * @param {string} props.selectedDate - Currently selected date (YYYY-MM-DD)
 * @param {Function} props.onDateSelect - Callback when date is selected
 * @param {Array} props.calendarData - Array of day objects with metadata
 * @param {boolean} props.showIndicators - Whether to show indicators on days
 * @param {Function} props.colorMap - Function to determine day colors
 * @param {string} props.className - Additional CSS classes
 */
export default function Calendar({
    selectedDate,
    onDateSelect,
    calendarData = [],
    showIndicators = true,
    colorMap = null,
    className = '',
}) {
    const [currentDate, setCurrentDate] = useState(() => {
        if (selectedDate) {
            return new Date(selectedDate);
        }
        return new Date();
    });

    // Get month and year from currentDate
    const month = currentDate.getMonth();
    const year = currentDate.getFullYear();

    // Create a map of calendar data by date for quick lookup
    const dataMap = useMemo(() => {
        const map = new Map();
        if (Array.isArray(calendarData)) {
            calendarData.forEach(day => {
                if (day.date) {
                    map.set(day.date, day);
                }
            });
        }
        return map;
    }, [calendarData]);

    // Generate calendar days for the current month
    const calendarDays = useMemo(() => {
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - startDate.getDay()); // Start from Sunday
        
        const days = [];
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        for (let i = 0; i < 42; i++) { // 6 weeks * 7 days
            const date = new Date(startDate);
            date.setDate(startDate.getDate() + i);
            
            const dateStr = date.toISOString().split('T')[0];
            const dayData = dataMap.get(dateStr) || {};
            
            const isCurrentMonth = date.getMonth() === month;
            const isToday = date.getTime() === today.getTime();
            const isSelected = selectedDate && dateStr === selectedDate;
            
            days.push({
                date: dateStr,
                day: date.getDate(),
                isCurrentMonth,
                isToday,
                isSelected,
                ...dayData,
            });
        }
        
        return days;
    }, [year, month, dataMap, selectedDate]);

    const handlePrevMonth = () => {
        setCurrentDate(new Date(year, month - 1, 1));
    };

    const handleNextMonth = () => {
        setCurrentDate(new Date(year, month + 1, 1));
    };

    const handleToday = () => {
        const today = new Date();
        setCurrentDate(today);
        if (onDateSelect) {
            onDateSelect(today.toISOString().split('T')[0]);
        }
    };

    const handleDateClick = (dateStr) => {
        if (onDateSelect) {
            onDateSelect(dateStr);
        }
    };

    const getDayClassName = (day) => {
        let classes = 'p-3 md:p-4 text-center rounded-xl border-2 transition-all duration-200 hover:scale-105 ';
        
        // Selected date
        if (day.isSelected) {
            classes += 'bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] border-[var(--theme-primary)] shadow-2xl transform scale-105 ';
        }
        // Today (if not selected)
        else if (day.isToday) {
            classes += 'bg-[var(--theme-primary-bg)] text-[var(--theme-primary)] border-[var(--theme-primary)] shadow-lg font-bold ';
        }
        // Not current month
        else if (!day.isCurrentMonth) {
            classes += 'text-gray-400 border-gray-200 bg-gray-50 ';
        }
        // Default
        else {
            classes += 'border-gray-300 bg-white text-gray-900 ';
        }

        // Apply custom color mapping if provided
        if (colorMap && !day.isSelected) {
            const customColor = colorMap(day);
            if (customColor) {
                classes += customColor + ' ';
            }
        }
        // Apply background color from day data
        else if (day.backgroundColor && !day.isSelected) {
            classes += day.backgroundColor + ' ';
        }

        return classes.trim();
    };

    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    return (
        <div className={`bg-white rounded-lg shadow-sm border border-gray-200 p-4 md:p-6 ${className}`}>
            {/* Calendar Navigation */}
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-xl md:text-2xl font-bold text-gray-900">
                    {monthNames[month]} {year}
                </h2>
                <div className="flex items-center space-x-2 md:space-x-3">
                    <button
                        onClick={handlePrevMonth}
                        className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                        aria-label="Previous month"
                    >
                        <ChevronLeft className="w-5 h-5" />
                    </button>
                    <button
                        onClick={handleToday}
                        className="px-3 md:px-4 py-2 text-sm font-medium bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] rounded-lg hover:bg-[var(--theme-primary-hover)] transition-colors"
                    >
                        Today
                    </button>
                    <button
                        onClick={handleNextMonth}
                        className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                        aria-label="Next month"
                    >
                        <ChevronRight className="w-5 h-5" />
                    </button>
                </div>
            </div>

            {/* Calendar Grid */}
            <div className="grid grid-cols-7 gap-2">
                {/* Day Headers */}
                {dayNames.map((dayName) => (
                    <div
                        key={dayName}
                        className="p-2 md:p-3 text-center text-xs md:text-sm font-semibold text-gray-600 bg-gray-50 rounded"
                    >
                        {dayName}
                    </div>
                ))}

                {/* Calendar Days */}
                {calendarDays.map((day, index) => (
                    <button
                        key={`${day.date}-${index}`}
                        onClick={() => handleDateClick(day.date)}
                        className={getDayClassName(day)}
                        disabled={!day.isCurrentMonth}
                    >
                        <div className={`text-base md:text-lg font-bold ${day.isSelected ? 'text-white' : ''}`}>
                            {day.day}
                        </div>
                        
                        {/* Indicators */}
                        {showIndicators && day.indicators && day.indicators.length > 0 && !day.isSelected && (
                            <div className="mt-1 flex flex-wrap justify-center gap-1">
                                {day.indicators.slice(0, 3).map((indicator, idx) => (
                                    <div
                                        key={idx}
                                        className={`w-1.5 h-1.5 md:w-2 md:h-2 rounded-full ${
                                            indicator.color || 'bg-gray-400'
                                        }`}
                                        title={indicator.type || ''}
                                    />
                                ))}
                                {day.indicators.length > 3 && (
                                    <div className="text-xs font-semibold text-gray-600">
                                        +{day.indicators.length - 3}
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Count badge */}
                        {day.count !== undefined && day.count > 0 && !day.isSelected && (
                            <div className="mt-1 text-xs font-semibold text-gray-700">
                                {day.count}
                            </div>
                        )}
                    </button>
                ))}
            </div>
        </div>
    );
}

