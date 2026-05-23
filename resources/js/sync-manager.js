export class SyncManager {
  constructor(db, detector) {
    this.db = db;
    this.detector = detector;
    this.isSyncing = false;
    this.pollingMs = 30000;
    this.poller = null;
  }

  async initialize() {
    document.addEventListener('went-online', () => this.syncPending());
    document.addEventListener('sync-now', () => this.syncPending());
    document.addEventListener('visibilitychange', () => this.handleVisibilityChange());
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data?.type === 'SYNC_COMPLETE') {
          document.dispatchEvent(new CustomEvent('sync-complete', { detail: event.data }));
          this.syncPending();
        }
      });
    }
    this.startPolling();
    return this;
  }

  handleVisibilityChange() {
    if (document.hidden) {
      this.stopPolling();
    } else {
      this.startPolling();
      this.syncPending();
    }
  }

  startPolling() {
    this.stopPolling();
    this.poller = setInterval(() => {
      if (navigator.onLine) {
        this.syncPending();
      }
    }, this.pollingMs);
  }

  stopPolling() {
    if (this.poller) {
      clearInterval(this.poller);
      this.poller = null;
    }
  }

  async syncPending() {
    if (this.isSyncing) {
      return;
    }
    if (!navigator.onLine) {
      return;
    }

    const pending = await this.db.getPendingQueue();
    if (!pending || pending.length === 0) {
      await this.db.setLastSyncTime();
      document.dispatchEvent(new CustomEvent('sync-complete', { detail: { success: true, synced: 0 } }));
      return;
    }

    this.isSyncing = true;
    document.dispatchEvent(new CustomEvent('sync-started', { detail: { count: pending.length } }));

    const payload = {
      operations: pending.map((item) => ({
        queue_id: item.queue_id,
        local_id: item.local_id,
        action: item.action,
        entity_type: item.entity_type,
        payload: item.payload,
      })),
    };

    try {
      const response = await fetch('/api/v1/sync/batch', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      });

      if (!response.ok) {
        throw new Error('Sync request failed');
      }

      const result = await response.json();
      await this.handleSyncResults(result.results || []);
      await this.db.setLastSyncTime();
      document.dispatchEvent(new CustomEvent('sync-complete', { detail: { success: true, synced: pending.length } }));
    } catch (error) {
      console.warn('Sync failed:', error);
      for (const item of pending) {
        const retryCount = item.retry_count + 1;
        const status = retryCount >= item.max_retries ? 'failed' : 'pending';
        await this.db.updateQueueItem(item.queue_id, {
          retry_count: retryCount,
          status,
          error_message: error.message,
        });
      }
      document.dispatchEvent(new CustomEvent('sync-complete', { detail: { success: false, error: error.message } }));
    } finally {
      this.isSyncing = false;
    }
  }

  async handleSyncResults(results) {
    let completedCount = 0;
    for (const record of results) {
      const queueId = record.queue_id;
      if (record.success) {
        await this.db.updateQueueItem(queueId, {
          status: 'completed',
          synced_at: new Date().toISOString(),
          error_message: null,
        });
        completedCount += 1;
        if (record.server_id && record.local_id) {
          await this.db.mapIds(record.local_id, record.server_id, record.entity_type || 'invoice');
          await this.db.updateAllReferences(record.local_id, record.server_id);
          const storeName = record.entity_type === 'customer' ? 'customers' : 'invoices';
          await this.db._withStore(storeName, 'readwrite', async (store) => {
            const entry = await this.db.request(store, 'get', record.local_id);
            if (entry) {
              entry.sync_status = 'synced';
              entry._local_only = false;
              entry.server_id = record.server_id;
              entry.invoice_no = record.server_invoice_no || entry.invoice_no;
              await this.db.request(store, 'put', entry);
            }
          });
        }
      } else {
        const queueItem = await this.db._withStore('sync_queue', 'readonly', async (store) => this.db.request(store, 'get', queueId));
        if (!queueItem) {
          continue;
        }
        const retryCount = queueItem.retry_count + 1;
        await this.db.updateQueueItem(queueId, {
          retry_count: retryCount,
          status: retryCount >= queueItem.max_retries ? 'failed' : 'pending',
          error_message: record.error || 'Sync failed',
        });
      }
    }
    return completedCount;
  }

  async registerBackgroundSync() {
    if ('serviceWorker' in navigator && 'SyncManager' in window) {
      try {
        const registration = await navigator.serviceWorker.ready;
        await registration.sync.register('gold-workshop-sync');
      } catch (error) {
        console.warn('Background sync registration failed', error);
      }
    }
  }
}
