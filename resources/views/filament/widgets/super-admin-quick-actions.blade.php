<x-filament-widgets::widget>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: linear-gradient(to bottom right, #1E3A5F, #2E5A8F);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">System administration tasks</p>
                </div>
            </div>
            <span class="hidden md:inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: rgba(30, 58, 95, 0.1); color: #1E3A5F;">
                {{ \App\Models\Facility::count() }} Facilities
            </span>
        </div>
        
        <!-- Actions Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Manage Facilities -->
            <a href="{{ route('filament.admin.resources.facilities.index') }}" class="group relative overflow-hidden rounded-lg border p-4 hover:shadow-lg transition-all duration-200 hover:-translate-y-0.5" style="background: linear-gradient(to bottom right, rgba(30, 58, 95, 0.05), rgba(30, 58, 95, 0.1)); border-color: rgba(30, 58, 95, 0.2);">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-200 shadow-sm" style="background-color: #1E3A5F;">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm">Facilities</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">Manage all facilities</p>
                    </div>
                </div>
            </a>
            
            <!-- Review Registrations -->
            <a href="{{ route('filament.admin.resources.facility-registrations.index', ['tableFilters[status][value]' => 'pending']) }}" class="group relative overflow-hidden rounded-lg border p-4 hover:shadow-lg transition-all duration-200 hover:-translate-y-0.5" style="background: linear-gradient(to bottom right, rgba(134, 239, 172, 0.1), rgba(134, 239, 172, 0.15)); border-color: rgba(134, 239, 172, 0.3);">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-200 shadow-sm" style="background-color: #86EFAC;">
                        <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm">Registrations</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">Review pending</p>
                    </div>
                </div>
            </a>
            
            <!-- Manage Users -->
            <a href="{{ route('filament.admin.resources.users.index') }}" class="group relative overflow-hidden rounded-lg border p-4 hover:shadow-lg transition-all duration-200 hover:-translate-y-0.5" style="background: linear-gradient(to bottom right, rgba(30, 58, 95, 0.05), rgba(30, 58, 95, 0.1)); border-color: rgba(30, 58, 95, 0.2);">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-200 shadow-sm" style="background-color: #1E3A5F;">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm">Users</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">System users</p>
                    </div>
                </div>
            </a>
            
            <!-- System Settings -->
            <a href="{{ route('filament.admin.resources.roles.index') }}" class="group relative overflow-hidden rounded-lg border p-4 hover:shadow-lg transition-all duration-200 hover:-translate-y-0.5" style="background: linear-gradient(to bottom right, rgba(134, 239, 172, 0.1), rgba(134, 239, 172, 0.15)); border-color: rgba(134, 239, 172, 0.3);">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-200 shadow-sm" style="background-color: #86EFAC;">
                        <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm">Roles & Permissions</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">Access control</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</x-filament-widgets::widget>

