<x-filament-widgets::widget>
    <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 shadow-lg">
        <div class="absolute top-0 right-0 -mt-4 -mr-16 opacity-10">
            <svg class="w-48 h-48" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        </div>
        
        <div class="relative px-6 py-4 flex items-center justify-between">
            <div class="flex-1">
                <h2 class="text-lg font-semibold text-white mb-1">
                    Welcome, {{ auth()->user()->name }}!
                </h2>
                <p class="text-purple-100 text-sm">
                    System Administrator Dashboard - Managing all facilities and system operations
                </p>
            </div>
            
            <div class="hidden md:flex items-center space-x-4">
                <div class="text-right">
                    <p class="text-purple-100 text-xs">{{ now()->format('l') }}</p>
                    <p class="text-white font-medium">{{ now()->format('F j, Y') }}</p>
                </div>
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>

