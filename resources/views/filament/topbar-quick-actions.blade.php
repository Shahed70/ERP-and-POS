<div x-data="{
        isOnline: navigator.onLine,
        isSyncing: false,
        pendingCount: 0,
        init() {
            window.addEventListener('aquafeed:status-changed', (e) => {
                this.isOnline = e.detail.isOnline;
                this.isSyncing = e.detail.isSyncing;
                this.pendingCount = e.detail.pendingCount;
            });
            if (window.AquaFeedDB) {
                this.isOnline = window.AquaFeedDB.isOnline;
                this.pendingCount = window.AquaFeedDB.pendingCount;
            }
        }
    }"
    class="flex items-center gap-2 mr-2">

    <!-- Network & Sync Status Indicator -->
    <a href="/admin/offline-pos"
       title="{{ __('Click to open Offline POS & Sync Manager') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold transition shadow-sm cursor-pointer"
       :class="isOnline ? (pendingCount > 0 ? 'bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-700 animate-pulse' : 'bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40') : 'bg-rose-100 text-rose-800 border border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800 animate-pulse'">
        <span class="relative flex h-2 w-2">
            <span x-show="pendingCount > 0 || !isOnline" class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                  :class="!isOnline ? 'bg-rose-400' : 'bg-amber-400'"></span>
            <span class="relative inline-flex rounded-full h-2 w-2"
                  :class="!isOnline ? 'bg-rose-500' : (pendingCount > 0 ? 'bg-amber-500' : 'bg-emerald-500')"></span>
        </span>
        <span x-show="isOnline && pendingCount === 0" class="hidden sm:inline">{{ __('Live Sync · Online') }}</span>
        <span x-show="isOnline && pendingCount > 0" class="inline" x-text="'Syncing (' + pendingCount + ')'"></span>
        <span x-show="!isOnline" class="inline" x-text="'Offline (' + pendingCount + ')'"></span>
    </a>

    <!-- Offline POS Quick Action -->
    <a href="/admin/offline-pos"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-bold text-xs bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-sm transition">
        <x-heroicon-m-signal-slash class="w-3.5 h-3.5 text-teal-500" />
        <span class="hidden md:inline">{{ __('Offline POS') }}</span>
    </a>

    @if(auth()->user()?->hasRole('Admin'))
        <!-- Topbar Quick Action: + New Sale [POS] -->
        <a href="/admin/sales/create"
           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl font-bold text-xs bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white shadow-md shadow-orange-500/20 transition-all duration-200 hover:scale-[1.03] active:scale-[0.98]">
            <x-heroicon-m-bolt class="w-3.5 h-3.5 text-white animate-bounce" />
            <span>{{ __('+ New Sale') }}</span>
        </a>
    @endif
</div>
