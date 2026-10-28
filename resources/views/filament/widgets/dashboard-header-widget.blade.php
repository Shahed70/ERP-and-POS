<x-filament-widgets::widget>
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-primary-950 text-white rounded-2xl p-6 shadow-md border border-slate-700/60 flex flex-col md:flex-row md:items-center md:justify-between gap-5">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    {{ __('Live Store Hub') }}
                </span>
                <span class="text-xs text-slate-300 pl-4">
                    {{ $dateFormatted }}
                </span>
            </div>

            <h1 class="text-l md:text-3xl font-black tracking-tight text-white flex items-center gap-2 pt-2">
                {{ __('স্বাগতম,') }} {{ $user?->name ?? __('User') }}
                <!-- <span class="text-xs font-semibold px-2 py-0.5 rounded bg-primary-500/30 text-primary-200 border border-primary-400/40">
                    {{ $isAdmin ? __('Admin / স্বত্বাধিকারী') : __('Viewer / পর্যবেক্ষক') }}
                </span> -->
            </h1>

            <p class="text-sm text-slate-300 py-3">
                <span class="font-bold text-primary-300">{{ $shop->shop_name }}</span> — {{ $shop->shop_tagline }}
            </p>
        </div>

        <!-- Quick Action Buttons -->
        <div class="flex flex-wrap items-center gap-4">
            @if($isAdmin)
                <a href="/admin/sales/create"
                   class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl font-bold text-sm bg-primary-500 hover:bg-primary-600 text-white shadow-sm transition hover:scale-102">
                    <x-heroicon-m-plus-circle class="w-5 h-5" />
                    {{ __('+ New Sale (POS)') }}
                </a>

                <a href="/admin/purchases/create"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl font-semibold text-sm bg-slate-800 hover:bg-slate-700 text-slate-100 border border-slate-600 transition">
                    <x-heroicon-m-truck class="w-4 h-4 text-amber-400" />
                    {{ __('+ Purchase (ক্রয়)') }}
                </a>

                <a href="/admin/payment-collections/create"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl font-semibold text-sm bg-slate-800 hover:bg-slate-700 text-slate-100 border border-slate-600 transition">
                    <x-heroicon-m-banknotes class="w-4 h-4 text-emerald-400" />
                    {{ __('Collect Due (আদায়)') }}
                </a>
            @endif

            <a href="/admin/executive-summary-report"
               class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl font-semibold text-sm bg-slate-800/80 hover:bg-slate-700 text-slate-200 border border-slate-600 transition">
                <x-heroicon-m-presentation-chart-line class="w-4 h-4 text-sky-400" />
                {{ __('Executive P&L') }}
            </a>
        </div>
    </div>
</x-filament-widgets::widget>
