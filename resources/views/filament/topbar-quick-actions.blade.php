<div class="flex items-center gap-2.5 mr-2">
    <!-- Depot Status Chip (Hidden on small screens) -->
    <div class="hidden lg:flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40">
        <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
        </span>
        <span>{{ __('Khulna Central Hub · Live POS') }}</span>
    </div>

    @if(auth()->user()?->hasRole('Admin'))
        <!-- Topbar Quick Action: + New Sale [POS] -->
        <a href="/admin/sales/create"
           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl font-bold text-xs bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white shadow-md shadow-orange-500/20 transition-all duration-200 hover:scale-[1.03] active:scale-[0.98]">
            <x-heroicon-m-bolt class="w-3.5 h-3.5 text-white animate-bounce" />
            <span>{{ __('+ New Sale [POS]') }}</span>
        </a>
    @endif
</div>
