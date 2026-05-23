export class OfflineDetector {
  constructor(options = {}) {
    this.pingUrl = options.pingUrl || '/api/v1/ping';
    this.pingIntervalMs = options.pingIntervalMs || 15000;
    this.statusBarId = options.statusBarId || 'offline-status-bar';
    this.state = navigator.onLine ? 'uncertain' : 'offline';
    this.intervalId = null;
  }

  initialize() {
    window.addEventListener('online', () => this.handleOnline());
    window.addEventListener('offline', () => this.handleOffline());
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        this.stopPolling();
      } else {
        this.startPolling();
        this.ping();
      }
    });
    this.renderStatusBar();
    this.startPolling();
    if (navigator.onLine) {
      this.ping();
    }
    return this;
  }

  async ping() {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 5000);
    try {
      const response = await fetch(this.pingUrl, {
        method: 'HEAD',
        cache: 'no-cache',
        signal: controller.signal,
      });
      clearTimeout(timeout);
      if (response.ok) {
        this.updateState('online');
        return true;
      }
    } catch (error) {
      // network failure or timeout
    }
    clearTimeout(timeout);
    this.updateState('offline');
    return false;
  }

  handleOnline() {
    this.updateState('uncertain');
    this.ping().then((online) => {
      if (online) {
        document.dispatchEvent(new Event('went-online'));
      }
    });
  }

  handleOffline() {
    this.updateState('offline');
    document.dispatchEvent(new Event('went-offline'));
  }

  updateState(state) {
    if (this.state === state) {
      return;
    }
    this.state = state;
    document.dispatchEvent(new CustomEvent('connectivity-change', { detail: { state } }));
    this.renderStatusBar();
  }

  startPolling() {
    this.stopPolling();
    this.intervalId = setInterval(() => {
      if (navigator.onLine) {
        this.ping();
      }
    }, this.pingIntervalMs);
  }

  stopPolling() {
    if (this.intervalId) {
      clearInterval(this.intervalId);
      this.intervalId = null;
    }
  }

  renderStatusBar(pendingCount = 0, syncing = false, failed = 0) {
    const bar = document.getElementById(this.statusBarId);
    if (!bar) {
      return;
    }

    if (this.state === 'online' && !syncing && !failed) {
      bar.classList.add('hidden');
      return;
    }

    bar.classList.remove('hidden');
    if (this.state === 'offline') {
      bar.innerHTML = `
        <div class="status-bar-content">
          <span class="status-dot offline"></span>
          <strong>آپ آف لائن ہیں</strong>
          <span>Data is being saved locally.</span>
          <span class="status-pill">${pendingCount} items waiting to sync</span>
          <button id="retry-sync-button" class="status-action">Retry Now</button>
        </div>
      `;
    } else if (syncing) {
      bar.innerHTML = `
        <div class="status-bar-content">
          <span class="status-dot syncing"></span>
          <strong>Internet restored.</strong>
          <span>Syncing ${pendingCount} items...</span>
          <div class="status-progress"><span style="width: ${pendingCount ? 100 : 0}%"></span></div>
        </div>
      `;
    } else if (failed) {
      bar.innerHTML = `
        <div class="status-bar-content">
          <span class="status-dot failed"></span>
          <strong>Sync failed for ${failed} items.</strong>
          <button id="retry-sync-button" class="status-action">Retry</button>
        </div>
      `;
    } else {
      bar.innerHTML = `
        <div class="status-bar-content">
          <span class="status-dot online"></span>
          <strong>Back online.</strong>
          <span>Checking for pending sync items...</span>
        </div>
      `;
    }

    const retryButton = bar.querySelector('#retry-sync-button');
    if (retryButton) {
      retryButton.addEventListener('click', () => document.dispatchEvent(new Event('sync-now')));
    }
  }
}
