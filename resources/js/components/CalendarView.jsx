import React from 'react';
import { Calendar, momentLocalizer } from 'react-big-calendar';
import moment from 'moment';
import 'react-big-calendar/lib/css/react-big-calendar.css';

const localizer = momentLocalizer(moment);

export default function CalendarView({ events, onSelectEvent, onSelectSlot, defaultDate, views = ['month', 'week', 'day'], height = '600px', ...props }) {
    // Format events for react-big-calendar
    const formattedEvents = React.useMemo(() => {
        if (!events || !Array.isArray(events)) return [];
        
        return events.map(event => {
            try {
                const start = event.start ? new Date(event.start) : new Date();
                const end = event.end ? new Date(event.end) : (moment ? moment(start).add(1, 'hour').toDate() : new Date(start.getTime() + 3600000));
                
                return {
                    ...event,
                    start,
                    end,
                };
            } catch (err) {
                console.error('Error formatting event:', err, event);
                return null;
            }
        }).filter(Boolean);
    }, [events]);

    return (
        <div style={{ height, width: '100%' }} className="bg-white rounded-lg shadow-sm p-4 w-full">
            <style>{`
                .rbc-calendar {
                    font-size: 11px;
                }
                .rbc-event {
                    font-size: 10px;
                    line-height: 1.2;
                    padding: 1px 3px;
                }
                .rbc-event-label {
                    font-size: 9px;
                }
                .rbc-day-slot .rbc-event {
                    font-size: 10px;
                    padding: 1px 2px;
                }
                .rbc-month-view .rbc-event {
                    font-size: 9px;
                    padding: 1px 2px;
                    min-height: 16px;
                }
                .rbc-agenda-view .rbc-event {
                    font-size: 11px;
                }
            `}</style>
            <Calendar
                localizer={localizer}
                events={formattedEvents}
                startAccessor="start"
                endAccessor="end"
                defaultDate={defaultDate || new Date()}
                views={views}
                onSelectEvent={onSelectEvent}
                onSelectSlot={onSelectSlot}
                selectable
                style={{ height: '100%', width: '100%' }}
                eventPropGetter={(event) => {
                    const backgroundColor = event.color || 'var(--theme-primary)';
                    const borderColor = event.borderColor || backgroundColor;
                    return {
                        style: {
                            backgroundColor,
                            borderColor,
                            borderWidth: '1px',
                            borderRadius: '3px',
                            color: event.textColor || '#ffffff',
                            padding: '1px 3px',
                            fontSize: '9px',
                            lineHeight: '1.2',
                            minHeight: '16px',
                        },
                    };
                }}
                {...props}
            />
        </div>
    );
}

