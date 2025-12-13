import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { Calendar, Clock, MapPin } from 'lucide-react';
import api from '../../services/api';

/**
 * TodaysSchedule - Timeline view of today's appointments/schedule
 */
export default function TodaysSchedule() {
    const navigate = useNavigate();

    const { data: schedule, isLoading } = useQuery({
        queryKey: ['todays-schedule'],
        queryFn: async () => {
            const response = await api.get('/dashboard/todays-schedule');
            return response.data?.data || response.data || [];
        },
        retry: 1,
        refetchInterval: 300000, // Refresh every 5 minutes
    });

    if (isLoading) {
        return (
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div className="animate-pulse">
                    <div className="h-6 bg-gray-200 rounded w-1/3 mb-4"></div>
                    <div className="space-y-4">
                        {[1, 2, 3].map((i) => (
                            <div key={i} className="flex gap-4">
                                <div className="w-16 h-16 bg-gray-200 rounded-full"></div>
                                <div className="flex-1 space-y-2">
                                    <div className="h-4 bg-gray-200 rounded w-3/4"></div>
                                    <div className="h-3 bg-gray-200 rounded w-1/2"></div>
                                    <div className="h-3 bg-gray-200 rounded w-2/3"></div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        );
    }

    if (!schedule || schedule.length === 0) {
        return (
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center gap-2">
                        <Calendar className="w-5 h-5 text-blue-500" />
                        <h2 className="text-lg font-semibold text-gray-900">Today's Schedule</h2>
                    </div>
                </div>
                <div className="text-center py-8">
                    <Calendar className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                    <p className="text-sm text-gray-500">No appointments scheduled for today</p>
                </div>
            </div>
        );
    }

    // Sort by time
    const sortedSchedule = [...schedule].sort((a, b) => {
        if (!a.time_24h || !b.time_24h) return 0;
        return a.time_24h.localeCompare(b.time_24h);
    });

    const getCategoryStyles = (color) => {
        const styles = {
            blue: 'bg-blue-100 text-blue-700',
            purple: 'bg-purple-100 text-purple-700',
            green: 'bg-green-100 text-green-700',
            pink: 'bg-pink-100 text-pink-700',
        };
        return styles[color] || styles.blue;
    };

    const handleEventClick = (event) => {
        navigate(`/appointments/${event.id}`);
    };

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            {/* Header */}
            <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <Calendar className="w-5 h-5 text-blue-500" />
                    <h2 className="text-lg font-semibold text-gray-900">Today's Schedule</h2>
                </div>
                <span className="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-medium rounded-full">
                    {schedule.length} {schedule.length === 1 ? 'Event' : 'Events'}
                </span>
            </div>

            {/* Timeline */}
            <div className="p-6">
                <div className="relative">
                    {/* Timeline line */}
                    <div className="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                    
                    {/* Events */}
                    <div className="space-y-6">
                        {sortedSchedule.map((event, index) => (
                            <div
                                key={event.id}
                                className="relative flex gap-4 cursor-pointer group"
                                onClick={() => handleEventClick(event)}
                            >
                                {/* Time indicator */}
                                <div className="relative z-10 flex-shrink-0">
                                    <div className="w-16 h-16 rounded-full bg-purple-600 text-white flex items-center justify-center font-semibold text-sm shadow-md group-hover:bg-purple-700 transition-colors">
                                        {event.time_short}
                                    </div>
                                </div>

                                {/* Event content */}
                                <div className="flex-1 min-w-0 pt-1 pb-4 border-b border-gray-100 last:border-b-0">
                                    <div className="flex items-start justify-between gap-3 mb-2">
                                        <div className="flex-1 min-w-0">
                                            <h3 className="text-base font-semibold text-gray-900 mb-1 group-hover:text-purple-600 transition-colors">
                                                {event.title}
                                            </h3>
                                            <p className="text-sm text-gray-600 mb-2">
                                                {event.resident_name}
                                            </p>
                                            <div className="flex items-center gap-4 text-xs text-gray-500">
                                                {event.time && (
                                                    <div className="flex items-center gap-1">
                                                        <Clock className="w-3.5 h-3.5" />
                                                        <span>{event.time}</span>
                                                    </div>
                                                )}
                                                {event.location && (
                                                    <div className="flex items-center gap-1">
                                                        <MapPin className="w-3.5 h-3.5" />
                                                        <span>{event.location}</span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                        {/* Category tag */}
                                        <span className={`px-3 py-1 rounded-full text-xs font-medium flex-shrink-0 ${getCategoryStyles(event.category_color)}`}>
                                            {event.category}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
