const STORE_CUSTOMERS = 'customers';
const STORE_INVOICES = 'invoices';
const STORE_SYNC_QUEUE = 'sync_queue';
const STORE_APP_CACHE = 'app_cache';
const STORE_ID_MAPPING = 'id_mapping';

export class GoldWorkshopDB {
  constructor() {
    this.dbName = 'GoldWorkshopDB';
    this.dbVersion = 1;
    this.dbPromise = this.open();
  }

  open() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.dbName, this.dbVersion);

      request.onupgradeneeded = (event) => {
        const db = event.target.result;

        if (!db.objectStoreNames.contains(STORE_CUSTOMERS)) {
          const customerStore = db.createObjectStore(STORE_CUSTOMERS, { keyPath: 'id' });
          customerStore.createIndex('by_name', 'name', { unique: false });
          customerStore.createIndex('by_phone', 'phone', { unique: false });
          customerStore.createIndex('by_sync_status', 'sync_status', { unique: false });
        }

        if (!db.objectStoreNames.contains(STORE_INVOICES)) {
          const invoiceStore = db.createObjectStore(STORE_INVOICES, { keyPath: 'id' });
          invoiceStore.createIndex('by_customer', 'customer_id', { unique: false });
          invoiceStore.createIndex('by_date', 'invoice_date', { unique: false });
          invoiceStore.createIndex('by_sync_status', 'sync_status', { unique: false });
          invoiceStore.createIndex('by_book_no', 'manual_book_no', { unique: false });
        }

        if (!db.objectStoreNames.contains(STORE_SYNC_QUEUE)) {
          const queueStore = db.createObjectStore(STORE_SYNC_QUEUE, { keyPath: 'queue_id', autoIncrement: true });
          queueStore.createIndex('by_status', 'status', { unique: false });
          queueStore.createIndex('by_created', 'created_at', { unique: false });
        }

        if (!db.objectStoreNames.contains(STORE_APP_CACHE)) {
          db.createObjectStore(STORE_APP_CACHE, { keyPath: 'cache_key' });
        }

        if (!db.objectStoreNames.contains(STORE_ID_MAPPING)) {
          const mapStore = db.createObjectStore(STORE_ID_MAPPING, { keyPath: 'local_id' });
          mapStore.createIndex('by_server_id', 'server_id', { unique: false });
        }
      };

      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
      request.onblocked = () => console.warn('IndexedDB upgrade blocked');
    });
  }

  transaction(storeNames, mode = 'readonly') {
    return this.dbPromise.then((db) => db.transaction(storeNames, mode));
  }

  request(store, method, ...args) {
    return new Promise((resolve, reject) => {
      const request = store[method](...args);
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async _withStore(storeName, mode, callback) {
    const tx = await this.transaction([storeName], mode);
    const store = tx.objectStore(storeName);
    const result = await callback(store);
    return new Promise((resolve, reject) => {
      tx.oncomplete = () => resolve(result);
      tx.onerror = () => reject(tx.error);
      tx.onabort = () => reject(tx.error);
    });
  }

  generateLocalId(prefix) {
    return `${prefix}_${Date.now()}_${Math.floor(Math.random() * 10000)}`;
  }

  async saveCustomers(customers) {
    return this._withStore(STORE_CUSTOMERS, 'readwrite', async (store) => {
      for (const customer of customers) {
        await this.request(store, 'put', {
          ...customer,
          sync_status: 'synced',
          _local_only: false,
          local_created_at: customer.local_created_at || new Date().toISOString(),
          server_updated_at: customer.server_updated_at || new Date().toISOString(),
        });
      }
    });
  }

  async getCustomers(searchTerm = '') {
    return this._withStore(STORE_CUSTOMERS, 'readonly', async (store) => {
      const all = await this.request(store, 'getAll');
      if (!searchTerm) {
        return all;
      }
      const term = searchTerm.toLowerCase();
      return all.filter((customer) => {
        return (
          String(customer.name || '').toLowerCase().includes(term) ||
          String(customer.phone || '').toLowerCase().includes(term)
        );
      });
    });
  }

  async getCustomer(id) {
    return this._withStore(STORE_CUSTOMERS, 'readonly', async (store) => {
      return this.request(store, 'get', id);
    });
  }

  async saveCustomerLocally(customerData) {
    const id = customerData.id || this.generateLocalId('local_CUST');
    const record = {
      ...customerData,
      id,
      sync_status: 'pending_create',
      _local_only: true,
      local_created_at: new Date().toISOString(),
      server_updated_at: null,
    };
    await this._withStore(STORE_CUSTOMERS, 'readwrite', async (store) => {
      await this.request(store, 'put', record);
    });
    return record;
  }

  async updateCustomerLocally(id, data) {
    return this._withStore(STORE_CUSTOMERS, 'readwrite', async (store) => {
      const existing = await this.request(store, 'get', id);
      if (!existing) {
        throw new Error('Customer not found locally');
      }
      const updated = {
        ...existing,
        ...data,
        sync_status: existing._local_only ? 'pending_create' : 'pending_update',
        server_updated_at: new Date().toISOString(),
      };
      await this.request(store, 'put', updated);
      return updated;
    });
  }

  async deleteCustomerLocally(id) {
    return this._withStore(STORE_CUSTOMERS, 'readwrite', async (store) => {
      const existing = await this.request(store, 'get', id);
      if (!existing) {
        return null;
      }
      if (existing._local_only) {
        await this.request(store, 'delete', id);
        return null;
      }
      const deleted = { ...existing, sync_status: 'pending_delete' };
      await this.request(store, 'put', deleted);
      return deleted;
    });
  }

  async saveInvoices(invoices) {
    return this._withStore(STORE_INVOICES, 'readwrite', async (store) => {
      for (const invoice of invoices) {
        await this.request(store, 'put', {
          ...invoice,
          sync_status: 'synced',
          _local_only: false,
          local_created_at: invoice.local_created_at || new Date().toISOString(),
        });
      }
    });
  }

  async getInvoices(filters = {}) {
    return this._withStore(STORE_INVOICES, 'readonly', async (store) => {
      const all = await this.request(store, 'getAll');
      return all.filter((invoice) => {
        let matches = true;
        if (filters.customer_id) {
          matches = matches && String(invoice.customer_id) === String(filters.customer_id);
        }
        if (filters.manual_book_no) {
          matches = matches && String(invoice.manual_book_no || '').includes(filters.manual_book_no);
        }
        if (filters.from_date) {
          matches = matches && invoice.invoice_date >= filters.from_date;
        }
        if (filters.to_date) {
          matches = matches && invoice.invoice_date <= filters.to_date;
        }
        if (filters.search) {
          const term = String(filters.search).toLowerCase();
          matches = matches && (
            String(invoice.invoice_no || '').toLowerCase().includes(term) ||
            String(invoice.manual_book_no || '').toLowerCase().includes(term)
          );
        }
        return matches;
      });
    });
  }

  async getInvoice(id) {
    return this._withStore(STORE_INVOICES, 'readonly', async (store) => {
      return this.request(store, 'get', id);
    });
  }

  async saveInvoiceLocally(invoiceData) {
    const id = invoiceData.id || this.generateLocalId('local_INV');
    const invoiceNo = invoiceData.invoice_no || (await this.generateLocalInvoiceNo());
    const record = {
      ...invoiceData,
      id,
      invoice_no: invoiceNo,
      sync_status: 'pending_create',
      _local_only: true,
      local_created_at: new Date().toISOString(),
      _temp_id: id,
    };
    await this._withStore(STORE_INVOICES, 'readwrite', async (store) => {
      await this.request(store, 'put', record);
    });
    return record;
  }

  async updateInvoiceLocally(id, data) {
    return this._withStore(STORE_INVOICES, 'readwrite', async (store) => {
      const existing = await this.request(store, 'get', id);
      if (!existing) {
        throw new Error('Invoice not found locally');
      }
      const updated = {
        ...existing,
        ...data,
        sync_status: existing._local_only ? 'pending_create' : 'pending_update',
        local_created_at: existing.local_created_at || new Date().toISOString(),
      };
      await this.request(store, 'put', updated);
      return updated;
    });
  }

  async deleteInvoiceLocally(id) {
    return this._withStore(STORE_INVOICES, 'readwrite', async (store) => {
      const existing = await this.request(store, 'get', id);
      if (!existing) {
        return null;
      }
      if (existing._local_only) {
        await this.request(store, 'delete', id);
        return null;
      }
      const deleted = { ...existing, sync_status: 'pending_delete' };
      await this.request(store, 'put', deleted);
      return deleted;
    });
  }

  async generateLocalInvoiceNo() {
    const invoices = await this.getInvoices();
    const localInvoices = invoices.filter((invoice) => String(invoice.invoice_no || '').startsWith('LOCAL-'));
    const nextNumber = localInvoices.length + 1;
    return `LOCAL-${String(nextNumber).padStart(4, '0')}`;
  }

  async addToQueue(action, entityType, localId, payload) {
    return this._withStore(STORE_SYNC_QUEUE, 'readwrite', async (store) => {
      const item = {
        action,
        entity_type: entityType,
        local_id: localId,
        server_id: payload.server_id || null,
        payload,
        status: 'pending',
        retry_count: 0,
        max_retries: 5,
        error_message: null,
        created_at: new Date().toISOString(),
        synced_at: null,
      };
      await this.request(store, 'add', item);
      return item;
    });
  }

  async getPendingQueue() {
    return this._withStore(STORE_SYNC_QUEUE, 'readonly', async (store) => {
      const index = store.index('by_status');
      return this.request(index, 'getAll', IDBKeyRange.only('pending'));
    });
  }

  async updateQueueItem(queueId, updates) {
    return this._withStore(STORE_SYNC_QUEUE, 'readwrite', async (store) => {
      const item = await this.request(store, 'get', queueId);
      if (!item) {
        throw new Error('Queue item not found');
      }
      const updated = { ...item, ...updates };
      await this.request(store, 'put', updated);
      return updated;
    });
  }

  async getFailedItems() {
    return this._withStore(STORE_SYNC_QUEUE, 'readonly', async (store) => {
      const index = store.index('by_status');
      return this.request(index, 'getAll', IDBKeyRange.only('failed'));
    });
  }

  async clearCompleted() {
    return this._withStore(STORE_SYNC_QUEUE, 'readwrite', async (store) => {
      const request = store.openCursor();
      return new Promise((resolve, reject) => {
        request.onsuccess = (event) => {
          const cursor = event.target.result;
          if (!cursor) {
            resolve();
            return;
          }
          const item = cursor.value;
          if (item.status === 'completed' && new Date(item.synced_at) < new Date(Date.now() - 24 * 60 * 60 * 1000)) {
            cursor.delete();
          }
          cursor.continue();
        };
        request.onerror = () => reject(request.error);
      });
    });
  }

  async setCache(key, data, ttlMinutes = 5) {
    const record = {
      cache_key: key,
      data,
      cached_at: new Date().toISOString(),
      expires_at: ttlMinutes ? new Date(Date.now() + ttlMinutes * 60 * 1000).toISOString() : null,
    };
    return this._withStore(STORE_APP_CACHE, 'readwrite', async (store) => {
      await this.request(store, 'put', record);
      return record;
    });
  }

  async getCache(key) {
    return this._withStore(STORE_APP_CACHE, 'readonly', async (store) => {
      const record = await this.request(store, 'get', key);
      if (!record) {
        return null;
      }
      if (record.expires_at && new Date(record.expires_at) <= new Date()) {
        return null;
      }
      return record.data;
    });
  }

  async clearCache(key = null) {
    if (key) {
      return this._withStore(STORE_APP_CACHE, 'readwrite', async (store) => {
        await this.request(store, 'delete', key);
      });
    }
    return this._withStore(STORE_APP_CACHE, 'readwrite', async (store) => {
      await this.request(store, 'clear');
    });
  }

  async mapIds(localId, serverId, entityType) {
    return this._withStore(STORE_ID_MAPPING, 'readwrite', async (store) => {
      await this.request(store, 'put', {
        local_id: localId,
        server_id: serverId,
        entity_type: entityType,
        mapped_at: new Date().toISOString(),
      });
    });
  }

  async getServerId(localId) {
    return this._withStore(STORE_ID_MAPPING, 'readonly', async (store) => {
      const record = await this.request(store, 'get', localId);
      return record?.server_id || null;
    });
  }

  async getLocalId(serverId, entityType) {
    return this._withStore(STORE_ID_MAPPING, 'readonly', async (store) => {
      const index = store.index('by_server_id');
      const request = index.getAll(IDBKeyRange.only(serverId));
      return new Promise((resolve, reject) => {
        request.onsuccess = () => {
          const results = request.result.filter((entry) => entry.entity_type === entityType);
          resolve(results.length ? results[0]?.local_id : null);
        };
        request.onerror = () => reject(request.error);
      });
    });
  }

  async updateAllReferences(localId, serverId) {
    await this._withStore(STORE_INVOICES, 'readwrite', async (store) => {
      const request = store.openCursor();
      return new Promise((resolve, reject) => {
        request.onsuccess = async (event) => {
          const cursor = event.target.result;
          if (!cursor) {
            resolve();
            return;
          }
          const record = cursor.value;
          let updated = false;
          if (String(record.customer_id) === String(localId)) {
            record.customer_id = serverId;
            updated = true;
          }
          if (String(record.id) === String(localId)) {
            record.server_id = serverId;
            updated = true;
          }
          if (updated) {
            await this.request(cursor, 'update', record);
          }
          cursor.continue();
        };
        request.onerror = () => reject(request.error);
      });
    });
  }

  async getPendingCount() {
    return this._withStore(STORE_SYNC_QUEUE, 'readonly', async (store) => {
      const index = store.index('by_status');
      const pending = await this.request(index, 'count', IDBKeyRange.only('pending'));
      const processing = await this.request(index, 'count', IDBKeyRange.only('processing'));
      return pending + processing;
    });
  }

  async getLastSyncTime() {
    return this._withStore(STORE_APP_CACHE, 'readonly', async (store) => {
      const record = await this.request(store, 'get', 'last_sync');
      return record?.data || null;
    });
  }

  async setLastSyncTime(timestamp = null) {
    const data = timestamp || new Date().toISOString();
    return this.setCache('last_sync', data, 60 * 24 * 30);
  }

  async exportAllData() {
    const customers = await this._withStore(STORE_CUSTOMERS, 'readonly', async (store) => this.request(store, 'getAll'));
    const invoices = await this._withStore(STORE_INVOICES, 'readonly', async (store) => this.request(store, 'getAll'));
    const syncQueue = await this._withStore(STORE_SYNC_QUEUE, 'readonly', async (store) => this.request(store, 'getAll'));
    const appCache = await this._withStore(STORE_APP_CACHE, 'readonly', async (store) => this.request(store, 'getAll'));
    const idMapping = await this._withStore(STORE_ID_MAPPING, 'readonly', async (store) => this.request(store, 'getAll'));
    return { customers, invoices, syncQueue, appCache, idMapping };
  }

  async importData(jsonData) {
    if (!jsonData) {
      throw new Error('Invalid backup data');
    }
    if (jsonData.customers) {
      await this.saveCustomers(jsonData.customers);
    }
    if (jsonData.invoices) {
      await this.saveInvoices(jsonData.invoices);
    }
    if (jsonData.syncQueue) {
      await this._withStore(STORE_SYNC_QUEUE, 'readwrite', async (store) => {
        for (const item of jsonData.syncQueue) {
          await this.request(store, 'put', item);
        }
      });
    }
    if (jsonData.appCache) {
      await this._withStore(STORE_APP_CACHE, 'readwrite', async (store) => {
        for (const item of jsonData.appCache) {
          await this.request(store, 'put', item);
        }
      });
    }
    if (jsonData.idMapping) {
      await this._withStore(STORE_ID_MAPPING, 'readwrite', async (store) => {
        for (const item of jsonData.idMapping) {
          await this.request(store, 'put', item);
        }
      });
    }
  }
}
