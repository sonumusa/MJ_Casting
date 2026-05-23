import './bootstrap';
import { GoldCalc } from './calculations';
import { GoldWorkshopDB } from './idb-manager';
import { OfflineDetector } from './offline-detector';
import { SyncManager } from './sync-manager';
import { ApiClient } from './api-client';

window.GoldCalc = GoldCalc;

const db = new GoldWorkshopDB();
const detector = new OfflineDetector();
const syncManager = new SyncManager(db, detector);
const apiClient = new ApiClient(db);

window.GoldWorkshopDB = db;
window.ApiClient = apiClient;
window.OfflineDetector = detector;
window.SyncManager = syncManager;

document.addEventListener('DOMContentLoaded', async () => {
  detector.initialize();
  await syncManager.initialize();

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker
      .register('/sw.js')
      .then(async (registration) => {
        console.log('Service Worker registered with scope:', registration.scope);

        if ('sync' in registration) {
          try {
            await registration.sync.register('gold-workshop-sync');
          } catch (error) {
            console.warn('Background sync registration failed:', error);
          }
        }

        navigator.serviceWorker.addEventListener('message', (event) => {
          if (event.data?.type === 'SYNC_COMPLETE') {
            document.dispatchEvent(new CustomEvent('sync-complete', { detail: event.data }));
          }
        });
      })
      .catch((error) => {
        console.warn('Service Worker registration failed:', error);
      });
  }

  const pendingCount = await db.getPendingCount();
  detector.renderStatusBar(pendingCount, false, 0);
});
