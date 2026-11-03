@php
    $settings = \App\Models\ShopSetting::getSettings();
    $logoUrl = $settings->getLogoUrl();
@endphp

<div class="w-full">
    @if($logoUrl)
        <div class="p-4 rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-center shrink-0">
                    <img src="{{ $logoUrl }}" alt="{{ $settings->shop_name }}" class="max-h-16 max-w-[140px] object-contain rounded-md">
                </div>
                <div>
                    <div class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>{{ __('Current Active Brand Logo (বর্তমান লোগো)') }}</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300/60 dark:border-emerald-800/40">
                            {{ __('Active / ব্যবহৃত হচ্ছে') }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        {{ __('This logo is displayed beside the shop name in the top navigation, sales invoices, money receipts, and reports.') }}
                    </div>
                </div>
            </div>

            <button
                type="button"
                wire:click="deleteLogo"
                wire:confirm="{{ __('Are you sure you want to delete the shop logo? The default store icon will be used instead. (আপনি কি নিশ্চিতভাবে এই লোগোটি মুছে ফেলতে চান?)') }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800/60 rounded-xl transition shadow-sm hover:scale-[1.02] active:scale-[0.98] shrink-0"
            >
                <x-heroicon-m-trash class="w-4 h-4 text-rose-600 dark:text-rose-400" />
                <span>{{ __('Delete Logo (লোগো মুছুন)') }}</span>
            </button>
        </div>
    @else
        <div class="p-4 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/30 flex items-center gap-3 text-slate-500 dark:text-slate-400 text-xs">
            <div class="w-10 h-10 rounded-xl bg-slate-200/70 dark:bg-slate-800 flex items-center justify-center shrink-0">
                <x-heroicon-o-photo class="w-5 h-5 text-slate-400" />
            </div>
            <div>
                <div class="font-bold text-slate-700 dark:text-slate-300 text-xs">
                    {{ __('No Custom Logo Uploaded (কোনো লোগো আপলোড করা হয়নি)') }}
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5">
                    {{ __('Upload an image file below to place your shop logo beside the store name across the entire ERP.') }}
                </div>
            </div>
        </div>
    @endif
</div>
