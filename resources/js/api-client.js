import { GoldCalc } from './calculations';

export class ApiClient {
  constructor(db) {
    this.db = db;
    this.baseUrl = '/api/v1';
  }

  async request(url, options = {}, fallbackStoreFn = null) {
    try {
      const controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), 5000);
      const response = await fetch(url, {
        credentials: 'include',
        cache: 'no-cache',
        signal: controller.signal,
        ...options,
      });
      clearTimeout(timeout);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      const data = await response.json();
      return { success: true, offline: false, data };
    } catch (error) {
      if (options.method && ['POST', 'PUT', 'DELETE'].includes(options.method.toUpperCase())) {
        return { success: false, offline: true, message: 'Offline mode: queued for sync.' };
      }
      if (fallbackStoreFn) {
        const cached = await fallbackStoreFn();
        if (cached) {
          return { success: true, offline: true, data: cached, message: 'Loaded from local cache.' };
        }
      }
      return { success: false, offline: true, message: 'Offline mode: no cached data available.' };
    }
  }

  async getInvoices(filters = {}) {
    const query = new URLSearchParams(filters).toString();
    const url = `${this.baseUrl}/invoices${query ? `?${query}` : ''}`;
    const result = await this.request(url, { method: 'GET' }, async () => {
      return this.db.getInvoices(filters);
    });
    if (!result.offline && result.data?.data) {
      await this.db.saveInvoices(result.data.data);
    }
    return result;
  }

  async getCustomers(search = '') {
    const url = `${this.baseUrl}/customers${search ? `?search=${encodeURIComponent(search)}` : ''}`;
    const result = await this.request(url, { method: 'GET' }, async () => {
      return this.db.getCustomers(search);
    });
    if (!result.offline && result.data?.data) {
      await this.db.saveCustomers(result.data.data);
    }
    return result;
  }

  async createInvoice(payload) {
    if (navigator.onLine) {
      const response = await this.request(`${this.baseUrl}/invoices`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      if (!response.offline && response.data?.data) {
        await this.db.saveInvoices([response.data.data]);
      }
      return response;
    }

    const totalWeight = GoldCalc.totalWeight(payload.casting_weight, payload.waste_weight);
    const maleWaste = GoldCalc.maleWaste(totalWeight, payload.ratti, payload.ratti_rate);
    const goldKhalis = GoldCalc.goldKhalis(totalWeight, maleWaste);
    const effectiveGold = GoldCalc.effectiveGold(goldKhalis, payload.rp_mazdori_weight, payload.casting_mazdori_weight);
    const grandTotal = GoldCalc.grandTotal(effectiveGold, payload.rp_rate);

    const localRecord = await this.db.saveInvoiceLocally({
      ...payload,
      status: 'active',
      invoice_no: await this.db.generateLocalInvoiceNo(),
      total_weight: totalWeight,
      male_waste: maleWaste,
      gold_khalis: goldKhalis,
      rp_amount: GoldCalc.rpAmount(goldKhalis, payload.rp_rate),
      rp_mazdori_amount: GoldCalc.mazdoriAmount(payload.rp_mazdori_weight, payload.rp_mazdori_rate),
      casting_mazdori_amount: GoldCalc.mazdoriAmount(payload.casting_mazdori_weight, payload.casting_mazdori_rate),
      effective_gold: effectiveGold,
      grand_total: grandTotal,
      remaining_balance: GoldCalc.remainingBalance(payload.previous_balance || 0, grandTotal, payload.wasooli || 0),
      _local_only: true,
    });

    await this.db.addToQueue('create_invoice', 'invoice', localRecord.id, localRecord);
    document.dispatchEvent(new Event('sync-now'));

    return {
      success: true,
      offline: true,
      data: localRecord,
      message: 'Saved offline. Will sync when internet returns.',
      pending_sync: true,
    };
  }

  async updateInvoice(id, payload) {
    const serverId = await this.db.getServerId(id);
    if (navigator.onLine && serverId) {
      const response = await this.request(`${this.baseUrl}/invoices/${serverId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      if (!response.offline && response.data?.data) {
        await this.db.updateInvoiceLocally(id, { ...payload, sync_status: 'synced' });
      }
      return response;
    }

    const record = await this.db.updateInvoiceLocally(id, payload);
    await this.db.addToQueue('update_invoice', 'invoice', record.id, { ...record, server_id: serverId });
    document.dispatchEvent(new Event('sync-now'));
    return { success: true, offline: true, data: record, pending_sync: true };
  }

  async deleteInvoice(id) {
    const serverId = await this.db.getServerId(id);
    if (navigator.onLine && serverId) {
      const response = await this.request(`${this.baseUrl}/invoices/${serverId}`, { method: 'DELETE' });
      if (!response.offline) {
        await this.db.deleteInvoiceLocally(id);
      }
      return response;
    }

    const record = await this.db.deleteInvoiceLocally(id);
    await this.db.addToQueue('delete_invoice', 'invoice', id, { id, server_id: serverId });
    document.dispatchEvent(new Event('sync-now'));
    return { success: true, offline: true, data: record, pending_sync: true };
  }

  async createCustomer(payload) {
    if (navigator.onLine) {
      const response = await this.request(`${this.baseUrl}/customers`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      if (!response.offline && response.data?.data) {
        await this.db.saveCustomers([response.data.data]);
      }
      return response;
    }

    const localRecord = await this.db.saveCustomerLocally(payload);
    await this.db.addToQueue('create_customer', 'customer', localRecord.id, localRecord);
    document.dispatchEvent(new Event('sync-now'));
    return { success: true, offline: true, data: localRecord, message: 'Saved offline. Will sync when internet returns.', pending_sync: true };
  }

  async updateCustomer(id, payload) {
    const serverId = await this.db.getServerId(id);
    if (navigator.onLine && serverId) {
      const response = await this.request(`${this.baseUrl}/customers/${serverId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      if (!response.offline && response.data?.data) {
        await this.db.updateCustomerLocally(id, payload);
      }
      return response;
    }

    const record = await this.db.updateCustomerLocally(id, payload);
    await this.db.addToQueue('update_customer', 'customer', record.id, { ...record, server_id: serverId });
    document.dispatchEvent(new Event('sync-now'));
    return { success: true, offline: true, data: record, pending_sync: true };
  }

  async deleteCustomer(id) {
    const serverId = await this.db.getServerId(id);
    if (navigator.onLine && serverId) {
      const response = await this.request(`${this.baseUrl}/customers/${serverId}`, { method: 'DELETE' });
      if (!response.offline) {
        await this.db.deleteCustomerLocally(id);
      }
      return response;
    }

    const record = await this.db.deleteCustomerLocally(id);
    await this.db.addToQueue('delete_customer', 'customer', id, { id, server_id: serverId });
    document.dispatchEvent(new Event('sync-now'));
    return { success: true, offline: true, data: record, pending_sync: true };
  }
}
