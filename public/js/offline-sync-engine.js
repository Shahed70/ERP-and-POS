/**
 * AquaFeed ERP - Offline IndexedDB & Auto-Synchronization Engine
 */
(function (window) {
    'use strict';

    const DB_NAME = 'AquaFeedOfflineDB';
    const DB_VERSION = 1;
    const STORE_CACHE = 'cache';
    const STORE_PENDING = 'pending_sync';

    let dbInstance = null;

    const AquaFeedDB = {
        isOnline: navigator.onLine,
        isSyncing: false,
        pendingCount: 0,
        syncListeners: [],

        /**
         * Initialize IndexedDB database and object stores.
         */
        async init() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(DB_NAME, DB_VERSION);

                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    if (!db.objectStoreNames.contains(STORE_CACHE)) {
                        db.createObjectStore(STORE_CACHE, { keyPath: 'key' });
                    }
                    if (!db.objectStoreNames.contains(STORE_PENDING)) {
                        const pendingStore = db.createObjectStore(STORE_PENDING, { keyPath: 'offline_id' });
                        pendingStore.createIndex('type', 'type', { unique: false });
                        pendingStore.createIndex('created_at', 'created_at', { unique: false });
                    }
                };

                request.onsuccess = (event) => {
                    dbInstance = event.target.result;
                    this.updatePendingCount();
                    this.setupNetworkWatchers();
                    this.registerServiceWorker();

                    // If online, refresh cached catalog
                    if (navigator.onLine) {
                        this.refreshCache().catch(() => {});
                    }

                    resolve(dbInstance);
                };

                request.onerror = (event) => {
                    console.error('IndexedDB open error:', event.target.error);
                    reject(event.target.error);
                };
            });
        },

        registerServiceWorker() {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch((err) => {
                    console.log('SW registration optional:', err);
                });
            }
        },

        setupNetworkWatchers() {
            window.addEventListener('online', () => {
                this.isOnline = true;
                this.notifyStatusChange();
                this.syncAll();
            });

            window.addEventListener('offline', () => {
                this.isOnline = false;
                this.notifyStatusChange();
            });

            // Periodic heartbeat every 20 seconds
            setInterval(() => {
                this.checkPing();
            }, 20000);
        },

        async checkPing() {
            try {
                const res = await fetch('/admin/offline-sync/ping', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                });
                const wasOffline = !this.isOnline;
                this.isOnline = res.ok;
                this.notifyStatusChange();

                if (wasOffline && this.isOnline && this.pendingCount > 0) {
                    this.syncAll();
                }
            } catch (e) {
                this.isOnline = false;
                this.notifyStatusChange();
            }
        },

        notifyStatusChange() {
            const detail = {
                isOnline: this.isOnline,
                isSyncing: this.isSyncing,
                pendingCount: this.pendingCount
            };
            window.dispatchEvent(new CustomEvent('aquafeed:status-changed', { detail }));
            this.syncListeners.forEach(fn => fn(detail));
        },

        onStatusChange(fn) {
            this.syncListeners.push(fn);
        },

        /**
         * Cache management: get & set
         */
        async setCached(key, value) {
            if (!dbInstance) await this.init();
            return new Promise((resolve, reject) => {
                const tx = dbInstance.transaction(STORE_CACHE, 'readwrite');
                const store = tx.objectStore(STORE_CACHE);
                const req = store.put({ key, value, updated_at: new Date().toISOString() });
                req.onsuccess = () => resolve(true);
                req.onerror = (e) => reject(e.target.error);
            });
        },

        async getCached(key) {
            if (!dbInstance) await this.init();
            return new Promise((resolve, reject) => {
                const tx = dbInstance.transaction(STORE_CACHE, 'readonly');
                const store = tx.objectStore(STORE_CACHE);
                const req = store.get(key);
                req.onsuccess = (e) => resolve(e.target.result ? e.target.result.value : null);
                req.onerror = (e) => reject(e.target.error);
            });
        },

        /**
         * Fetch and update cache from server
         */
        async refreshCache() {
            try {
                const res = await fetch('/admin/offline-sync/data', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return null;
                const data = await res.json();
                if (data.status === 'success') {
                    await this.setCached('products', data.products);
                    await this.setCached('customers', data.customers);
                    await this.setCached('shop', data.shop);
                    await this.setCached('expense_categories', data.expense_categories);
                    await this.setCached('payment_methods', data.payment_methods);
                    await this.setCached('last_sync', data.timestamp);
                    return data;
                }
            } catch (e) {
                console.log('Could not refresh cache (offline):', e);
            }
            return null;
        },

        /**
         * Pending Sync Queue operations
         */
        async addPending(type, data) {
            if (!dbInstance) await this.init();
            const offlineId = 'off_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
            const record = {
                offline_id: offlineId,
                type: type, // 'sale', 'collection', 'expense'
                data: data,
                created_at: new Date().toISOString(),
                status: 'pending'
            };

            return new Promise((resolve, reject) => {
                const tx = dbInstance.transaction(STORE_PENDING, 'readwrite');
                const store = tx.objectStore(STORE_PENDING);
                const req = store.add(record);
                req.onsuccess = () => {
                    this.updatePendingCount();
                    // Attempt immediate sync if online
                    if (this.isOnline) {
                        setTimeout(() => this.syncAll(), 300);
                    }
                    resolve(record);
                };
                req.onerror = (e) => reject(e.target.error);
            });
        },

        async getPending() {
            if (!dbInstance) await this.init();
            return new Promise((resolve, reject) => {
                const tx = dbInstance.transaction(STORE_PENDING, 'readonly');
                const store = tx.objectStore(STORE_PENDING);
                const req = store.getAll();
                req.onsuccess = (e) => resolve(e.target.result || []);
                req.onerror = (e) => reject(e.target.error);
            });
        },

        async removePending(offlineId) {
            if (!dbInstance) await this.init();
            return new Promise((resolve, reject) => {
                const tx = dbInstance.transaction(STORE_PENDING, 'readwrite');
                const store = tx.objectStore(STORE_PENDING);
                const req = store.delete(offlineId);
                req.onsuccess = () => {
                    this.updatePendingCount();
                    resolve(true);
                };
                req.onerror = (e) => reject(e.target.error);
            });
        },

        async updatePendingCount() {
            const list = await this.getPending();
            this.pendingCount = list.length;
            this.notifyStatusChange();
            return this.pendingCount;
        },

        /**
         * Synchronize all queued transactions with the server.
         */
        async syncAll() {
            if (this.isSyncing) return;
            const pendingList = await this.getPending();
            if (pendingList.length === 0) return;

            this.isSyncing = true;
            this.notifyStatusChange();

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch('/admin/offline-sync/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({ transactions: pendingList })
                });

                if (!response.ok) {
                    throw new Error('Server returned HTTP ' + response.status);
                }

                const result = await response.json();

                if (result.status === 'success') {
                    // Remove successfully synced records
                    const syncedIds = (result.synced || []).map(item => item.offline_id);
                    for (const id of syncedIds) {
                        await this.removePending(id);
                    }

                    // Refresh product & customer caches
                    await this.refreshCache();

                    this.isSyncing = false;
                    await this.updatePendingCount();

                    // Notify UI & Play notification sound if available
                    window.dispatchEvent(new CustomEvent('aquafeed:sync-complete', {
                        detail: {
                            syncedCount: result.synced_count,
                            synced: result.synced,
                            errors: result.errors
                        }
                    }));

                    if (result.synced_count > 0) {
                        this.showNotification(`✓ ${result.synced_count} offline transaction(s) synced to database!`, 'success');
                    }

                    if (result.errors && result.errors.length > 0) {
                        console.warn('Some offline items encountered sync errors:', result.errors);
                        this.showNotification(`⚠️ ${result.errors.length} transaction(s) had sync issues. Check pending queue.`, 'warning');
                    }
                }
            } catch (err) {
                console.warn('Auto-sync failed, will retry when connection stable:', err);
                this.isSyncing = false;
                this.notifyStatusChange();
            }
        },

        showNotification(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = 'aquafeed-sync-toast ' + type;
            toast.innerHTML = `
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span>${type === 'success' ? '✅' : (type === 'warning' ? '⚠️' : 'ℹ️')}</span>
                    <span style="font-weight: 700; font-size: 13px;">${message}</span>
                </div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('show');
            }, 50);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 4500);
        }
    };

    // Auto-init on page load
    window.addEventListener('DOMContentLoaded', () => {
        AquaFeedDB.init().catch(err => console.error('AquaFeedDB init failed:', err));
    });

    window.AquaFeedDB = AquaFeedDB;

})(window);
