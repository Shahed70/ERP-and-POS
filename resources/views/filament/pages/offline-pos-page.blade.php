<x-filament-panels::page>
    <div x-data="offlinePosApp()" x-init="initApp()" class="space-y-6">

        {{-- Connection & Auto-Sync Alert Banner --}}
        <div class="p-4 rounded-2xl border transition-all duration-300 flex flex-wrap items-center justify-between gap-4 shadow-sm"
             :class="isOnline ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-950 dark:text-emerald-200' : 'bg-amber-500/15 border-amber-500/40 text-amber-950 dark:text-amber-200'">
            <div class="flex items-center gap-3">
                <span class="relative flex h-3.5 w-3.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                          :class="isOnline ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5"
                          :class="isOnline ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                </span>
                <div>
                    <div class="font-extrabold text-sm sm:text-base flex items-center gap-2">
                        <span x-text="isOnline ? '🟢 Online Mode (সরাসরি সংযুক্ত)' : '🟡 Offline Mode — Internet Disconnected (ইন্টারনেট বিচ্ছিন্ন)'"></span>
                        <span x-show="pendingCount > 0"
                              class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-200 text-amber-900 dark:bg-amber-900/60 dark:text-amber-200 border border-amber-300 dark:border-amber-700 animate-pulse">
                            <span x-text="pendingCount"></span> {{ __('Pending in IndexedDB') }}
                        </span>
                    </div>
                    <div class="text-xs opacity-85 mt-0.5">
                        <span x-show="isOnline">{{ __('Connected to local & cloud database. Offline backup cache is warm and ready.') }}</span>
                        <span x-show="!isOnline">{{ __('No internet connection. The software is 100% operational locally via IndexedDB. All transactions will automatically sync when connection returns.') }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <button type="button"
                        x-show="pendingCount > 0"
                        @click="triggerManualSync()"
                        :disabled="isSyncing || !isOnline"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <x-heroicon-m-arrow-path class="w-4 h-4" ::class="{'animate-spin': isSyncing}" />
                    <span x-text="isSyncing ? 'Syncing...' : 'Sync Pending Now (' + pendingCount + ')'"></span>
                </button>

                <button type="button"
                        @click="refreshLocalCache()"
                        :disabled="!isOnline"
                        title="{{ __('Refresh products and customer catalog from server') }}"
                        class="p-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition">
                    <x-heroicon-m-arrow-path class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- Navigation Tabs --}}
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
            <button type="button"
                    @click="activeTab = 'pos'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition"
                    :class="activeTab === 'pos' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700'">
                <x-heroicon-m-shopping-cart class="w-4 h-4" />
                <span>{{ __('1. Offline POS Sale (বিক্রয় চালান)') }}</span>
            </button>

            <button type="button"
                    @click="activeTab = 'collection'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition"
                    :class="activeTab === 'collection' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700'">
                <x-heroicon-m-banknotes class="w-4 h-4" />
                <span>{{ __('2. Due Collection (বাকি আদায়)') }}</span>
            </button>

            <button type="button"
                    @click="activeTab = 'expense'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition"
                    :class="activeTab === 'expense' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700'">
                <x-heroicon-m-receipt-percent class="w-4 h-4" />
                <span>{{ __('3. Quick Expense (দৈনন্দিন খরচ)') }}</span>
            </button>

            <button type="button"
                    @click="activeTab = 'queue'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition ml-auto"
                    :class="activeTab === 'queue' ? 'bg-slate-900 text-white shadow-sm dark:bg-slate-700' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700'">
                <x-heroicon-m-queue-list class="w-4 h-4 text-amber-500" />
                <span>{{ __('Pending Sync Queue (অপেক্ষমান তালিকা)') }}</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white" x-text="pendingCount"></span>
            </button>
        </div>

        {{-- TAB 1: OFFLINE POS SALE --}}
        <div x-show="activeTab === 'pos'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Left Column: Product Selection & Catalog --}}
            <div class="lg:col-span-7 space-y-4">
                {{-- Search & Category Filter --}}
                <div class="rpt-filter-card mb-0">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <input type="text"
                                   x-model="productSearch"
                                   placeholder="{{ __('Search feed products by name, brand or feed type...') }}"
                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-teal-500">
                        </div>
                        <select x-model="selectedCategory"
                                class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-3 py-2.5 text-slate-900 dark:text-white">
                            <option value="">{{ __('All Feed Categories') }}</option>
                            <template x-for="cat in categories" :key="cat">
                                <option :value="cat" x-text="cat"></option>
                            </template>
                        </select>
                    </div>
                </div>

                {{-- Products Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 max-h-[580px] overflow-y-auto pr-1">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <div @click="addToCart(product)"
                             class="p-4 rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm hover:shadow-md hover:border-teal-500/50 cursor-pointer transition flex flex-col justify-between select-none">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-300 border border-teal-200/60"
                                          x-text="product.brand"></span>
                                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400"
                                          x-text="product.pack_size + ' KG Bag'"></span>
                                </div>
                                <h4 class="font-bold text-sm text-slate-900 dark:text-white leading-snug"
                                    x-text="product.name"></h4>
                            </div>

                            <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                                <div>
                                    <div class="text-base font-extrabold text-teal-600 dark:text-teal-400">
                                        ৳<span x-text="formatNumber(product.sale_price)"></span>
                                    </div>
                                    <div class="text-[11px] text-slate-400"
                                         :class="{'text-rose-500 font-bold': product.total_bags <= product.reorder_level_bags}">
                                        Stock: <span x-text="product.total_bags"></span> Bags
                                    </div>
                                </div>

                                <button type="button"
                                        class="px-3 py-1.5 rounded-xl font-bold text-xs bg-slate-100 hover:bg-teal-600 hover:text-white text-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-teal-600 transition">
                                    + Add
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Right Column: Cart, Customer & Checkout --}}
            <div class="lg:col-span-5 space-y-4">
                <div class="p-5 rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm space-y-4">
                    {{-- Customer Selector --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                                {{ __('Customer / Gher Owner (ক্রেতা)') }}
                            </label>
                            <button type="button"
                                    @click="selectWalkInCustomer()"
                                    class="text-[11px] font-bold text-teal-600 dark:text-teal-400 hover:underline">
                                {{ __('Walk-in (নগদ খরিদ্দার)') }}
                            </button>
                        </div>
                        <select x-model="selectedCustomerId"
                                @change="onCustomerChange()"
                                class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-3 py-2 text-slate-900 dark:text-white font-medium">
                            <template x-for="c in customers" :key="c.id">
                                <option :value="c.id" x-text="c.name + (c.phone ? ' (' + c.phone + ')' : '')"></option>
                            </template>
                        </select>
                        <div x-show="selectedCustomer && selectedCustomer.current_due > 0"
                             class="text-xs text-rose-600 dark:text-rose-400 font-bold mt-1">
                            Current Due (পূর্বের দেনা): ৳<span x-text="formatNumber(selectedCustomer.current_due)"></span>
                        </div>
                    </div>

                    {{-- Cart Items Table --}}
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center justify-between">
                            <span>{{ __('Invoice Items') }}</span>
                            <span class="text-slate-400" x-text="cart.length + ' item(s)'"></span>
                        </div>

                        <div class="space-y-2 max-h-[220px] overflow-y-auto pr-1">
                            <template x-for="(item, index) in cart" :key="index">
                                <div class="p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 flex items-center justify-between gap-2 text-xs">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-white truncate" x-text="item.name"></div>
                                        <div class="text-[11px] text-slate-400">
                                            ৳<span x-text="formatNumber(item.unit_price)"></span> / <span x-text="item.unit_type"></span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button type="button" @click="decrementQty(index)" class="w-6 h-6 rounded-lg bg-slate-200 dark:bg-slate-700 font-bold flex items-center justify-center">-</button>
                                        <input type="number" min="1" x-model.number="item.quantity" @input="recalcCart()" class="w-12 text-center py-0.5 rounded-md border border-slate-300 dark:border-slate-600 font-bold text-xs bg-white dark:bg-slate-900">
                                        <button type="button" @click="incrementQty(index)" class="w-6 h-6 rounded-lg bg-slate-200 dark:bg-slate-700 font-bold flex items-center justify-center">+</button>
                                    </div>

                                    <div class="text-right shrink-0 min-w-[65px]">
                                        <div class="font-extrabold text-slate-900 dark:text-white">
                                            ৳<span x-text="formatNumber(item.line_total)"></span>
                                        </div>
                                        <button type="button" @click="removeFromCart(index)" class="text-rose-500 text-[10px] hover:underline">Remove</button>
                                    </div>
                                </div>
                            </template>
                            <div x-show="cart.length === 0" class="text-center py-8 text-xs text-slate-400">
                                {{ __('No products selected. Click any feed product on the left to add.') }}
                            </div>
                        </div>
                    </div>

                    {{-- Invoice Totals & Payment --}}
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('Subtotal (মোট)') }}</span>
                            <span class="font-bold text-slate-900 dark:text-white">৳<span x-text="formatNumber(cartSubtotal)"></span></span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">{{ __('Special Discount (ছাড়)') }}</span>
                            <div class="flex items-center gap-1">
                                <span>৳</span>
                                <input type="number" min="0" x-model.number="discountAmount" @input="recalcCart()" class="w-20 text-right py-0.5 px-1.5 rounded-md border border-slate-300 dark:border-slate-600 text-xs bg-white dark:bg-slate-900">
                            </div>
                        </div>

                        <div class="flex justify-between text-sm font-extrabold pt-1 border-t border-slate-100 dark:border-slate-800">
                            <span class="text-slate-900 dark:text-white">{{ __('Grand Total (সর্বমোট)') }}</span>
                            <span class="text-teal-600 dark:text-teal-400">৳<span x-text="formatNumber(cartGrandTotal)"></span></span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-2">
                            <div>
                                <label class="text-[11px] font-bold text-slate-500 block mb-0.5">{{ __('Cash Paid (নগদ জমা)') }}</label>
                                <input type="number" min="0" x-model.number="paidAmount" @input="recalcCart()" class="w-full py-1.5 px-2 rounded-lg border border-slate-300 dark:border-slate-600 font-bold text-xs bg-white dark:bg-slate-900">
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-500 block mb-0.5">{{ __('Due (বকেয়া)') }}</label>
                                <div class="py-1.5 px-2 rounded-lg bg-slate-100 dark:bg-slate-800 font-extrabold text-xs"
                                     :class="dueAmount > 0 ? 'text-rose-600' : 'text-emerald-600'">
                                    ৳<span x-text="formatNumber(dueAmount)"></span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="text-[11px] font-bold text-slate-500 block mb-0.5">{{ __('Payment Method') }}</label>
                            <select x-model="paymentMethod" class="w-full py-1.5 px-2 rounded-lg border border-slate-300 dark:border-slate-600 text-xs bg-white dark:bg-slate-900">
                                <option value="cash">Cash (নগদ)</option>
                                <option value="bkash">bKash (বিকাশ)</option>
                                <option value="bank">Bank Transfer (ব্যাংক)</option>
                                <option value="nagad">Nagad (নগদ)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Complete Sale Button --}}
                    <button type="button"
                            @click="completeOfflineSale()"
                            :disabled="cart.length === 0"
                            class="w-full py-3 rounded-xl font-black text-sm text-white bg-gradient-to-r from-teal-600 via-teal-700 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="isOnline">{{ __('✓ Complete Sale & Print Invoice') }}</span>
                        <span x-show="!isOnline">{{ __('✓ Save Offline Sale & Print [Local Queue]') }}</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- TAB 2: DUE COLLECTION --}}
        <div x-show="activeTab === 'collection'" class="max-w-xl mx-auto">
            <div class="p-6 rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm space-y-4">
                <h3 class="font-extrabold text-base text-slate-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-m-banknotes class="w-5 h-5 text-emerald-500" />
                    <span>{{ __('Record Customer Due Collection (বাকি আদায়)') }}</span>
                </h3>
                <p class="text-xs text-slate-500">
                    {{ __('Accept payment from farmer/client. Safe to record offline; balances sync automatically when connected.') }}
                </p>

                <div class="space-y-3 pt-2">
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1">{{ __('Select Customer (ক্রেতার নাম)') }}</label>
                        <select x-model="collectionCustomerId" @change="onCollectionCustomerChange()" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-3 py-2 text-slate-900 dark:text-white font-medium">
                            <option value="">-- Choose Customer --</option>
                            <template x-for="c in customers" :key="c.id">
                                <option :value="c.id" x-text="c.name + ' (Due: ৳' + formatNumber(c.current_due) + ')'"></option>
                            </template>
                        </select>
                    </div>

                    <div x-show="selectedCollectionCustomer" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-xs">
                        <div class="flex justify-between font-bold">
                            <span>{{ __('Current Outstanding Balance') }}:</span>
                            <span class="text-rose-600">৳<span x-text="formatNumber(selectedCollectionCustomer?.current_due || 0)"></span></span>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1">{{ __('Amount Paid (আদায়কৃত টাকা)') }}</label>
                        <input type="number" min="1" x-model.number="collectionAmount" placeholder="e.g. 5000" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-3 py-2 text-slate-900 dark:text-white font-bold">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1">{{ __('Payment Method (মাধ্যম)') }}</label>
                        <select x-model="collectionMethod" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-3 py-2 text-slate-900 dark:text-white">
                            <option value="cash">Cash (নগদ)</option>
                            <option value="bkash">bKash (বিকাশ)</option>
                            <option value="bank">Bank Deposit (ব্যাংক জমা)</option>
                            <option value="nagad">Nagad (নগদ)</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1">{{ __('Transaction Reference / Slip #') }}</label>
                        <input type="text" x-model="collectionReference" placeholder="e.g. TrxID / Bank Slip # / Manual MR #" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-3 py-2 text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1">{{ __('Notes (মন্তব্য)') }}</label>
                        <textarea x-model="collectionNotes" rows="2" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-3 py-2 text-slate-900 dark:text-white"></textarea>
                    </div>

                    <button type="button"
                            @click="saveOfflineCollection()"
                            :disabled="!collectionCustomerId || collectionAmount <= 0"
                            class="w-full py-2.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-md transition disabled:opacity-50">
                        {{ __('Save Due Collection & Print Receipt') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- TAB 3: QUICK EXPENSE --}}
        <div x-show="activeTab === 'expense'" class="max-w-xl mx-auto">
            <div class="p-6 rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm space-y-4">
                <h3 class="font-extrabold text-base text-slate-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-m-receipt-percent class="w-5 h-5 text-amber-500" />
                    <span>{{ __('Record Operating Expense (দৈনন্দিন খরচ)') }}</span>
                </h3>
                <p class="text-xs text-slate-500">
                    {{ __('Record shop rent, electricity, warehouse, snacks, or miscellaneous counter costs.') }}
                </p>

                <div class="space-y-3 pt-2">
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1">{{ __('Expense Category (খাত)') }}</label>
                        <select x-model="expenseCategory" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-3 py-2 text-slate-900 dark:text-white font-medium">
                            <option value="snack_expenses">{{ __('Snack Expenses (আপ্যায়ন / নাস্তা খরচ)') }}</option>
                            <option value="shop_rent">{{ __('Shop Rent (দোকান ভাড়া)') }}</option>
                            <option value="electricity_bill">{{ __('Electricity Bill (বিদ্যুৎ বিল)') }}</option>
                            <option value="warehouse_rent">{{ __('Warehouse Rent (গোডাউন ভাড়া)') }}</option>
                            <option value="others">{{ __('Others (অন্যান্য খরচ)') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1">{{ __('Description / Title (বিবরণ)') }}</label>
                        <input type="text" x-model="expenseTitle" placeholder="e.g. Tea & biscuits for farmers visiting counter" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-3 py-2 text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1">{{ __('Amount (টাকা)') }}</label>
                        <input type="number" min="1" x-model.number="expenseAmount" placeholder="e.g. 350" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-3 py-2 text-slate-900 dark:text-white font-bold">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1">{{ __('Paid To (প্রাপক)') }}</label>
                        <input type="text" x-model="expensePaidTo" placeholder="e.g. Barek Tea Stall" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-3 py-2 text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1">{{ __('Payment Method') }}</label>
                        <select x-model="expenseMethod" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-3 py-2 text-slate-900 dark:text-white">
                            <option value="cash">Cash (নগদ)</option>
                            <option value="bkash">bKash (বিকাশ)</option>
                            <option value="bank">Bank Transfer (ব্যাংক)</option>
                        </select>
                    </div>

                    <button type="button"
                            @click="saveOfflineExpense()"
                            :disabled="!expenseTitle || expenseAmount <= 0"
                            class="w-full py-2.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 shadow-md transition disabled:opacity-50">
                        {{ __('Save Operating Expense') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- TAB 4: PENDING SYNC QUEUE --}}
        <div x-show="activeTab === 'queue'" class="space-y-4">
            <div class="rpt-table-card">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="font-extrabold text-sm sm:text-base text-slate-900 dark:text-white">
                            {{ __('Offline Transaction Queue (অপেক্ষমান ডেটা)') }}
                        </h3>
                        <p class="text-xs text-slate-400">
                            {{ __('All entries saved while offline in browser IndexedDB. Automatically dispatched when internet connects.') }}
                        </p>
                    </div>
                    <button type="button"
                            x-show="pendingCount > 0"
                            @click="triggerManualSync()"
                            :disabled="isSyncing || !isOnline"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 transition flex items-center gap-2">
                        <x-heroicon-m-arrow-path class="w-4 h-4" ::class="{'animate-spin': isSyncing}" />
                        <span>{{ __('Sync All Now') }}</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="rpt-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type (ধরণ)</th>
                                <th>Details / Customer</th>
                                <th>Amount (৳)</th>
                                <th>Recorded Time</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in pendingList" :key="item.offline_id">
                                <tr>
                                    <td class="text-xs text-slate-400" x-text="idx + 1"></td>
                                    <td>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase"
                                              :class="{
                                                  'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300': item.type === 'sale',
                                                  'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300': item.type === 'collection',
                                                  'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300': item.type === 'expense'
                                              }"
                                              x-text="item.type"></span>
                                    </td>
                                    <td>
                                        <div class="font-bold text-slate-900 dark:text-white"
                                             x-text="getItemTitle(item)"></div>
                                        <div class="text-[11px] text-slate-400" x-text="item.offline_id"></div>
                                    </td>
                                    <td class="font-black text-slate-900 dark:text-white">
                                        ৳<span x-text="formatNumber(getItemAmount(item))"></span>
                                    </td>
                                    <td class="text-xs text-slate-500" x-text="formatDate(item.created_at)"></td>
                                    <td>
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Queued (IndexedDB)
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <button type="button" @click="deletePendingItem(item.offline_id)" class="text-rose-500 hover:underline text-xs font-bold">Delete</button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="pendingList.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                    {{ __('No pending offline transactions. All records are currently synchronized with the database.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- PRINTABLE OFFLINE RECEIPT MODAL --}}
        <div x-show="showReceiptModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white text-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 print:p-0 print:shadow-none print:m-0 print:max-w-none">
                {{-- Header --}}
                <div class="text-center border-b pb-3">
                    <h2 class="font-black text-xl tracking-tight text-teal-800" x-text="shop.name || 'AquaFeed Fish Feed Store'"></h2>
                    <div class="text-xs text-slate-500" x-text="shop.tagline || 'Wholesale & Retail Fish Feed'"></div>
                    <div class="text-xs text-slate-600 mt-1" x-text="shop.address || 'Rupsha Ghat, Khulna'"></div>
                    <div class="text-xs text-slate-600 font-bold" x-text="'Mobile: ' + (shop.phone || '01712-000000')"></div>
                    <div class="mt-2 inline-block px-2.5 py-0.5 rounded text-[11px] font-black bg-amber-100 text-amber-800 border border-amber-300">
                        {{ __('OFFLINE SALES INVOICE (অফলাইন রসিদ)') }}
                    </div>
                </div>

                {{-- Invoice Meta --}}
                <div class="text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Invoice #:</span>
                        <span class="font-bold font-mono" x-text="lastReceiptData?.invoice_no"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Date & Time:</span>
                        <span class="font-semibold" x-text="formatDate(lastReceiptData?.date)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Customer:</span>
                        <span class="font-bold text-slate-900" x-text="lastReceiptData?.customer_name"></span>
                    </div>
                </div>

                {{-- Itemized Table --}}
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="border-y border-slate-200 text-slate-500 text-left font-bold">
                            <th class="py-1">Feed Item</th>
                            <th class="py-1 text-center">Qty</th>
                            <th class="py-1 text-right">Price</th>
                            <th class="py-1 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in (lastReceiptData?.items || [])" :key="item.name">
                            <tr class="border-b border-slate-100">
                                <td class="py-1 font-semibold" x-text="item.name"></td>
                                <td class="py-1 text-center" x-text="item.quantity + ' ' + item.unit_type"></td>
                                <td class="py-1 text-right" x-text="'৳' + formatNumber(item.unit_price)"></td>
                                <td class="py-1 text-right font-bold" x-text="'৳' + formatNumber(item.line_total)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                {{-- Totals --}}
                <div class="border-t pt-2 space-y-1 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Subtotal:</span>
                        <span class="font-bold">৳<span x-text="formatNumber(lastReceiptData?.subtotal || 0)"></span></span>
                    </div>
                    <div class="flex justify-between" x-show="lastReceiptData?.discount > 0">
                        <span class="text-slate-500">Discount:</span>
                        <span class="text-rose-600 font-bold">-৳<span x-text="formatNumber(lastReceiptData?.discount || 0)"></span></span>
                    </div>
                    <div class="flex justify-between text-sm font-black border-t pt-1">
                        <span>Grand Total:</span>
                        <span class="text-teal-700">৳<span x-text="formatNumber(lastReceiptData?.grand_total || 0)"></span></span>
                    </div>
                    <div class="flex justify-between font-bold">
                        <span>Cash Paid:</span>
                        <span>৳<span x-text="formatNumber(lastReceiptData?.paid_amount || 0)"></span></span>
                    </div>
                    <div class="flex justify-between font-bold" :class="lastReceiptData?.due_amount > 0 ? 'text-rose-600' : 'text-emerald-600'">
                        <span>Due Amount:</span>
                        <span>৳<span x-text="formatNumber(lastReceiptData?.due_amount || 0)"></span></span>
                    </div>
                </div>

                {{-- Footer & Buttons --}}
                <div class="text-[10px] text-center text-slate-400 pt-2 border-t">
                    {{ __('Saved locally. Automatically synchronized with Central Depot Database when online.') }}
                </div>

                <div class="flex items-center gap-2 pt-2 print:hidden">
                    <button type="button" @click="window.print()" class="flex-1 py-2 rounded-xl text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 transition">
                        🖨️ {{ __('Print Receipt (রসিদ প্রিন্ট)') }}
                    </button>
                    <button type="button" @click="showReceiptModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>

    </div>

    {{-- Application Logic & IndexedDB Controller --}}
    <script>
        function offlinePosApp() {
            return {
                activeTab: 'pos',
                isOnline: navigator.onLine,
                isSyncing: false,
                pendingCount: 0,
                pendingList: [],
                products: [],
                customers: [],
                categories: [],
                shop: {},
                productSearch: '',
                selectedCategory: '',
                selectedCustomerId: 1,
                selectedCustomer: null,
                cart: [],
                discountAmount: 0,
                paidAmount: 0,
                paymentMethod: 'cash',
                paymentNotes: '',

                // Collection Form
                collectionCustomerId: '',
                selectedCollectionCustomer: null,
                collectionAmount: 0,
                collectionMethod: 'cash',
                collectionReference: '',
                collectionNotes: '',

                // Expense Form
                expenseCategory: 'snack_expenses',
                expenseTitle: '',
                expenseAmount: 0,
                expensePaidTo: '',
                expenseMethod: 'cash',

                // Receipt Modal
                showReceiptModal: false,
                lastReceiptData: null,

                async initApp() {
                    if (!window.AquaFeedDB) {
                        setTimeout(() => this.initApp(), 100);
                        return;
                    }

                    // Watch network changes
                    window.addEventListener('aquafeed:status-changed', (e) => {
                        this.isOnline = e.detail.isOnline;
                        this.isSyncing = e.detail.isSyncing;
                        this.pendingCount = e.detail.pendingCount;
                        this.loadPendingList();
                    });

                    window.addEventListener('aquafeed:sync-complete', () => {
                        this.loadCachedData();
                        this.loadPendingList();
                    });

                    await this.loadCachedData();
                    await this.loadPendingList();

                    // If online, refresh cache in background
                    if (this.isOnline) {
                        this.refreshLocalCache();
                    }
                },

                async loadCachedData() {
                    this.products = (await AquaFeedDB.getCached('products')) || [];
                    this.customers = (await AquaFeedDB.getCached('customers')) || [];
                    this.shop = (await AquaFeedDB.getCached('shop')) || {};

                    // Extract categories
                    const cats = new Set();
                    this.products.forEach(p => {
                        if (p.category_name) cats.add(p.category_name);
                    });
                    this.categories = Array.from(cats);

                    // Select default walk-in customer
                    this.selectWalkInCustomer();
                },

                async refreshLocalCache() {
                    const data = await AquaFeedDB.refreshCache();
                    if (data) {
                        this.products = data.products || [];
                        this.customers = data.customers || [];
                        this.shop = data.shop || {};
                    }
                },

                async loadPendingList() {
                    this.pendingList = await AquaFeedDB.getPending();
                    this.pendingCount = this.pendingList.length;
                },

                get filteredProducts() {
                    let list = this.products;
                    if (this.selectedCategory) {
                        list = list.filter(p => p.category_name === this.selectedCategory);
                    }
                    if (this.productSearch.trim()) {
                        const q = this.productSearch.toLowerCase();
                        list = list.filter(p =>
                            p.name.toLowerCase().includes(q) ||
                            p.brand.toLowerCase().includes(q) ||
                            p.feed_type.toLowerCase().includes(q)
                        );
                    }
                    return list;
                },

                selectWalkInCustomer() {
                    if (this.customers.length > 0) {
                        this.selectedCustomerId = this.customers[0].id;
                        this.selectedCustomer = this.customers[0];
                    }
                },

                onCustomerChange() {
                    this.selectedCustomer = this.customers.find(c => c.id == this.selectedCustomerId) || null;
                },

                addToCart(product) {
                    const existing = this.cart.find(item => item.product_id === product.id);
                    if (existing) {
                        existing.quantity += 1;
                        existing.line_total = existing.quantity * existing.unit_price;
                    } else {
                        this.cart.push({
                            product_id: product.id,
                            name: product.name,
                            brand: product.brand,
                            unit_type: 'bag',
                            pack_size: product.pack_size || 25.00,
                            quantity: 1,
                            unit_price: product.sale_price,
                            line_total: product.sale_price
                        });
                    }
                    this.recalcCart();
                },

                incrementQty(index) {
                    this.cart[index].quantity += 1;
                    this.cart[index].line_total = this.cart[index].quantity * this.cart[index].unit_price;
                    this.recalcCart();
                },

                decrementQty(index) {
                    if (this.cart[index].quantity > 1) {
                        this.cart[index].quantity -= 1;
                        this.cart[index].line_total = this.cart[index].quantity * this.cart[index].unit_price;
                        this.recalcCart();
                    } else {
                        this.removeFromCart(index);
                    }
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                    this.recalcCart();
                },

                recalcCart() {
                    this.cart.forEach(item => {
                        item.line_total = (item.quantity || 0) * (item.unit_price || 0);
                    });
                    // Auto-fill paidAmount with grand total if walk-in
                    if (this.selectedCustomer && this.selectedCustomer.name.includes('Walk-in')) {
                        this.paidAmount = this.cartGrandTotal;
                    }
                },

                get cartSubtotal() {
                    return this.cart.reduce((sum, item) => sum + (item.line_total || 0), 0);
                },

                get cartGrandTotal() {
                    return Math.max(0, this.cartSubtotal - (this.discountAmount || 0));
                },

                get dueAmount() {
                    return Math.max(0, this.cartGrandTotal - (this.paidAmount || 0));
                },

                async completeOfflineSale() {
                    if (this.cart.length === 0) return;

                    const dateStr = new Date().toISOString().split('T')[0];
                    const randSuffix = Math.floor(1000 + Math.random() * 9000);
                    const offlineInvoiceNo = 'OFF-' + dateStr.replace(/-/g, '') + '-' + randSuffix;

                    const salePayload = {
                        sale_data: {
                            customer_id: this.selectedCustomerId,
                            sale_date: dateStr,
                            discount_amount: this.discountAmount || 0,
                            paid_amount: this.paidAmount || 0,
                            payment_method: this.paymentMethod,
                            payment_notes: this.paymentNotes
                        },
                        items_data: this.cart.map(item => ({
                            product_id: item.product_id,
                            unit_type: item.unit_type,
                            quantity: item.quantity,
                            pack_size: item.pack_size,
                            unit_price: item.unit_price
                        })),
                        offline_invoice_no: offlineInvoiceNo
                    };

                    // Save to IndexedDB
                    await AquaFeedDB.addPending('sale', salePayload);

                    // Setup receipt data for printing
                    this.lastReceiptData = {
                        invoice_no: offlineInvoiceNo,
                        date: new Date().toISOString(),
                        customer_name: this.selectedCustomer?.name || 'Walk-in Cash Customer',
                        items: [...this.cart],
                        subtotal: this.cartSubtotal,
                        discount: this.discountAmount,
                        grand_total: this.cartGrandTotal,
                        paid_amount: this.paidAmount,
                        due_amount: this.dueAmount
                    };

                    this.showReceiptModal = true;

                    // Deduct local product stock in cache
                    this.cart.forEach(item => {
                        const p = this.products.find(prod => prod.id === item.product_id);
                        if (p && item.unit_type === 'bag') {
                            p.total_bags = Math.max(0, p.total_bags - item.quantity);
                        }
                    });
                    await AquaFeedDB.setCached('products', this.products);

                    // Reset cart
                    this.cart = [];
                    this.discountAmount = 0;
                    this.paidAmount = 0;
                    this.paymentNotes = '';
                    await this.loadPendingList();
                },

                onCollectionCustomerChange() {
                    this.selectedCollectionCustomer = this.customers.find(c => c.id == this.collectionCustomerId) || null;
                },

                async saveOfflineCollection() {
                    if (!this.collectionCustomerId || this.collectionAmount <= 0) return;

                    const payload = {
                        customer_id: this.collectionCustomerId,
                        amount_paid: this.collectionAmount,
                        payment_method: this.collectionMethod,
                        transaction_reference: this.collectionReference,
                        notes: this.collectionNotes
                    };

                    await AquaFeedDB.addPending('collection', payload);

                    AquaFeedDB.showNotification(`✓ Collection of ৳${this.formatNumber(this.collectionAmount)} saved offline!`, 'success');

                    this.collectionAmount = 0;
                    this.collectionReference = '';
                    this.collectionNotes = '';
                    await this.loadPendingList();
                },

                async saveOfflineExpense() {
                    if (!this.expenseTitle || this.expenseAmount <= 0) return;

                    const payload = {
                        category: this.expenseCategory,
                        title: this.expenseTitle,
                        amount: this.expenseAmount,
                        paid_to: this.expensePaidTo,
                        payment_method: this.expenseMethod,
                        expense_date: new Date().toISOString().split('T')[0]
                    };

                    await AquaFeedDB.addPending('expense', payload);

                    AquaFeedDB.showNotification(`✓ Expense of ৳${this.formatNumber(this.expenseAmount)} saved offline!`, 'success');

                    this.expenseTitle = '';
                    this.expenseAmount = 0;
                    this.expensePaidTo = '';
                    await this.loadPendingList();
                },

                async triggerManualSync() {
                    if (!this.isOnline) {
                        alert('Internet is not available. Transactions remain safely in IndexedDB.');
                        return;
                    }
                    await AquaFeedDB.syncAll();
                    await this.loadPendingList();
                },

                async deletePendingItem(offlineId) {
                    if (confirm('Are you sure you want to remove this offline transaction?')) {
                        await AquaFeedDB.removePending(offlineId);
                        await this.loadPendingList();
                    }
                },

                getItemTitle(item) {
                    if (item.type === 'sale') {
                        return (item.data.offline_invoice_no || 'Sale') + ' (' + (item.data.items_data?.length || 0) + ' items)';
                    }
                    if (item.type === 'collection') {
                        const cust = this.customers.find(c => c.id == item.data.customer_id);
                        return 'Due Collection: ' + (cust ? cust.name : 'Customer #' + item.data.customer_id);
                    }
                    if (item.type === 'expense') {
                        return (item.data.title || 'Expense') + ' [' + item.data.category + ']';
                    }
                    return 'Transaction';
                },

                getItemAmount(item) {
                    if (item.type === 'sale') {
                        const sub = (item.data.items_data || []).reduce((s, i) => s + (i.quantity * i.unit_price), 0);
                        return Math.max(0, sub - (item.data.sale_data?.discount_amount || 0));
                    }
                    if (item.type === 'collection') {
                        return item.data.amount_paid || 0;
                    }
                    if (item.type === 'expense') {
                        return item.data.amount || 0;
                    }
                    return 0;
                },

                formatNumber(val) {
                    const num = Number(val) || 0;
                    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                }
            };
        }
    </script>
</x-filament-panels::page>
