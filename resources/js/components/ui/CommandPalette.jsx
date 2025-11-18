import React, { useState, useEffect, useRef } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { Search, Command, ArrowRight, Clock, FileText, Users, Calendar, Heart, Pill, Moon, ClipboardList, Building2, Settings } from 'lucide-react';

const COMMANDS = [
    { id: 'dashboard', label: 'Dashboard', path: '/dashboard', icon: Command, category: 'Navigation' },
    { id: 'residents', label: 'Residents', path: '/administration/residents', icon: Users, category: 'Administration' },
    { id: 'medications', label: 'Medications', path: '/medications', icon: Pill, category: 'Care' },
    { id: 'vitals', label: 'Vitals', path: '/vitals', icon: Heart, category: 'Care' },
    { id: 'appointments', label: 'Appointments', path: '/appointments', icon: Calendar, category: 'Care' },
    { id: 'assessments', label: 'Assessments', path: '/assessments', icon: ClipboardList, category: 'Care' },
    { id: 'sleep', label: 'Sleep Records', path: '/sleep', icon: Moon, category: 'Care' },
    { id: 'reports', label: 'Reports', path: '/reports', icon: FileText, category: 'Reports' },
    { id: 'facilities', label: 'Facilities', path: '/administration/facilities', icon: Building2, category: 'Administration' },
    { id: 'users', label: 'Users', path: '/administration/users', icon: Users, category: 'Administration' },
    { id: 'settings', label: 'Settings', path: '/profile', icon: Settings, category: 'Settings' },
];

export default function CommandPalette({ isOpen, onClose }) {
    const navigate = useNavigate();
    const location = useLocation();
    const [search, setSearch] = useState('');
    const [selectedIndex, setSelectedIndex] = useState(0);
    const inputRef = useRef(null);
    const listRef = useRef(null);

    const filteredCommands = React.useMemo(() => {
        if (!search.trim()) return COMMANDS;
        
        const query = search.toLowerCase();
        return COMMANDS.filter(cmd => 
            cmd.label.toLowerCase().includes(query) ||
            cmd.category.toLowerCase().includes(query)
        );
    }, [search]);

    useEffect(() => {
        if (isOpen) {
            inputRef.current?.focus();
            setSearch('');
            setSelectedIndex(0);
        }
    }, [isOpen]);

    useEffect(() => {
        if (!isOpen) return;

        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                onClose();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                setSelectedIndex(prev => 
                    prev < filteredCommands.length - 1 ? prev + 1 : prev
                );
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setSelectedIndex(prev => prev > 0 ? prev - 1 : 0);
            } else if (e.key === 'Enter' && filteredCommands[selectedIndex]) {
                handleSelect(filteredCommands[selectedIndex]);
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [isOpen, filteredCommands, selectedIndex, onClose]);

    useEffect(() => {
        if (listRef.current && selectedIndex >= 0) {
            const selectedElement = listRef.current.children[selectedIndex];
            if (selectedElement) {
                selectedElement.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        }
    }, [selectedIndex]);

    const handleSelect = (command) => {
        navigate(command.path);
        onClose();
    };

    if (!isOpen) return null;

    return (
        <div
            className="fixed inset-0 z-50 overflow-y-auto"
            onClick={onClose}
        >
            <div className="flex min-h-full items-start justify-center p-4 pt-20">
                <div
                    className="w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden"
                    onClick={(e) => e.stopPropagation()}
                >
                    {/* Search Input */}
                    <div className="flex items-center px-4 py-3 border-b border-gray-200">
                        <Search className="w-5 h-5 text-gray-400 mr-3" />
                        <input
                            ref={inputRef}
                            type="text"
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                setSelectedIndex(0);
                            }}
                            placeholder="Search pages and navigate..."
                            className="flex-1 outline-none text-gray-900 placeholder-gray-400"
                            autoFocus
                        />
                        <div className="flex items-center space-x-1 text-xs text-gray-400 ml-4">
                            <kbd className="px-2 py-1 bg-gray-100 rounded">Esc</kbd>
                            <span>to close</span>
                        </div>
                    </div>

                    {/* Results */}
                    <div className="max-h-96 overflow-y-auto">
                        {filteredCommands.length === 0 ? (
                            <div className="px-4 py-12 text-center text-gray-500">
                                <p className="text-sm">No results found</p>
                            </div>
                        ) : (
                            <div ref={listRef} className="py-2">
                                {filteredCommands.map((command, index) => {
                                    const Icon = command.icon;
                                    const isSelected = index === selectedIndex;

                                    return (
                                        <button
                                            key={command.id}
                                            onClick={() => handleSelect(command)}
                                            className={`w-full flex items-center space-x-3 px-4 py-3 text-left transition-colors ${
                                                isSelected
                                                    ? 'bg-[#25603E] text-white'
                                                    : 'hover:bg-gray-50 text-gray-900'
                                            }`}
                                            onMouseEnter={() => setSelectedIndex(index)}
                                        >
                                            <Icon className={`w-5 h-5 ${isSelected ? 'text-white' : 'text-gray-400'}`} />
                                            <div className="flex-1">
                                                <div className={`font-medium ${isSelected ? 'text-white' : 'text-gray-900'}`}>
                                                    {command.label}
                                                </div>
                                                <div className={`text-xs ${isSelected ? 'text-green-100' : 'text-gray-500'}`}>
                                                    {command.category}
                                                </div>
                                            </div>
                                            {isSelected && (
                                                <ArrowRight className="w-4 h-4 text-white" />
                                            )}
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                    </div>

                    {/* Footer */}
                    <div className="px-4 py-2 border-t border-gray-200 bg-gray-50 flex items-center justify-between text-xs text-gray-500">
                        <div className="flex items-center space-x-4">
                            <div className="flex items-center space-x-1">
                                <kbd className="px-1.5 py-0.5 bg-white border border-gray-300 rounded">↑</kbd>
                                <kbd className="px-1.5 py-0.5 bg-white border border-gray-300 rounded">↓</kbd>
                                <span>to navigate</span>
                            </div>
                            <div className="flex items-center space-x-1">
                                <kbd className="px-1.5 py-0.5 bg-white border border-gray-300 rounded">Enter</kbd>
                                <span>to select</span>
                            </div>
                        </div>
                        <div className="text-gray-400">
                            {filteredCommands.length} result{filteredCommands.length !== 1 ? 's' : ''}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}



