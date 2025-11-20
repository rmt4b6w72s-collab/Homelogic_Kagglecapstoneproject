import React, { useState, useMemo } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

/**
 * Mini Calendar Component - Compact version for dashboard/widget use
 * 
 * @param {Object} props
 * @param {string} props.selectedDate - Currently selected date (YYYY-MM-DD)
 * @param {Function} props.onDateSelect - Callback when date is selected
 * @param {Array} props.calendarData - Array of day objects with metadata
 * @param {string} props.className - Additional CSS classes
 */
export default function MiniCalendar({
    selectedDate,
    onDateSelect,
    calendarData = [],
    className = '',
}) {
    const [currentDate, setCurrentDate] = useState(() => {
        if (selectedDate) {
            return new Date(selectedDate);
        }
        return new Date();
    });

    const month = currentDate.getMonth();
    const year = currentDate.getFullYear();

    // Create a map of calendar data by date
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

    // Generate calendar days
    const calendarDays = useMemo(() => {
        const firstDay = new Date(year, month, 1);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - startDate.getDay());
        
        const days = [];
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        for (let i = 0; i < 35; i++) { // 5 weeks for compact view
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

    const handleDateClick = (dateStr) => {
        if (onDateSelect) {
            onDateSelect(dateStr);
        }
    };

    const getDayClassName = (day) => {
        let classes = 'p-1.5 md:p-2 text-center rounded-lg border transition-all text-xs md:text-sm ';
        
        if (day.isSelected) {
            classes += 'bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] border-[var(--theme-primary)] font-bold ';
        } else if (day.isToday) {
            classes += 'bg-[var(--theme-primary-bg)] text-[var(--theme-primary)] border-[var(--theme-primary)] font-semibold ';
        } else if (!day.isCurrentMonth) {
            classes += 'text-gray-300 border-transparent ';
        } else {
            classes += 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 ';
        }

        if (day.backgroundColor && !day.isSelected) {
            classes += day.backgroundColor + ' ';
        }

        return classes.trim();
    };

    const monthNames = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];

    const dayNames = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

    return (
        <div className={`bg-white rounded-lg shadow border border-gray-200 p-3 md:p-4 ${className}`}>
            {/* Compact Header */}
            <div className="flex items-center justify-between mb-3">
                <h3 className="text-sm md:text-base font-bold text-gray-900">
                    {monthNames[month]} {year}
                </h3>
                <div className="flex items-center space-x-1">
                    <button
                        onClick={handlePrevMonth}
                        className="p-1 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded transition-colors"
                        aria-label="Previous month"
                    >
                        <ChevronLeft className="w-4 h-4" />
                    </button>
                    <button
                        onClick={handleNextMonth}
                        className="p-1 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded transition-colors"
                        aria-label="Next month"
                    >
                        <ChevronRight className="w-4 h-4" />
                    </button>
                </div>
            </div>

            {/* Compact Grid */}
            <div className="grid grid-cols-7 gap-1">
                {/* Day Headers */}
                {dayNames.map((dayName, idx) => (
                    <div
                        key={idx}
                        className="p-1 text-center text-xs font-semibold text-gray-500"
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
                        title={day.date}
                    >
                        <div>{day.day}</div>
                        {/* Mini indicators */}
                        {day.indicators && day.indicators.length > 0 && !day.isSelected && (
                            <div className="flex justify-center gap-0.5 mt-0.5">
                                {day.indicators.slice(0, 2).map((indicator, idx) => (
                                    <div
                                        key={idx}
                                        className={`w-1 h-1 rounded-full ${indicator.color || 'bg-gray-400'}`}
                                    />
                                ))}
                            </div>
                        )}
                    </button>
                ))}
            </div>
        </div>
    );
}



