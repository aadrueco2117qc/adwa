/* Offline-first: local cache + action queue + sync */
(function () {
  const LS = {
    cacheWorkOrders: 'mrtsp.cache.workorders.v1',
    cacheWorkOrderDetail: (id) => `mrtsp.cache.wo.${id}.v1`,
    queue: 'mrtsp.queue.v1',
  };

  function nowMs() { return Date.now(); }
  function uid() { return `q_${nowMs()}_${Math.random().toString(16).slice(2)}`; }

  function loadQueue() {
    try { return JSON.parse(localStorage.getItem(LS.queue) || '[]'); } catch { return []; }
  }
  function saveQueue(items) {
    localStorage.setItem(LS.queue, JSON.stringify(items));
    window.dispatchEvent(new Event('mrtsp:queuechange'));
  }

  function queueAction(type, workOrderId, data = {}, meta = {}) {
    const items = loadQueue();
    items.push({ id: uid(), type, workOrderId, data, meta, ts: nowMs(), synced: false });
    saveQueue(items);
  }

  function markSynced(ids = []) {
    const items = loadQueue();
    const set = new Set(ids);
    const next = items.map((it) => (set.has(it.id) ? { ...it, synced: true } : it));
    saveQueue(next.filter((it) => !it.synced)); // keep queue small for prototype
  }

  function queueCount() { return loadQueue().length; }

  function clearQueue() {
    saveQueue([]);
    console.log('[v0] Offline queue cleared.');
  }

  async function isReallyOnline() {
    if (!navigator.onLine) return false;
    try {
      const r = await fetch(window.MRTS.APP_BASE + 'modules/technician/api/ping.php', { cache: 'no-store' });
      return r.ok;
    } catch {
      return false;
    }
  }

  async function sha256Hex(str) {
    // Best-effort: if WebCrypto is unavailable, return empty string.
    try {
      const enc = new TextEncoder();
      const bytes = enc.encode(String(str));
      const digest = await crypto.subtle.digest('SHA-256', bytes);
      const arr = Array.from(new Uint8Array(digest));
      return arr.map((b) => b.toString(16).padStart(2, '0')).join('');
    } catch {
      return '';
    }
  }

  async function ensureAuditHashes(items) {
    // Spec: keep a local audit hash for offline entries until synced.
    // We compute it at sync time (syncNow is async) so queueAction stays synchronous.
    const userId = (window.MRTS && window.MRTS.USER_ID) ? String(window.MRTS.USER_ID) : '';
    for (const it of items) {
      it.meta = it.meta && typeof it.meta === 'object' ? it.meta : {};
      if (it.meta.auditHash) continue;
      const payload = userId + '|' + String(it.workOrderId) + '|' + String(it.type) + '|' + String(it.ts) + '|' + JSON.stringify(it.data || {});
      const h = await sha256Hex(payload);
      if (h) it.meta.auditHash = h;
    }
  }

  async function syncNow() {
    let items = loadQueue();
    if (!items.length) return { ok: true, results: [], conflicts: [] };
    if (typeof window.enrichOfflineQueueBeforeSync === 'function') {
      try {
        items = window.enrichOfflineQueueBeforeSync(items) || items;
      } catch (e) {
        console.warn('[v0] enrichOfflineQueueBeforeSync failed:', e);
      }
    }
    await ensureAuditHashes(items);

    const allResults = [];
    const allErrors  = [];

    // ── Step 1: Upload blob items one at a time ──────────────────
    // Each blob (evidence/config) is sent as its own FormData POST so a
    // single large file never causes the entire batch to exceed post_max_size.
    const blobItems    = items.filter((it) => it.meta && it.meta.hasBlob && it.meta.blobId);
    const nonBlobItems = items.filter((it) => !(it.meta && it.meta.hasBlob && it.meta.blobId));

    for (const item of blobItems) {
      const actionType = item.type || item.action || '';
      const woId = item.workOrderId || item.wo_id || '';

      try {
        const blobRecord = await window.MRTS.idbStorage.getBlob(item.meta.blobId);
        if (!blobRecord || !blobRecord.blob) {
          console.warn('[v0] Blob record not found, skipping:', item.meta.blobId);
          // Mark as synced so it doesn't block the queue forever
          markSynced([item.id]);
          allResults.push({ id: item.id, ok: true, action: actionType });
          continue;
        }

        const formData = new FormData();
        formData.append(`item_${item.id}_action`, actionType);
        formData.append(`item_${item.id}_wo_id`, woId);

        if (actionType === 'evidence_add') {
          formData.append(`item_${item.id}_side`, item.data.side || '');
          formData.append(`item_${item.id}_kind`, item.data.kind || 'image');
          formData.append(`item_${item.id}_name`, item.data.name || blobRecord.fileName || '');
          formData.append(`item_${item.id}_file`, blobRecord.blob, blobRecord.fileName);
        } else if (actionType === 'config_add') {
          formData.append(`item_${item.id}_name`, item.data.name || blobRecord.fileName || '');
          formData.append(`item_${item.id}_file`, blobRecord.blob, blobRecord.fileName);
        }

        const data = await window.MRTS.api('/modules/technician/api/sync.php', {
          method: 'POST',
          body: formData,
        });

        const result = (data.results || [])[0] || { id: item.id, ok: true, action: actionType };
        allResults.push(result);

        if (result.ok) {
          markSynced([item.id]);
          if (result.serverUrl && typeof window.updateDraftItemAfterSync === 'function') {
            window.updateDraftItemAfterSync(item.id, actionType, result.serverUrl);
          }
          try { await window.MRTS.idbStorage.deleteBlob(item.meta.blobId); } catch {}
        } else {
          allErrors.push(result);
          if (typeof window.updateDraftItemError === 'function') {
            window.updateDraftItemError(item.id, actionType, result.error || 'Sync failed');
          }
        }
      } catch (e) {
        console.error('[v0] Blob upload failed for item', item.id, e);
        allErrors.push({ id: item.id, ok: false, action: actionType, error: e.message });
        if (typeof window.updateDraftItemError === 'function') {
          window.updateDraftItemError(item.id, actionType, e.message || 'Upload failed');
        }
      }
    }

    // ── Step 2: Send all non-blob items as a single JSON batch ───
    if (nonBlobItems.length > 0) {
      try {
        const payload = { items: nonBlobItems };
        const data = await window.MRTS.api('/modules/technician/api/sync.php', {
          method: 'POST',
          body: JSON.stringify(payload),
          headers: { 'Content-Type': 'application/json' },
        });

        const results = data.results || [];
        const okIds = [];

        for (const result of results) {
          allResults.push(result);
          if (result.ok) {
            okIds.push(result.id);
            if (result.serverUrl && typeof window.updateDraftItemAfterSync === 'function') {
              window.updateDraftItemAfterSync(result.id, result.action, result.serverUrl);
            }
          } else {
            allErrors.push(result);
            if (typeof window.updateDraftItemError === 'function') {
              window.updateDraftItemError(result.id, result.action, result.error || 'Sync failed');
            }
          }
        }
        markSynced(okIds);
      } catch (e) {
        console.error('[v0] Non-blob batch sync failed:', e);
        allErrors.push({ ok: false, error: e.message });
      }
    }

    return { ok: true, results: allResults, conflicts: [], errors: allErrors };
  }

  function cacheSet(key, value) {
    try { localStorage.setItem(key, JSON.stringify({ v: value, ts: nowMs() })); } catch {}
  }
  function cacheGet(key) {
    try {
      const raw = JSON.parse(localStorage.getItem(key) || 'null');
      return raw && raw.v ? raw.v : null;
    } catch { return null; }
  }

  function updateNetUI() {
    const dot = document.getElementById('offlineDot');
    const txt = document.getElementById('offlineText');
    if (!dot || !txt) return;
    isReallyOnline().then((ok) => {
      if (ok) {
        dot.style.background = 'var(--success)';
        txt.textContent = 'Online';
      } else {
        dot.style.background = 'var(--warn)';
        txt.textContent = 'Offline';
      }
    });
  }

  function wireGlobal() {
    const btn = document.getElementById('syncNowBtn');
    if (btn) {
      btn.addEventListener('click', async () => {
        btn.disabled = true;
        btn.textContent = 'Syncing…';
        try {
          const online = await isReallyOnline();
          if (!online) throw new Error('You are offline');
          const result = await syncNow();
          if (result.conflicts.length) {
            (window.MRTS && window.MRTS.modal
              ? window.MRTS.modal.toast(`Sync completed with ${result.conflicts.length} conflict(s).`, { type: 'warning' })
              : alert(`Sync completed with ${result.conflicts.length} conflict(s) (prototype simulation).`));
          } else {
            (window.MRTS && window.MRTS.modal
              ? window.MRTS.modal.toast('Sync complete', { type: 'success' })
              : alert('Sync complete'));
          }
        } catch (e) {
          (window.MRTS && window.MRTS.modal
            ? window.MRTS.modal.toast(e.message || 'Sync failed', { type: 'error' })
            : alert(e.message || 'Sync failed'));
        } finally {
          btn.disabled = false;
          btn.textContent = 'Sync';
        }
      });
    }

    window.addEventListener('online', updateNetUI);
    window.addEventListener('offline', updateNetUI);
    window.addEventListener('mrtsp:queuechange', () => {
      const el = document.getElementById('queueCount');
      if (el) el.textContent = String(queueCount());
    });
    // Background-ish sync attempt when we become online (best-effort)
    window.addEventListener('online', async () => {
      try {
        const ok = await isReallyOnline();
        if (!ok) return;
        await syncNow();
      } catch {}
    });
    updateNetUI();
    const el = document.getElementById('queueCount');
    if (el) el.textContent = String(queueCount());
  }

  // Register service worker (best-effort)
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register(window.MRTS.APP_BASE + '/public/sw.js').catch(() => {});
    });
  }

  window.MRTS = window.MRTS || {};
  window.MRTS.offline = {
    LS,
    cacheSet,
    cacheGet,
    queueAction,
    queueCount,
    clearQueue,
    syncNow,
    isReallyOnline,
    wireGlobal,
  };

  document.addEventListener('DOMContentLoaded', wireGlobal);
})();

