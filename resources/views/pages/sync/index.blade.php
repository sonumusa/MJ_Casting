@extends('layouts.app')

@section('title', 'Sync Status')

@section('extra_css')
<style>
    .sync-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 32px;
    }
    .sync-stat-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 24px;
        text-align: center;
    }
    .sync-stat-label { font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
    .sync-stat-value { font-family: 'JetBrains Mono', monospace; font-size: 1.75rem; font-weight: 700; color: var(--text-primary); }
    
    .status-online { color: var(--success); }
    .status-offline { color: var(--error); }

    .queue-table-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 32px;
    }
    .queue-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .badge-pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); border: 1px solid var(--warning); }
    .badge-failed { background: rgba(244, 63, 94, 0.1); color: var(--error); border: 1px solid var(--error); }
    .badge-completed { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid var(--success); }
    .badge-processing { background: rgba(56, 189, 248, 0.1); color: var(--info); border: 1px solid var(--info); }

    .error-text { color: var(--error); font-size: 0.75rem; display: block; margin-top: 4px; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .collapsible-section {
        border: 1px solid var(--border-color);
        border-radius: 12px;
        margin-bottom: 20px;
        overflow: hidden;
    }
    .collapsible-trigger {
        padding: 16px 24px;
        background-color: var(--bg-card);
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        user-select: none;
    }
    .collapsible-content {
        padding: 0 24px;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out, padding 0.3s ease;
        background-color: rgba(255,255,255,0.02);
    }
    .collapsible-section.open .collapsible-content {
        max-height: 1000px;
        padding: 24px;
    }
    .collapsible-section.open .bi-chevron-down { transform: rotate(180deg); }

    .btn-action {
        padding: 4px 8px;
        font-size: 0.7rem;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-retry { color: var(--info); border: 1px solid var(--info); background: transparent; }
    .btn-discard { color: var(--error); border: 1px solid var(--error); background: transparent; }
    .btn-retry:hover { background: var(--info); color: white; }
    .btn-discard:hover { background: var(--error); color: white; }
</style>
@endsection

@section('content')
    <div class="page-header" style="margin-bottom: 24px;">
        <div class="page-title-group">
            <h1>Sync Status <span class="font-urdu">ہم آہنگی کی صورتحال</span></h1>
            <p style="color: var(--text-muted);">Monitor offline data and synchronization queue</p>
        </div>
        <div class="header-actions">
            <button id="clear-completed-btn" class="btn-outline" style="margin-right: 12px;">
                <i class="bi bi-trash"></i> Clear Completed
            </button>
            <button id="sync-now-btn" class="btn-gold">
                <i class="bi bi-arrow-repeat"></i> Sync Now
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="sync-stat-grid">
        <div class="sync-stat-card">
            <div class="sync-stat-label">Connection</div>
            <div id="connection-status" class="sync-stat-value status-online">
                <i class="bi bi-circle-fill" style="font-size: 0.75rem; vertical-align: middle; margin-right: 8px;"></i>
                <span>Online</span>
            </div>
        </div>
        <div class="sync-stat-card">
            <div class="sync-stat-label">Pending Changes</div>
            <div id="pending-count" class="sync-stat-value">0</div>
        </div>
        <div class="sync-stat-card">
            <div class="sync-stat-label">Failed Items</div>
            <div id="failed-count" class="sync-stat-value" style="color: var(--error);">0</div>
        </div>
        <div class="sync-stat-card">
            <div class="sync-stat-label">Total Synced</div>
            <div id="synced-count" class="sync-stat-value" style="color: var(--success);">0</div>
        </div>
    </div>

    <!-- Pending Queue Table -->
    <div class="queue-table-container">
        <div class="queue-header">
            <h3 style="font-family: 'Playfair Display', serif; margin: 0;">Pending Queue</h3>
            <span id="queue-status-hint" style="font-size: 0.75rem; color: var(--text-muted);">Loading queue data...</span>
        </div>
        <div style="overflow-x: auto;">
            <table class="table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Local ID</th>
                        <th>Status</th>
                        <th>Retries</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="pending-queue-body">
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            No pending changes in queue.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Failed Items Section -->
    <div id="failed-items-section" style="display: none;">
        <h3 style="font-family: 'Playfair Display', serif; color: var(--error); margin-bottom: 16px;">Failed Operations</h3>
        <div class="queue-table-container" style="border-color: var(--error);">
            <div style="overflow-x: auto;">
                <table class="table" style="margin-bottom: 0;">
                    <tbody id="failed-queue-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Completed Items (Collapsible) -->
    <div class="collapsible-section" id="completed-section">
        <div class="collapsible-trigger" onclick="toggleCollapsible('completed-section')">
            <h3 style="font-family: 'Playfair Display', serif; margin: 0; font-size: 1.1rem;">Recently Completed</h3>
            <div style="display: flex; align-items: center; gap: 12px;">
                <span id="completed-badge" class="badge-completed" style="padding: 2px 8px; border-radius: 12px; font-size: 0.7rem;">0 items</span>
                <i class="bi bi-chevron-down"></i>
            </div>
        </div>
        <div class="collapsible-content">
            <div style="overflow-x: auto;">
                <table class="table" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Local ID</th>
                            <th>Server ID</th>
                            <th>Synced At</th>
                        </tr>
                    </thead>
                    <tbody id="completed-queue-body">
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: var(--text-muted);">
                                No recently synced items.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Backup & Maintenance -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 40px;">
        <div class="card">
            <h3 style="font-family: 'Playfair Display', serif; margin-bottom: 16px; font-size: 1.1rem;">Data Maintenance</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">
                Manage your local data cache and synchronization settings.
            </p>
            <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                <button class="btn-outline" onclick="exportData()">
                    <i class="bi bi-download"></i> Export Local Data
                </button>
                <button class="btn-outline" onclick="clearLocalCache()" style="color: var(--error); border-color: rgba(244, 63, 94, 0.3);">
                    <i class="bi bi-trash"></i> Clear Local Cache
                </button>
            </div>
        </div>
        <div class="card">
            <h3 style="font-family: 'Playfair Display', serif; margin-bottom: 16px; font-size: 1.1rem;">Sync Information</h3>
            <div style="font-size: 0.85rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: var(--text-secondary);">Last Sync:</span>
                    <span id="last-sync-time" class="font-mono">Never</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: var(--text-secondary);">Storage Used:</span>
                    <span id="storage-usage" class="font-mono">Calculating...</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Sync Protocol:</span>
                    <span class="font-mono">JSON/REST v1</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('extra_js')
<script>
    function toggleCollapsible(id) {
        document.getElementById(id).classList.toggle('open');
    }

    document.addEventListener('DOMContentLoaded', async () => {
        const db = window.GoldWorkshopDB;
        if (!db) return;

        async function updateUI() {
            // Update Connection Status
            const connStatus = document.getElementById('connection-status');
            if (navigator.onLine) {
                connStatus.className = 'sync-stat-value status-online';
                connStatus.querySelector('span').innerText = 'Online';
            } else {
                connStatus.className = 'sync-stat-value status-offline';
                connStatus.querySelector('span').innerText = 'Offline';
            }

            // Get Queue Data
            const allItems = await db._withStore('sync_queue', 'readonly', async (store) => {
                const request = store.getAll();
                return new Promise(resolve => request.onsuccess = () => resolve(request.result));
            });

            const pending = allItems.filter(i => i.status === 'pending' || i.status === 'processing');
            const failed = allItems.filter(i => i.status === 'failed');
            const completed = allItems.filter(i => i.status === 'completed').sort((a,b) => new Date(b.synced_at) - new Date(a.synced_at));

            // Update Counts
            document.getElementById('pending-count').innerText = pending.length;
            document.getElementById('failed-count').innerText = failed.length;
            document.getElementById('synced-count').innerText = completed.length;
            document.getElementById('completed-badge').innerText = `${completed.length} items`;

            // Update Pending Table
            const pendingBody = document.getElementById('pending-queue-body');
            if (pending.length === 0) {
                pendingBody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">No pending changes in queue.</td></tr>';
            } else {
                pendingBody.innerHTML = pending.map(item => `
                    <tr>
                        <td class="font-mono" style="font-size: 0.75rem;">#${item.queue_id}</td>
                        <td style="text-transform: capitalize;">${item.action.replace('_', ' ')}</td>
                        <td style="text-transform: capitalize;">${item.entity_type}</td>
                        <td class="font-mono" style="font-size: 0.75rem;">${item.local_id}</td>
                        <td><span class="status-badge badge-${item.status}">${item.status}</span></td>
                        <td class="text-center">${item.retry_count} / ${item.max_retries}</td>
                        <td style="font-size: 0.75rem; color: var(--text-secondary);">${new Date(item.created_at).toLocaleString()}</td>
                        <td>
                            <button class="btn-action btn-discard" onclick="discardItem(${item.queue_id})">Discard</button>
                        </td>
                    </tr>
                `).join('');
            }

            // Update Failed Table
            const failedSection = document.getElementById('failed-items-section');
            const failedBody = document.getElementById('failed-queue-body');
            if (failed.length > 0) {
                failedSection.style.display = 'block';
                failedBody.innerHTML = failed.map(item => `
                    <tr>
                        <td style="width: 80px;" class="font-mono">#${item.queue_id}</td>
                        <td style="width: 150px; text-transform: capitalize;">${item.action.replace('_', ' ')}</td>
                        <td style="width: 150px;">
                            <strong>${item.entity_type}</strong>
                            <span class="error-text" title="${item.error_message || 'Unknown error'}">${item.error_message || 'Unknown error'}</span>
                        </td>
                        <td class="font-mono" style="font-size: 0.75rem;">${item.local_id}</td>
                        <td><span class="status-badge badge-failed">Failed</span></td>
                        <td>${item.retry_count} retries</td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                <button class="btn-action btn-retry" onclick="retryItem(${item.queue_id})">Retry</button>
                                <button class="btn-action btn-discard" onclick="discardItem(${item.queue_id})">Discard</button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            } else {
                failedSection.style.display = 'none';
            }

            // Update Completed Table
            const completedBody = document.getElementById('completed-queue-body');
            const recentCompleted = completed.slice(0, 20);
            if (recentCompleted.length === 0) {
                completedBody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--text-muted);">No recently synced items.</td></tr>';
            } else {
                completedBody.innerHTML = recentCompleted.map(item => `
                    <tr>
                        <td style="text-transform: capitalize;">${item.action.replace('_', ' ')}</td>
                        <td style="text-transform: capitalize;">${item.entity_type}</td>
                        <td class="font-mono" style="font-size: 0.75rem;">${item.local_id}</td>
                        <td class="font-mono" style="font-size: 0.75rem; color: var(--gold-primary);">${item.server_id || 'N/A'}</td>
                        <td style="font-size: 0.75rem; color: var(--text-secondary);">${new Date(item.synced_at).toLocaleString()}</td>
                    </tr>
                `).join('');
            }

            // Update Metadata
            const lastSync = localStorage.getItem('last_sync_time');
            document.getElementById('last-sync-time').innerText = lastSync ? new Date(lastSync).toLocaleString() : 'Never';
            
            if (navigator.storage && navigator.storage.estimate) {
                const estimate = await navigator.storage.estimate();
                const used = (estimate.usage / 1024 / 1024).toFixed(2);
                document.getElementById('storage-usage').innerText = `${used} MB`;
            }
        }

        // Global functions for buttons
        window.retryItem = async (id) => {
            await db.updateQueueItem(id, { status: 'pending', retry_count: 0, error_message: null });
            updateUI();
            if (navigator.onLine) document.dispatchEvent(new CustomEvent('sync-now'));
        };

        window.discardItem = async (id) => {
            if (confirm('Are you sure you want to discard this operation? It will NOT be sent to the server.')) {
                await db._withStore('sync_queue', 'readwrite', async (store) => {
                    await db.request(store, 'delete', id);
                });
                updateUI();
            }
        };

        window.exportData = async () => {
            const data = {};
            const stores = ['customers', 'invoices', 'sync_queue', 'id_mapping'];
            for (const storeName of stores) {
                data[storeName] = await db._withStore(storeName, 'readonly', async (store) => {
                    const req = store.getAll();
                    return new Promise(r => req.onsuccess = () => r(req.result));
                });
            }
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `gold_workshop_backup_${new Date().toISOString().slice(0,10)}.json`;
            a.click();
        };

        window.clearLocalCache = async () => {
            if (confirm('DANGER: This will clear ALL local data that has been successfully synced. Pending changes will be kept. Proceed?')) {
                const syncedItems = await db._withStore('sync_queue', 'readonly', async (store) => {
                    const req = store.getAll();
                    return new Promise(r => req.onsuccess = () => r(req.result.filter(i => i.status === 'completed')));
                });

                await db._withStore('sync_queue', 'readwrite', async (store) => {
                    for (const item of syncedItems) {
                        await db.request(store, 'delete', item.queue_id);
                    }
                });
                
                alert('Synced items cleared from local storage.');
                updateUI();
            }
        };

        // Event Listeners
        document.getElementById('sync-now-btn').addEventListener('click', () => {
            if (!navigator.onLine) {
                alert('You are currently offline. Please connect to the internet to sync.');
                return;
            }
            document.dispatchEvent(new CustomEvent('sync-now'));
        });

        document.getElementById('clear-completed-btn').addEventListener('click', window.clearLocalCache);

        document.addEventListener('sync-started', () => {
            document.getElementById('queue-status-hint').innerText = 'Syncing...';
            document.getElementById('sync-now-btn').disabled = true;
        });

        document.addEventListener('sync-complete', () => {
            document.getElementById('queue-status-hint').innerText = 'Sync complete.';
            document.getElementById('sync-now-btn').disabled = false;
            updateUI();
        });

        window.addEventListener('online', updateUI);
        window.addEventListener('offline', updateUI);

        // Initial Load
        updateUI();
        
        // Auto-sync if online and pending
        if (navigator.onLine) {
            const pendingCount = parseInt(document.getElementById('pending-count').innerText);
            if (pendingCount > 0) {
                document.dispatchEvent(new CustomEvent('sync-now'));
            }
        }
    });
</script>
@endsection
