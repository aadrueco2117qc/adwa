// Global tab switching functions (must be outside DOMContentLoaded for onclick handlers)

// Switch secondary tabs (simplified - single tab list)
function switchSecondaryTab(secondaryKey, btn) {
  // Remove active state from all secondary buttons
  document.querySelectorAll('.secondary-tab-btn').forEach(b => b.classList.remove('tab-on'));
  
  // Add active state to current button
  if (btn) btn.classList.add('tab-on');
  
  // Hide all secondary tab panels
  document.querySelectorAll('[id^="tab-"]').forEach(p => {
    p.classList.add('hidden');
  });
  
  // Show the selected tab
  const panel = document.getElementById('tab-' + secondaryKey);
  if (panel) panel.classList.remove('hidden');
}

// Legacy compatibility - keep switchTab for any old onclick handlers
function switchTab(key, btn) {
  switchSecondaryTab(key, btn);
}

document.addEventListener('DOMContentLoaded', async () => {
  const woId = (new URLSearchParams(window.location.search)).get('id') || '';
  if (!woId) {
    await MRTS.modal.alert('Missing work order id', { type: 'error' });
    window.location.href = window.MRTS.APP_BASE + '/modules/technician/index.php';
    return;
  }

  // Elements
  const els = {
    status: document.getElementById('woStatusBadge'),
    number: document.getElementById('woNumber'),
    title: document.getElementById('woTitle'),
    desc: document.getElementById('woDesc'),
    priority: document.getElementById('woPriority'),
    location: document.getElementById('woLocation'),
    requester: document.getElementById('woRequester'),

    checklistList: document.getElementById('checklistList'),
    checklistItems: document.getElementById('checklistItems'),
    checklistProgress: document.getElementById('checklistProgress'),
    safetyList: document.getElementById('safetyList'),
    safetyProgress: document.getElementById('safetyProgress'),
    notesList: document.getElementById('notesList'),
    noteTitle: document.getElementById('noteTitle'),
    noteText: document.getElementById('noteText'),
    beforeFiles: document.getElementById('beforeFiles'),
    afterFiles: document.getElementById('afterFiles'),
    beforeMedia: document.getElementById('beforeMedia'),
    afterMedia: document.getElementById('afterMedia'),
    beforeCount: document.getElementById('beforeCount'),
    afterCount: document.getElementById('afterCount'),
    configFiles: document.getElementById('configFiles'),
    configMedia: document.getElementById('configMedia'),
    configCount: document.getElementById('configCount'),
    partNumber: document.getElementById('partNumber'),
    partQty: document.getElementById('partQty'),
    partSerial: document.getElementById('partSerial'),
    partsList: document.getElementById('partsList'),
    signatorySelect: document.getElementById('signatorySelect'),
    sigCanvas: document.getElementById('sigCanvas'),
    sigStatus: document.getElementById('sigStatus'),
    btnClearSig: document.getElementById('btnClearSig'),
    btnSaveSig: document.getElementById('btnSaveSig'),
    btnSaveDraft: document.getElementById('btnSaveDraft'),
    btnComplete: document.getElementById('btnComplete'),
    blocker: document.getElementById('completeBlocker'),
    btnAddNote: document.getElementById('btnAddNote'),
    btnAddPart: document.getElementById('btnAddPart'),
    timerValue: document.getElementById('timerValue'),
    timerState: document.getElementById('timerState'),
    btnStart: document.getElementById('btnStart'),
    btnStop: document.getElementById('btnStop'),
    laborType: document.getElementById('laborType'),
  };

  // Tabs
  document.querySelectorAll('.tab').forEach((t) => {
    t.addEventListener('click', () => {
      document.querySelectorAll('.tab').forEach((x) => x.classList.remove('is-active'));
      document.querySelectorAll('.tab-content').forEach((x) => x.classList.add('hidden'));
      t.classList.add('is-active');
      document.getElementById('tab-' + t.getAttribute('data-tab')).classList.remove('hidden');
    });
  });

  // Local state (persisted)
  const stateKey = window.MRTS.offline.LS.cacheWorkOrderDetail(woId);
  const draftKey = `mrtsp.draft.${woId}.v1`;

  const defaultDraft = {
    woId,
    safety: {}, // id -> boolean
    checklist: {}, // id -> boolean
    notes: [], // {id, title, text, ts, source}
    evidence: { before: [], after: [] }, // {id, kind, name, blobId, dataUrl (transient), state: 'pending'|'saved'|'synced'|'error', error?: string, serverUrl?: string}
    config: [], // {id, name, blobId, dataUrl (transient), state: 'pending'|'saved'|'synced'|'error', error?: string, serverUrl?: string}
    parts: [], // {id, partNumber, qty, serial}
    timer: { running: false, startedAt: null, elapsedMs: 0, pausedMs: null, laborType: null },
    time_logs: [], // {id, labor_type, elapsed_ms, segment_ms, created_at, status}
    signoff: { signerName: '', signatoryUserId: null, signatureDataUrl: null },
  };

  // SESSION FLAG: track whether sync has been done this page load
  const sessionSyncedKey = `mrtsp.synced_session.${woId}`;

  function loadDraft() {
    // Always return a blank draft on page load.
    // Resolved/closed work orders are seeded from server data inside load().
    // In-progress work is restored only when the technician clicks Sync.
    return structuredClone(defaultDraft);
  }

  function loadSyncedDraft() {
    // Called by restoreSyncedDraft() when the user clicks Sync.
    // Reads the previously saved draft from localStorage and deep-merges it
    // with defaultDraft so any new fields added to defaultDraft are present.
    try {
      const stored = JSON.parse(localStorage.getItem(draftKey) || 'null');
      if (!stored) {
        console.log('[v0] loadSyncedDraft: nothing in localStorage for', draftKey);
        return structuredClone(defaultDraft);
      }
      const merged = { ...structuredClone(defaultDraft), ...stored };
      // Ensure all sub-objects exist and are properly typed
      if (!merged.time_logs || !Array.isArray(merged.time_logs)) merged.time_logs = [];
      if (!merged.safety   || typeof merged.safety   !== 'object') merged.safety   = {};
      if (!merged.checklist|| typeof merged.checklist!== 'object') merged.checklist= {};
      if (!merged.notes    || !Array.isArray(merged.notes))        merged.notes    = [];
      if (!merged.parts    || !Array.isArray(merged.parts))        merged.parts    = [];
      if (!merged.config   || !Array.isArray(merged.config))       merged.config   = [];
      if (!merged.evidence || typeof merged.evidence !== 'object') merged.evidence = { before: [], after: [] };
      if (!Array.isArray(merged.evidence.before)) merged.evidence.before = [];
      if (!Array.isArray(merged.evidence.after))  merged.evidence.after  = [];
      if (!merged.signoff  || typeof merged.signoff !== 'object')  merged.signoff  = structuredClone(defaultDraft.signoff);
      if (!merged.timer    || typeof merged.timer   !== 'object')  merged.timer    = structuredClone(defaultDraft.timer);
      console.log('[v0] loadSyncedDraft: restored from localStorage', {
        draftKey,
        safety_keys:    Object.keys(merged.safety).length,
        checklist_keys: Object.keys(merged.checklist).length,
        notes:          merged.notes.length,
        parts:          merged.parts.length,
        time_logs:      merged.time_logs.length,
        evidence_before:merged.evidence.before.length,
        evidence_after: merged.evidence.after.length,
      });
      return merged;
    } catch (e) {
      console.error('[v0] loadSyncedDraft: error reading localStorage:', e);
      return structuredClone(defaultDraft);
    }
  }

  function saveDraft(next) {
    localStorage.setItem(draftKey, JSON.stringify(next));
  }

  async function migrateDraftToIndexedDB(draftObj) {
    // Convert old Base64 dataURLs to IndexedDB Blobs
    let needsSave = false;
    
    // Migrate evidence
    for (const side of ['before', 'after']) {
      for (const media of draftObj.evidence[side] || []) {
        if (media.dataUrl && !media.blobId) {
          try {
            const blobId = await window.MRTS.idbStorage.migrateDataUrlToBlob(media.dataUrl, woId);
            if (blobId) {
              media.blobId = blobId;
              delete media.dataUrl;
              needsSave = true;
            }
          } catch (e) {
            console.error('[v0] Migration failed for evidence:', e);
          }
        }
      }
    }
    
    // Migrate config files
    for (const cfg of draftObj.config || []) {
      if (cfg.dataUrl && !cfg.blobId) {
        try {
          const blobId = await window.MRTS.idbStorage.migrateDataUrlToBlob(cfg.dataUrl, woId);
          if (blobId) {
            cfg.blobId = blobId;
            delete cfg.dataUrl;
            needsSave = true;
          }
        } catch (e) {
          console.error('[v0] Migration failed for config:', e);
        }
      }
    }
    
    if (needsSave) saveDraft(draftObj);
    return draftObj;
  }

  let wo = null;
  let draft = loadDraft();
  let migrationDone = false;
  let sig = null;
  let isEditableNow = false;

  function showLockedMessage() {
    MRTS.modal.toast('Start Work first to enable Technician Ops actions.', { type: 'warning', duration: 3500 });
  }

  function canMutateOrWarn() {
    if (isEditableNow) return true;
    showLockedMessage();
    return false;
  }

  function applyReadOnlyState() {
    const disable = !isEditableNow;
    const controls = [
      els.beforeFiles,
      els.afterFiles,
      els.configFiles,
      els.btnAddNote,
      els.btnAddPart,
      els.btnClearSig,
      els.btnSaveSig,
      els.btnSaveDraft,
      els.btnComplete,
      els.btnStart,
      els.btnStop,
      els.laborType,
      els.partNumber,
      els.partQty,
      els.partSerial,
      els.noteTitle,
      els.noteText,
      els.signatorySelect,
    ];

    controls.forEach((el) => {
      if (!el) return;
      el.disabled = disable;
    });

    // Also disable/enable ALL inputs, selects, textareas and buttons
    // inside the tab panes that are not covered by the named els above.
    // This catches dynamically-rendered safety/checklist checkboxes and
    // any other interactive controls added by renderSafety/renderChecklist.
    document.querySelectorAll(
      '#tab-safety input, #tab-safety button, ' +
      '#tab-checklist input, #tab-checklist button, ' +
      '#tab-timetracking input, #tab-timetracking button, #tab-timetracking select, ' +
      '#tab-parts input, #tab-parts button, #tab-parts select, ' +
      '#tab-communication input, #tab-communication button, #tab-communication textarea, ' +
      '#tab-evidence input, #tab-evidence button, ' +
      '#tab-signoff input, #tab-signoff button, #tab-signoff select, #tab-signoff textarea'
    ).forEach((el) => {
      // Skip elements that are intentionally always-on (e.g. tab nav, read-only displays)
      if (el.dataset.alwaysEnabled) return;
      el.disabled = disable;
    });

    // Show/hide gate banners in each tab pane.
    // Gate banners only make sense for assigned/scheduled/new — not for resolved/closed.
    const woStatus = (wo && wo.status) ? wo.status.toLowerCase() : '';
    const isCompletedWO = (woStatus === 'resolved' || woStatus === 'closed');
    document.querySelectorAll('.gateNotice').forEach((banner) => {
      // Hide gate notice entirely for completed work orders — they are read-only
      // but the technician should see their work history, not a "Start Work" prompt.
      banner.style.display = (disable && !isCompletedWO) ? 'flex' : 'none';
    });

    // Enable/disable the signature canvas drawing
    if (sig && typeof sig.setEnabled === 'function') {
      sig.setEnabled(!disable);
    }

    // Show/hide the locked overlay on the signature canvas
    const sigLockedOverlay = document.getElementById('sigLockedOverlay');
    if (sigLockedOverlay) {
      sigLockedOverlay.style.display = disable ? 'flex' : 'none';
      // Update overlay message based on WO status
      const overlayLabel = sigLockedOverlay.querySelector('span');
      if (overlayLabel && wo) {
        const st = (wo.status || '').toLowerCase();
        const isDone = st === 'resolved' || st === 'closed';
        overlayLabel.textContent = isDone ? 'Read-only — work order is completed' : 'Locked — Start Work first';
      }
    }
  }

  window.setTechnicianEditable = function(nextEditable) {
    isEditableNow = !!nextEditable;
    applyReadOnlyState();
    renderSafety();
    renderChecklist();
  };

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  function renderHeader() {
    if (!wo) return;
    const statusLabels = {
      new: 'New', assigned: 'Assigned', scheduled: 'Scheduled',
      in_progress: 'In Progress', on_hold: 'On Hold',
      resolved: 'Resolved', closed: 'Closed', pending: 'Pending'
    };
    const statusDots = {
      new: '#9a9a9a', assigned: '#3B82F6', scheduled: '#3B82F6',
      in_progress: '#D97706', on_hold: '#EC4899',
      resolved: '#3B6D11', closed: '#9a9a9a', pending: '#9a9a9a'
    };
    const st = wo.status || 'pending';
    const dotColor = statusDots[st] || '#9a9a9a';
    const label = statusLabels[st] || st.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    if (els.status) {
      els.status.innerHTML = `<span style="width:6px;height:6px;border-radius:50%;display:inline-block;flex-shrink:0;background:${dotColor};"></span>${label}`;
      els.status.className = `wo-badge badge-${st}`;
    }
    if (els.number) els.number.textContent = wo.wo_number || wo.id;
    if (els.title) els.title.textContent = wo.title || '—';
    if (els.desc) els.desc.textContent = wo.description || '—';
    if (els.priority) els.priority.textContent = (wo.priority || 'low').toUpperCase();
    if (els.location) els.location.textContent = wo.location || '—';
    if (els.requester) els.requester.textContent = (wo.requester && wo.requester.name) ? wo.requester.name : '—';
    const phone = wo.requester && wo.requester.phone ? wo.requester.phone : '';
    const email = wo.requester && wo.requester.email ? wo.requester.email : '';

  }

  function renderChecklist() {
    const items = (wo && wo.checklist) ? wo.checklist : [];
    
    // Auto-verify photo items ONLY when a successfully saved/synced upload actually exists.
    // Error-state or empty entries must NOT trigger auto-check.
    const hasBeforePhoto = (draft.evidence.before || []).some(
      (m) => m.state !== 'error' && (m.blobId || m.serverUrl || m.dataUrl)
    );
    const hasAfterPhoto = (draft.evidence.after || []).some(
      (m) => m.state !== 'error' && (m.blobId || m.serverUrl || m.dataUrl)
    );

    // Auto-verify items based on real evidence presence
    items.forEach((it) => {
      if (it.verification_type === 'photo_before') {
        // Only set to true when a real upload exists; remove the auto-check when there is none
        if (hasBeforePhoto) {
          draft.checklist[it.id] = true;
        } else {
          // Un-check if no real before photo exists (clears stale draft state)
          delete draft.checklist[it.id];
        }
      }
      if (it.verification_type === 'photo_after') {
        if (hasAfterPhoto) {
          draft.checklist[it.id] = true;
        } else {
          delete draft.checklist[it.id];
        }
      }
      it.is_done = !!draft.checklist[it.id];
    });
    
  // Count auto-verified items (time tracking, signature, before/after photos)
  const woHasStopLog =
    wo &&
    Array.isArray(wo.time_logs) &&
    wo.time_logs.some((r) => (r.action || '') === 'stop');
  const hasTimeLogs =
    (draft.time_logs || []).length > 0 || timerSeconds > 0 || woHasStopLog;
  const woHasSignoff =
    wo &&
    wo.signoff &&
    ((wo.signoff.signature_path && wo.signoff.signature_path !== 'data:inline') ||
      String(wo.signoff.signer_name || '').trim());
  const hasSig = !!draft.signoff.signatureDataUrl || woHasSignoff;
  
  // Filter out photo items from manual checklist count.
  // Match by verification_type (properly seeded DB rows) OR by item text
  // (DB rows where verification_type was not populated).
  const photoTexts = ['capture before-repair photo', 'capture after-repair photo'];
  const isPhotoItem = (it) =>
    ['photo_before', 'photo_after'].includes(it.verification_type || '') ||
    photoTexts.includes((it.text || '').toLowerCase().trim());
  const manualItems = items.filter((it) => !isPhotoItem(it));
  
  // Count completed manual items
  const itemsDone = manualItems.filter((it) => !!draft.checklist[it.id]).length;
  
  // Total includes manual items + 4 auto-verified rows (Time Logged, Signatory, Before Photo, After Photo)
  const done = itemsDone + (hasTimeLogs ? 1 : 0) + (hasSig ? 1 : 0) + (hasBeforePhoto ? 1 : 0) + (hasAfterPhoto ? 1 : 0);
  const total = manualItems.length + 4; // +4 for auto-verified rows
  const percentage = total > 0 ? Math.round(done / total * 100) : 0;
  
  if (els.checklistProgress) {
    els.checklistProgress.innerHTML = `${done}/${total} items <span class="text-xs ml-1">(${percentage}%)</span>`;
  }
  
  // Update progress bar
  const progressBar = document.querySelector('.cl-progress-fill');
  if (progressBar) {
    progressBar.style.width = `${percentage}%`;
  }
  
  // Update tab badge
  const badge = document.querySelector('[data-badge="checklist"]');
  if (badge) {
    badge.textContent = `${done}/${total}`;
    if (done === total && total > 0) {
      badge.classList.add('done');
    } else {
      badge.classList.remove('done');
    }
  }
  
  // Render into the items container (not the full checklistList, to preserve auto-verified status rows)
  const checklistContainer = els.checklistItems || els.checklistList;
  
  // Render only manual (non-photo) checklist items — same filter as above
  const renderItems = items.filter((it) => !isPhotoItem(it));
  
  checklistContainer.innerHTML = renderItems.length > 0 ? renderItems.map((it, idx) => {
      const checked = !!draft.checklist[it.id];
      const borderStyle = idx < renderItems.length - 1 ? 'border-bottom:1px solid #f3f4f6;' : '';
      
      // Regular clickable checklist items
      return `
        <label class="checklist-row${checked ? ' checklist-row--done' : ''}" style="${borderStyle}">
          <input type="checkbox" 
                 style="accent-color:#1a5c2a;width:14px;height:14px;flex-shrink:0;cursor:pointer;" 
                 data-check="${it.id}"
                 ${!isEditableNow ? 'disabled' : ''}
                 ${checked ? 'checked' : ''}>
          <span class="checklist-text" style="${checked ? 'color:#9ca3af;text-decoration:line-through;' : ''}">${escapeHtml(it.text)}</span>
          ${it.required ? '<span style="font-size:11px;color:#b91c1c;flex-shrink:0;font-weight:600;">*</span>' : ''}
        </label>
      `;
    }).join('') : `
      <div style="padding:40px 20px;text-align:center;">
        <p style="font-size:13px;color:#9ca3af;font-style:italic;">No manual checklist items assigned for this work order.</p>
      </div>
    `;

    checklistContainer.querySelectorAll('[data-check]').forEach((input) => {
      input.addEventListener('change', () => {
        if (!canMutateOrWarn()) {
          input.checked = !!draft.checklist[input.getAttribute('data-check')];
          return;
        }
        if (input.disabled) return;
        const id = input.getAttribute('data-check');
        draft.checklist[id] = input.checked;
        saveDraft(draft);
        // Sync draft back to __WO_DATA__ for badge updates
        if (wo.checklist && wo.checklist.length > 0) {
          wo.checklist.forEach((item) => {
            if (item.id == id) item.is_done = draft.checklist[id];
          });
        }
        // Note: checklist state is held locally until completion — no immediate server write
        renderChecklist();
        updateCompletionBlocker();
        if (typeof updateCompletionBadges === 'function') updateCompletionBadges();
        // Auto-sync to persist changes immediately
        window.MRTS.offline.isReallyOnline().then(online => {
          if (online) {
            window.MRTS.offline.syncNow().catch(e => console.warn('[v0] Auto-sync failed:', e));
          }
        });
      });
    });
    
    // Also render time logs in checklist tab
    renderChecklistTimeLogs();
  }
  
  function renderChecklistTimeLogs() {
    // ── Work Time Logged checklist row (auto-verified) ───────────────────────────
    const timeCheckbox = document.getElementById('clTimeCheckbox');
    const timeLabel    = document.getElementById('clTimeLabel');
    const timeStatus   = document.getElementById('clTimeStatus');
    const timeRow      = document.getElementById('clRowTimeTracking');
    const woHasStopLog =
      wo &&
      Array.isArray(wo.time_logs) &&
      wo.time_logs.some((r) => (r.action || '') === 'stop');
    const hasTimeLogs =
      (draft.time_logs || []).length > 0 || timerSeconds > 0 || woHasStopLog;

    if (timeCheckbox) {
      if (hasTimeLogs) {
        timeCheckbox.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;flex-shrink:0;border-radius:3px;border:1.5px solid #1a5c2a;background:#1a5c2a;pointer-events:none;';
        timeCheckbox.innerHTML = '<svg width="9" height="9" viewBox="0 0 10 10" fill="none"><path d="M2 5l2.5 2.5L8 3" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      } else {
        timeCheckbox.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;flex-shrink:0;border-radius:3px;border:1.5px solid #d1d5db;background:#fff;pointer-events:none;';
        timeCheckbox.innerHTML = '';
      }
    }
    if (timeLabel) {
      if (hasTimeLogs) {
        timeLabel.style.color = '#9ca3af';
        timeLabel.style.textDecoration = 'line-through';
      } else {
        timeLabel.style.color = '';
        timeLabel.style.textDecoration = '';
      }
    }
    if (timeStatus) {
      if (timerRunning) {
        timeStatus.textContent = 'Running (' + formatTime(timerSeconds) + ')';
        timeStatus.style.color = '#92400e';
        timeStatus.style.cursor = 'default';
        timeStatus.onclick = null;
      } else if ((draft.time_logs || []).length > 0) {
        const totalMs = draft.time_logs.reduce((s, l) => s + (l.elapsed_ms || 0), 0);
        timeStatus.textContent = draft.time_logs.length + ' entr' + (draft.time_logs.length === 1 ? 'y' : 'ies') + ' (' + window.MRTS.fmtTime(totalMs) + ')';
        timeStatus.style.color = '#15803d';
        timeStatus.style.cursor = 'default';
        timeStatus.onclick = null;
      } else if (woHasStopLog && wo.time_logs && wo.time_logs.length) {
        const stops = wo.time_logs.filter((r) => (r.action || '') === 'stop');
        const totalMs = stops.reduce((s, r) => s + (parseInt(r.elapsed_ms, 10) || 0), 0);
        const suffix = totalMs > 0 ? ' (' + window.MRTS.fmtTime(totalMs) + ')' : '';
        timeStatus.textContent =
          stops.length + ' entr' + (stops.length === 1 ? 'y' : 'ies') + suffix;
        timeStatus.style.color = '#15803d';
        timeStatus.style.cursor = 'default';
        timeStatus.onclick = null;
      } else if (timerSeconds > 0) {
        timeStatus.textContent = 'Paused (' + formatTime(timerSeconds) + ')';
        timeStatus.style.color = '#92400e';
        timeStatus.style.cursor = 'default';
        timeStatus.onclick = null;
      } else {
        timeStatus.textContent = 'Go to Time Tracking ↗';
        timeStatus.style.color = '#f59e0b';
        timeStatus.style.cursor = 'pointer';
        timeStatus.onclick = function() { switchSecondaryTab('timetracking', document.querySelector('[data-tab=timetracking]')); };
      }
    }
    if (timeRow) {
      timeRow.classList.toggle('checklist-row--done', hasTimeLogs);
    }

    // ── Authorized Signatory Captured checklist row (auto-verified) ───────────────────────────────
    const sigCheckbox = document.getElementById('clSigCheckbox');
    const sigLabel    = document.getElementById('clSigLabel');
    const sigStatus   = document.getElementById('clSigStatus');
    const sigRow      = document.getElementById('clRowSignature');
    const woHasSignoff =
      wo &&
      wo.signoff &&
      ((wo.signoff.signature_path && wo.signoff.signature_path !== 'data:inline') ||
        String(wo.signoff.signer_name || '').trim());
    const hasSig = !!draft.signoff.signatureDataUrl || woHasSignoff;

    if (sigCheckbox) {
      if (hasSig) {
        sigCheckbox.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;flex-shrink:0;border-radius:3px;border:1.5px solid #1a5c2a;background:#1a5c2a;pointer-events:none;';
        sigCheckbox.innerHTML = '<svg width="9" height="9" viewBox="0 0 10 10" fill="none"><path d="M2 5l2.5 2.5L8 3" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      } else {
        sigCheckbox.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;flex-shrink:0;border-radius:3px;border:1.5px solid #d1d5db;background:#fff;pointer-events:none;';
        sigCheckbox.innerHTML = '';
      }
    }
    if (sigLabel) {
      if (hasSig) {
        sigLabel.style.color = '#9ca3af';
        sigLabel.style.textDecoration = 'line-through';
      } else {
        sigLabel.style.color = '';
        sigLabel.style.textDecoration = '';
      }
    }
    if (sigStatus) {
      if (hasSig) {
        const dispName =
          draft.signoff.signerName ||
          (wo && wo.signoff && wo.signoff.signer_name) ||
          '';
        const name = dispName ? 'Signed by ' + dispName : 'Signature captured';
        sigStatus.textContent = name;
        sigStatus.style.color = '#15803d';
        sigStatus.style.cursor = 'default';
        sigStatus.onclick = null;
      } else {
        sigStatus.textContent = 'Go to Sign-off ↗';
        sigStatus.style.color = '#f59e0b';
        sigStatus.style.cursor = 'pointer';
        sigStatus.onclick = function() { switchSecondaryTab('signoff', document.querySelector('[data-tab=signoff]')); };
      }
    }
    if (sigRow) {
      sigRow.classList.toggle('checklist-row--done', hasSig);
    }

    // ── Saved signature preview in Sign-off tab ───────────────
    const previewWrap  = document.getElementById('savedSigPreviewWrap');
    const previewImg   = document.getElementById('savedSigPreviewImg');
    const previewSigner = document.getElementById('savedSigSignerName');
    if (previewWrap && previewImg) {
      if (hasSig) {
        previewImg.src =
          draft.signoff.signatureDataUrl ||
          (wo && wo.signoff && wo.signoff.signature_path) ||
          '';
        if (previewSigner) {
          previewSigner.textContent =
            draft.signoff.signerName || (wo && wo.signoff && wo.signoff.signer_name) || '';
        }
        previewWrap.style.display = '';
      } else {
        previewWrap.style.display = 'none';
      }
    }

    // ── Capture Before-Repair Photo checklist row (auto-verified) ───────────────────────────
    const photoBeforeCheckbox = document.getElementById('clPhotoBeforeCheckbox');
    const photoBeforeLabel    = document.getElementById('clPhotoBeforeLabel');
    const photoBeforeStatus   = document.getElementById('clPhotoBeforeStatus');
    const photoBeforeRow      = document.getElementById('clRowPhotoBeforeRow');
    const hasBeforePhoto      = (draft.evidence.before || []).some(
      (m) => m.state !== 'error' && (m.blobId || m.serverUrl || m.dataUrl)
    );

    if (photoBeforeCheckbox) {
      if (hasBeforePhoto) {
        photoBeforeCheckbox.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;flex-shrink:0;border-radius:3px;border:1.5px solid #1a5c2a;background:#1a5c2a;pointer-events:none;';
        photoBeforeCheckbox.innerHTML = '<svg width="9" height="9" viewBox="0 0 10 10" fill="none"><path d="M2 5l2.5 2.5L8 3" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      } else {
        photoBeforeCheckbox.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;flex-shrink:0;border-radius:3px;border:1.5px solid #d1d5db;background:#fff;pointer-events:none;';
        photoBeforeCheckbox.innerHTML = '';
      }
    }
    if (photoBeforeLabel) {
      if (hasBeforePhoto) {
        photoBeforeLabel.style.color = '#9ca3af';
        photoBeforeLabel.style.textDecoration = 'line-through';
      } else {
        photoBeforeLabel.style.color = '';
        photoBeforeLabel.style.textDecoration = '';
      }
    }
    if (photoBeforeStatus) {
      if (hasBeforePhoto) {
        photoBeforeStatus.textContent = 'Photo uploaded';
        photoBeforeStatus.style.color = '#15803d';
        photoBeforeStatus.style.cursor = 'default';
        photoBeforeStatus.onclick = null;
      } else {
        photoBeforeStatus.textContent = 'Go to Evidence ↗';
        photoBeforeStatus.style.color = '#f59e0b';
        photoBeforeStatus.style.cursor = 'pointer';
        photoBeforeStatus.onclick = function() { switchSecondaryTab('evidence', document.querySelector('[data-tab=evidence]')); };
      }
    }
    if (photoBeforeRow) {
      photoBeforeRow.classList.toggle('checklist-row--done', hasBeforePhoto);
    }

    // ── Capture After-Repair Photo checklist row (auto-verified) ───────────────────────────
    const photoAfterCheckbox = document.getElementById('clPhotoAfterCheckbox');
    const photoAfterLabel    = document.getElementById('clPhotoAfterLabel');
    const photoAfterStatus   = document.getElementById('clPhotoAfterStatus');
    const photoAfterRow      = document.getElementById('clRowPhotoAfterRow');
    const hasAfterPhoto      = (draft.evidence.after || []).some(
      (m) => m.state !== 'error' && (m.blobId || m.serverUrl || m.dataUrl)
    );

    if (photoAfterCheckbox) {
      if (hasAfterPhoto) {
        photoAfterCheckbox.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;flex-shrink:0;border-radius:3px;border:1.5px solid #1a5c2a;background:#1a5c2a;pointer-events:none;';
        photoAfterCheckbox.innerHTML = '<svg width="9" height="9" viewBox="0 0 10 10" fill="none"><path d="M2 5l2.5 2.5L8 3" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      } else {
        photoAfterCheckbox.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;flex-shrink:0;border-radius:3px;border:1.5px solid #d1d5db;background:#fff;pointer-events:none;';
        photoAfterCheckbox.innerHTML = '';
      }
    }
    if (photoAfterLabel) {
      if (hasAfterPhoto) {
        photoAfterLabel.style.color = '#9ca3af';
        photoAfterLabel.style.textDecoration = 'line-through';
      } else {
        photoAfterLabel.style.color = '';
        photoAfterLabel.style.textDecoration = '';
      }
    }
    if (photoAfterStatus) {
      if (hasAfterPhoto) {
        photoAfterStatus.textContent = 'Photo uploaded';
        photoAfterStatus.style.color = '#15803d';
        photoAfterStatus.style.cursor = 'default';
        photoAfterStatus.onclick = null;
      } else {
        photoAfterStatus.textContent = 'Go to Evidence ↗';
        photoAfterStatus.style.color = '#f59e0b';
        photoAfterStatus.style.cursor = 'pointer';
        photoAfterStatus.onclick = function() { switchSecondaryTab('evidence', document.querySelector('[data-tab=evidence]')); };
      }
    }
    if (photoAfterRow) {
      photoAfterRow.classList.toggle('checklist-row--done', hasAfterPhoto);
    }
  }

function renderSafety() {
  const items = (wo && wo.safety) ? wo.safety : [];
  items.forEach((it) => {
    // Always use string key — draft.safety is keyed by string (from getAttribute)
    it.is_done = !!draft.safety[String(it.id || it.safety_id || 0)];
  });
  const done = items.filter((it) => !!draft.safety[String(it.id || it.safety_id || 0)]).length;
  const total = items.length;
  const percentage = total > 0 ? Math.round(done / total * 100) : 0;
  
  if (els.safetyProgress) {
    els.safetyProgress.textContent = `${done} / ${total} complete`;
  }
  
  // Update safety progress bar
  const progressBar = document.querySelector('.safety-progress-fill');
  if (progressBar) {
    progressBar.style.width = `${percentage}%`;
  }
  
  // Update tab badge
  const badge = document.querySelector('[data-badge="safety"]');
  if (badge) {
    badge.textContent = `${done}/${total}`;
    if (done === total && total > 0) {
      badge.classList.remove('warn');
      badge.classList.add('done');
    } else {
      badge.classList.remove('done');
      badge.classList.add('warn');
    }
  }
    
    els.safetyList.innerHTML = items.map((it) => {
      const checked = !!draft.safety[it.id];
      const safetyId = it.id || it.safety_id || 0;
      
      return `
        <label class="checklist-row" style="border-bottom:1px solid #f3f4f6;">
          <input type="checkbox" 
                 style="accent-color:#1a5c2a;width:14px;height:14px;flex-shrink:0;cursor:pointer;" 
                 data-safety="${safetyId}"
                 ${!isEditableNow ? 'disabled' : ''}
                 ${checked ? 'checked' : ''}>
          <span class="checklist-text" style="${checked ? 'color:#9ca3af;text-decoration:line-through;' : ''}">${escapeHtml(it.text)}</span>
          ${it.mandatory ? '<span style="font-size:11px;color:#b91c1c;flex-shrink:0;font-weight:600;">*</span>' : ''}
        </label>
      `;
    }).join('');

    els.safetyList.querySelectorAll('[data-safety]').forEach((input) => {
      input.addEventListener('change', () => {
        if (!canMutateOrWarn()) {
          input.checked = !!draft.safety[input.getAttribute('data-safety')];
          return;
        }
        const id = input.getAttribute('data-safety');
        if (!id || id === '0' || id === 'undefined') return; // guard against bad IDs
        draft.safety[id] = input.checked;
        saveDraft(draft);
        // Sync draft back to __WO_DATA__ for badge updates
        if (wo.safety && wo.safety.length > 0) {
          wo.safety.forEach((item) => {
            const itemId = String(item.id || item.safety_id || 0);
            if (itemId === id) item.is_done = draft.safety[id];
          });
        }
        // Note: safety state is held locally until completion — no immediate server write
        renderSafety();
        updateCompletionBlocker();
        if (typeof updateCompletionBadges === 'function') updateCompletionBadges();
        // Auto-sync to persist changes immediately
        window.MRTS.offline.isReallyOnline().then(online => {
          if (online) {
            window.MRTS.offline.syncNow().catch(e => console.warn('[v0] Auto-sync failed:', e));
          }
        });
      });
    });
  }

  function renderNotes() {
    const notes = draft.notes.slice().sort((a,b)=>b.ts-a.ts);
    els.notesList.innerHTML = notes.length ? notes.map((n) => `
      <div class="note">
        <div class="note__header">
          <div class="note__title">${n.title ? escapeHtml(n.title) : '<em>Untitled</em>'}</div>
          <button class="note__remove" type="button" data-remove-note="${n.id}" title="Remove note">×</button>
        </div>
        <div class="note__meta">
          <span>${escapeHtml(n.source || 'local')}</span>
          <span>${new Date(n.ts).toLocaleString()}</span>
        </div>
        <div class="note__text">${escapeHtml(n.text)}</div>
      </div>
    `).join('') : '';
    
    // Attach remove handlers
    els.notesList.querySelectorAll('[data-remove-note]').forEach((btn) => {
      btn.addEventListener('click', () => {
        if (!canMutateOrWarn()) return;
        const id = btn.getAttribute('data-remove-note');
        draft.notes = draft.notes.filter((x) => x.id !== id);
        saveDraft(draft);
        window.MRTS.offline.queueAction('note_remove', woId, { id });
        renderNotes();
      });
    });
  }

  function renderEvidence() {
    const b = draft.evidence.before;
    const a = draft.evidence.after;
    els.beforeCount.textContent = String(b.length);
    els.afterCount.textContent = String(a.length);
    els.beforeMedia.innerHTML = b.map((m) => mediaTile('before', m)).join('');
    els.afterMedia.innerHTML = a.map((m) => mediaTile('after', m)).join('');
    document.querySelectorAll('[data-media-remove]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        if (!canMutateOrWarn()) return;
        e.preventDefault();
        e.stopPropagation();
        const side = btn.getAttribute('data-side');
        const id = btn.getAttribute('data-media-remove');
        
        // Cleanup blob URLs and delete from IndexedDB
        const media = draft.evidence[side].find((x) => x.id === id);
        if (media && media.blobId && media.dataUrl && media.dataUrl.startsWith('blob:')) {
          URL.revokeObjectURL(media.dataUrl);
          window.MRTS.idbStorage.deleteBlob(media.blobId).catch((e) => {
            console.error('[v0] Failed to delete blob:', e);
          });
        }
        
        draft.evidence[side] = draft.evidence[side].filter((x) => x.id !== id);
        saveDraft(draft);
        window.MRTS.offline.queueAction('evidence_remove', woId, { side, id });
        renderEvidence();
        updateCompletionBlocker();
      });
    });
  }

  // Get icon SVG for file category
  function getFileIcon(filename) {
    const category = window.MRTS.idbStorage.detectFileCategory(filename);
    const icons = {
      config: `<svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>`,
      log: `<svg class="w-8 h-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
      </svg>`,
      backup: `<svg class="w-8 h-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
      </svg>`,
      default: `<svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>`
    };
    return icons[category] || icons.default;
  }

  // Get category label for file
  function getFileLabel(filename) {
    const category = window.MRTS.idbStorage.detectFileCategory(filename);
    const labels = { config: 'Config', log: 'Log', backup: 'Backup' };
    return labels[category] || 'File';
  }

  function renderConfig() {
    console.log('[v0] renderConfig called, items:', draft.config);
    const items = draft.config;
    els.configCount.textContent = String(items.length);
    els.configMedia.innerHTML = items.map((m) => {
      const state = m.state || 'saved';
      const fileLabel = getFileLabel(m.name);
      
      // Error state
      if (state === 'error') {
        return `
          <div class="mediaTile mediaTile--error">
            <div class="absolute inset-0 flex flex-col items-center justify-center bg-red-50/95 rounded">
              <svg class="w-5 h-5 text-red-500 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span class="text-xs text-red-700 font-medium text-center px-1">${escapeHtml(m.error || 'Failed')}</span>
            </div>
            <button class="mediaTile__x" type="button" data-config-remove="${m.id}" aria-label="Remove">×</button>
          </div>
        `;
      }
      
      // Normal state - config files don't need dataUrl for display (just icon + filename)
      const badge = state === 'synced' ? `
        <div class="absolute top-1 right-1 bg-olfu-green rounded-full p-1">
          <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
          </svg>
        </div>
      ` : '';
      
      // Category badge
      const categoryBadge = `<div class="absolute bottom-1 left-1 px-1.5 py-0.5 bg-gray-800/70 text-white text-xs rounded font-medium">${fileLabel}</div>`;
      
      return `
        <div class="mediaTile" 
             style="cursor:pointer;" 
             onclick="const url = '${m.serverUrl || m.dataUrl || ''}'; if(url && event.target.tagName !== 'BUTTON') window.open(url, '_blank')">
          <div class="mediaTile__content flex flex-col items-center justify-center h-full">
            ${getFileIcon(m.name)}
            <div class="text-xs text-gray-600 mt-1 truncate max-w-full px-1" title="${escapeHtml(m.name)}">${escapeHtml(m.name)}</div>
          </div>
          ${categoryBadge}
          ${badge}
          <button class="mediaTile__x" type="button" data-config-remove="${m.id}" aria-label="Remove" onclick="event.stopPropagation()">×</button>
        </div>
      `;
    }).join('');

    els.configMedia.querySelectorAll('[data-config-remove]').forEach((b) => {
      b.addEventListener('click', () => {
        if (!canMutateOrWarn()) return;
        const id = b.getAttribute('data-config-remove');
        
        // Cleanup blob URLs and delete from IndexedDB
        const item = draft.config.find((x) => x.id === id);
        if (item && item.blobId && item.dataUrl && item.dataUrl.startsWith('blob:')) {
          URL.revokeObjectURL(item.dataUrl);
          window.MRTS.idbStorage.deleteBlob(item.blobId).catch(() => {});
        }
        
        draft.config = draft.config.filter((x) => x.id !== id);
        saveDraft(draft);
        window.MRTS.offline.queueAction('config_remove', woId, { id });
        renderConfig();
      });
    });
  }

  function mediaTile(side, m) {
    const isVideo = m.kind === 'video';
    const state = m.state || 'saved';
    const displayUrl = m.dataUrl || m.serverUrl;
    const srcAttr = displayUrl ? `src="${displayUrl}"` : '';
    
    // Show error state
    if (state === 'error') {
      return `
        <div class="mediaTile mediaTile--error">
          <div class="absolute inset-0 flex flex-col items-center justify-center bg-red-50/95 rounded">
            <svg class="w-6 h-6 text-red-500 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-xs text-red-700 font-medium text-center px-2">${escapeHtml(m.error || 'Upload failed')}</span>
          </div>
          <button class="mediaTile__x" type="button" data-side="${side}" data-media-remove="${m.id}" aria-label="Remove">×</button>
        </div>
      `;
    }
    
    // Show loading/processing state
    if (!displayUrl) {
      return `
        <div class="mediaTile mediaTile--loading">
          <div class="absolute inset-0 flex flex-col items-center justify-center bg-gray-50/95 rounded">
            <div class="w-4 h-4 border-2 border-gray-300 border-t-olfu-green rounded-full animate-spin mb-1"></div>
            <span class="text-xs text-gray-600 font-medium">${state === 'saved' ? 'Processing...' : 'Syncing...'}</span>
          </div>
          <button class="mediaTile__x" type="button" data-side="${side}" data-media-remove="${m.id}" aria-label="Remove">×</button>
        </div>
      `;
    }
    
    // Normal state
    const inner = isVideo
      ? `<video ${srcAttr} muted playsinline controls></video>`
      : `<img ${srcAttr} alt="${escapeHtml(m.name || 'photo')}" 
             style="cursor:pointer;" 
             onclick="window.open('${displayUrl}', '_blank')" />`;
    
    // Add checkmark for synced state
    const badge = state === 'synced' ? `
      <div class="absolute top-1 right-1 bg-olfu-green rounded-full p-1">
        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
        </svg>
      </div>
    ` : '';
    
    return `
      <div class="mediaTile">
        ${inner}
        ${badge}
        <button class="mediaTile__x" type="button" data-side="${side}" data-media-remove="${m.id}" aria-label="Remove">×</button>
      </div>
    `;
  }

  async function filesToEvidence(files, side) {
    if (!canMutateOrWarn()) return;
    const list = Array.from(files || []);
    const errors = [];
    
    for (const f of list) {
      const kind = (f.type || '').startsWith('video/') ? 'video' : 'image';
      const itemId = `m_${Date.now()}_${Math.random().toString(16).slice(2)}`;
      
      try {
        // Validate file first
        window.MRTS.idbStorage.validateFile(f, kind);
        
        // Save blob to IndexedDB, get back the blobId
        const blobId = await window.MRTS.idbStorage.saveBlobAndGetId(woId, f, kind);
        
        // Store reference to blob in draft with state tracking
        const item = { id: itemId, kind, name: f.name, blobId, state: 'saved' };
        draft.evidence[side].push(item);
        
        // Queue sync action with blobId in metadata
        window.MRTS.offline.queueAction('evidence_add', woId, { side, kind, name: f.name }, { hasBlob: true, blobId });
      } catch (e) {
        const errorMsg = e.message || 'Failed to save file';
        errors.push(`${f.name}: ${errorMsg}`);
        // Still add item to draft but mark as error
        const item = { id: itemId, kind, name: f.name, state: 'error', error: errorMsg };
        draft.evidence[side].push(item);
      }
    }
    
    saveDraft(draft);
    await loadBlobUrlsForEvidence();
    renderEvidence();
    updateCompletionBlocker();
    
    // Refresh checklist to reflect auto-verified items immediately
    renderChecklist();
    if (typeof updateCompletionBadges === 'function') updateCompletionBadges();
    
    // Show errors if any
    if (errors.length > 0) {
      await MRTS.modal.alert('Some files failed to upload:\n' + errors.join('\n'), { type: 'error', title: 'Upload Failed' });
    }
  }

  // Config uploads use EXACT same pattern as evidence uploads
  async function filesToConfig(files) {
    if (!canMutateOrWarn()) return;
    console.log('[v0] filesToConfig START - files:', files, 'length:', files?.length);
    const list = Array.from(files || []);
    console.log('[v0] filesToConfig list:', list);
    const errors = [];
    
    for (const f of list) {
      const itemId = `c_${Date.now()}_${Math.random().toString(16).slice(2)}`;
      console.log('[v0] Processing file:', f.name, 'size:', f.size, 'type:', f.type);
      
      try {
        // Save blob to IndexedDB - no extra validation, just like evidence
        // The file input's accept attribute already limits file types
        console.log('[v0] Calling saveBlobAndGetId...');
        const blobId = await window.MRTS.idbStorage.saveBlobAndGetId(woId, f, 'config');
        console.log('[v0] Got blobId:', blobId);
        
        // Store reference to blob in draft with state tracking (same structure as evidence)
        const item = { id: itemId, name: f.name, blobId, state: 'saved' };
        draft.config.push(item);
        console.log('[v0] Added to draft.config, length:', draft.config.length);
        
        // Queue sync action with blobId in metadata
        window.MRTS.offline.queueAction('config_add', woId, { name: f.name }, { hasBlob: true, blobId });
        console.log('[v0] Queued sync action');
      } catch (e) {
        console.error('[v0] Error in filesToConfig:', e);
        const errorMsg = e.message || 'Failed to save file';
        errors.push(`${f.name}: ${errorMsg}`);
        // Still add item to draft but mark as error
        const item = { id: itemId, name: f.name, state: 'error', error: errorMsg };
        draft.config.push(item);
      }
    }
    
    console.log('[v0] Saving draft...');
    saveDraft(draft);
    console.log('[v0] Loading blob URLs...');
    await loadBlobUrlsForConfig();  // Load URLs BEFORE render, just like evidence
    console.log('[v0] Calling renderConfig...');
    renderConfig();
    console.log('[v0] filesToConfig COMPLETE');
    
    // Reset the file input to allow re-uploading the same file
    els.configFiles.value = '';
    
    // Show errors if any
    if (errors.length > 0) {
      await MRTS.modal.alert('Some files failed to upload:\n' + errors.join('\n'), { type: 'error', title: 'Upload Failed' });
    }
  }

  // Generate ObjectURLs from IndexedDB Blobs for config files (same pattern as evidence)
  async function loadBlobUrlsForConfig() {
    const processConfig = async (item) => {
      if (item.blobId && !item.dataUrl) {
        const url = await window.MRTS.idbStorage.getBlobAsUrl(item.blobId);
        if (url) item.dataUrl = url;
      }
    };
    
    const promises = (draft.config || []).map(processConfig);
    await Promise.all(promises);
  }

  async function loadBlobUrlsForEvidence() {
    // Generate ObjectURLs from IndexedDB Blobs for rendering
    const processMedia = async (media) => {
      if (media.blobId && !media.dataUrl) {
        const url = await window.MRTS.idbStorage.getBlobAsUrl(media.blobId);
        if (url) media.dataUrl = url;
      }
    };
    
    const beforePromises = draft.evidence.before.map(processMedia);
    const afterPromises = draft.evidence.after.map(processMedia);
    await Promise.all([...beforePromises, ...afterPromises]);
  }

  // Called by offline.js after sync response to update draft items with server URLs
  window.updateDraftItemAfterSync = function(itemId, action, serverUrl) {
    if (action === 'evidence_add') {
      const media = draft.evidence.before.find((m) => m.id === itemId) || draft.evidence.after.find((m) => m.id === itemId);
      if (media) {
        media.state = 'synced';
        media.serverUrl = serverUrl;
      }
    } else if (action === 'config_add') {
      const item = draft.config.find((c) => c.id === itemId);
      if (item) {
        item.state = 'synced';
        item.serverUrl = serverUrl;
      }
    }
    saveDraft(draft);
    renderEvidence();
    renderConfig();
  };

  // Called by offline.js when sync fails for an item
  window.updateDraftItemError = function(itemId, action, errorMessage) {
    if (action === 'evidence_add') {
      const media = draft.evidence.before.find((m) => m.id === itemId) || draft.evidence.after.find((m) => m.id === itemId);
      if (media) {
        media.state = 'error';
        media.error = errorMessage;
      }
    } else if (action === 'config_add') {
      const item = draft.config.find((c) => c.id === itemId);
      if (item) {
        item.state = 'error';
        item.error = errorMessage;
      }
    }
    saveDraft(draft);
    renderEvidence();
    renderConfig();
  };

  function renderParts() {
    const list = els.partsList;
    if (!list) return;

    if (!draft.parts.length) {
      list.innerHTML = '<div style="font-size:13px;font-style:italic;color:var(--tech-gray-400);padding:8px 0;">No parts added.</div>';
      updatePartsFooter();
      return;
    }

    list.innerHTML = draft.parts.map((p) => `
      <div class="part-row-item" data-qty="${p.qty}">
        <div>
          <div class="part-row-item__name">
            ${escapeHtml(p.partNumber)}
            ${p.category ? `<span class="part-row-item__cat">${escapeHtml(p.category)}</span>` : ''}
          </div>
          <div class="part-row-item__meta">${p.serial ? 'SN: ' + escapeHtml(p.serial) : '—'}</div>
        </div>
        <span class="part-row-item__qty">×${p.qty}</span>
        <button class="part-row-item__remove" type="button" data-part-remove="${p.id}" title="Remove">×</button>
      </div>
    `).join('');

    list.querySelectorAll('[data-part-remove]').forEach((b) => {
      b.addEventListener('click', () => {
        if (!canMutateOrWarn()) return;
        const id = b.getAttribute('data-part-remove');
        draft.parts = draft.parts.filter((x) => x.id !== id);
        saveDraft(draft);
        window.MRTS.offline.queueAction('part_remove', woId, { id });
        renderParts();
      });
    });

    updatePartsFooter();
  }

  function updatePartsFooter() {
    const footer = document.getElementById('partsFooter');
    const countLbl = document.getElementById('partsCountLabel');
    const footerCount = document.getElementById('partsFooterCount');
    const footerTotal = document.getElementById('partsFooterTotal');
    const n = draft.parts.length;
    const total = draft.parts.reduce((s, p) => s + (parseInt(p.qty) || 1), 0);
    if (countLbl) countLbl.textContent = n ? `— ${n} item${n !== 1 ? 's' : ''}` : '';
    if (footer) footer.style.display = n ? 'flex' : 'none';
    if (footerCount) footerCount.textContent = `${n} part${n !== 1 ? 's' : ''} logged`;
    if (footerTotal) footerTotal.textContent = total;
  }

  // Timer variables
  let timerSeconds = 0;
  let timerRunning = false;
  let timerInterval = null;

  function formatTime(sec) {
    const h = String(Math.floor(sec / 3600)).padStart(2, '0');
    const m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
    const s = String(sec % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
  }

  function updateTimerDisplay() {
    els.timerValue.textContent = formatTime(timerSeconds);
  }

  function startTimer() {
    if (!canMutateOrWarn()) return;
    console.log('[v0] startTimer: timerRunning=', timerRunning, 'timerSeconds=', timerSeconds);
    
    if (!timerRunning) {
      // Validate labor type on first start only
      if (timerSeconds === 0) {
        const laborType = (els.laborType.value || '').trim();
        if (!laborType) {
          MRTS.modal.toast('Please select a labor type before starting the timer', { type: 'warning' });
          return;
        }
        draft.timer.laborType = laborType;
        console.log('[v0] startTimer: labor type set to', laborType);
      }
      
      timerRunning = true;
      draft.timer.running = true;
      draft.timer.startedAt = Date.now();
      
      timerInterval = setInterval(() => {
        timerSeconds++;
        updateTimerDisplay();
        // Keep checklist tab clock in sync
        const clClock = document.getElementById('clTimerValue');
        if (clClock) clClock.textContent = formatTime(timerSeconds);
        const clPill = document.getElementById('clTimerStatePill');
        if (clPill && !clPill.innerHTML.includes('Running')) {
          clPill.style.cssText = 'display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;background:#dcfce7;color:#15803d;';
          clPill.innerHTML = '<span style="width:6px;height:6px;border-radius:50%;background:#16a34a;display:inline-block;"></span>Running';
        }
      }, 1000);
      
      els.timerState.textContent = 'Running';
      els.btnStart.textContent = 'PAUSE';
      els.btnStart.style.background = '#D97706';
      els.btnStart.onmouseover = () => { els.btnStart.style.background = '#B45309'; };
      els.btnStart.onmouseout  = () => { els.btnStart.style.background = '#D97706'; };
      els.btnStop.disabled = false;
      
      saveDraft(draft);
      window.MRTS.offline.queueAction('time_start', woId, { labor_type: draft.timer.laborType });
      console.log('[v0] startTimer: timer started');
      updateCompletionBlocker();
    } else {
      // Pause
      console.log('[v0] startTimer: pausing timer');
      timerRunning = false;
      draft.timer.running = false;
      draft.timer.elapsedMs = timerSeconds * 1000;
      
      clearInterval(timerInterval);
      els.timerState.textContent = 'Paused';
      els.btnStart.textContent = 'START';
      els.btnStart.style.background = 'var(--tech-green)';
      els.btnStart.onmouseover = () => { els.btnStart.style.background = 'var(--tech-green-dk)'; };
      els.btnStart.onmouseout  = () => { els.btnStart.style.background = 'var(--tech-green)'; };
      
      saveDraft(draft);
      window.MRTS.offline.queueAction('time_pause', woId, { elapsedSeconds: timerSeconds });
      console.log('[v0] startTimer: paused at', timerSeconds, 'seconds');
      updateCompletionBlocker();
    }
  }

  function stopTimer() {
    if (!canMutateOrWarn()) return;
    if (timerSeconds === 0) {
      return;
    }
    
    // Clear interval if running
    if (timerRunning) {
      clearInterval(timerInterval);
      timerRunning = false;
    }
    
    // Finalize elapsed time
    const elapsedMs = timerSeconds * 1000;
    const elapsedFormatted = window.MRTS.fmtTime(elapsedMs);
    
    // Initialize time_logs array if needed
    if (!draft.time_logs) {
      draft.time_logs = [];
    }
    
    // Create time log entry with timestamp
    const now = new Date();
    const timeLogId = `tl_${Date.now()}_${Math.random().toString(16).slice(2)}`;
    const timeLogEntry = {
      id: timeLogId,
      labor_type: draft.timer.laborType || 'other',
      elapsed_ms: elapsedMs,
      elapsed_display: elapsedFormatted,
      created_at: now.toISOString(),
      created_at_display: now.toLocaleString('en-US', { 
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
      }),
      status: 'draft'
    };
    
    // Push to draft
    draft.time_logs.push(timeLogEntry);
    
    // Update draft.timer state
    draft.timer.running = false;
    draft.timer.startedAt = null;
    draft.timer.laborType = null;
    draft.timer.elapsedMs = 0;
    draft.timer.pausedMs = null;
    
    // Queue action and save draft BEFORE resetting UI
    window.MRTS.offline.queueAction('time_stop', woId, { 
      total_elapsed_ms: elapsedMs,
      labor_type: timeLogEntry.labor_type
    });
    saveDraft(draft);
    
    // Reset UI timer variables
    timerSeconds = 0;
    
    // Update timer display and state
    els.timerState.textContent = 'Not started';
    els.btnStart.textContent = 'START';
    els.btnStart.classList.remove('bg-yellow-500', 'hover:bg-yellow-600');
    els.btnStart.classList.add('bg-olfu-green', 'hover:bg-olfu-green-md');
    els.btnStop.disabled = true;
    
    // Update display
    updateTimerDisplay();
    
    // Render the time logs list
    renderTimeLogs();
    renderChecklistTimeLogs(); // keep checklist panel in sync
    if (typeof updateCompletionBadges === 'function') updateCompletionBadges();
    
    updateCompletionBlocker();
  }

  function renderTimeLogs() {
    const logs = draft.time_logs || [];
    const container   = document.getElementById('timeLogsList');
    const emptyState  = document.getElementById('laborEmptyState');
    const totalRow    = document.getElementById('timeTotalRow');
    const totalValue  = document.getElementById('timeTotalValue');
    const totalBadge  = document.getElementById('laborTotalBadge');

    if (!container) {
      console.log('[v0] renderTimeLogs: timeLogsList container not found');
      return;
    }

    // ── Empty state ──────────────────────────────────────────────
    if (!logs.length) {
      container.innerHTML = '';
      if (emptyState) emptyState.style.display = '';
      if (totalRow)   { totalRow.classList.add('hidden'); totalRow.style.display = 'none'; }
      if (totalBadge) totalBadge.classList.add('hidden');
      return;
    }

    // ── Hide empty state ─────────────────────────────────────────
    if (emptyState) emptyState.style.display = 'none';

    // ── Sort ─────────────────────────────────────────────────────
    const sortedLogs = [...logs];

    // ── Calculate total ──────────────────────────────────────────
    const totalMs = logs.reduce((sum, l) => sum + (l.elapsed_ms || 0), 0);
    const totalSec = Math.floor(totalMs / 1000);
    const th = String(Math.floor(totalSec / 3600)).padStart(2, '0');
    const tm = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
    const ts = String(totalSec % 60).padStart(2, '0');
    const totalFormatted = `${th}:${tm}:${ts}`;

    // ── Render entries ───────────────────────────────────────────
    container.innerHTML = sortedLogs.map((log) => {
      const elapsed = log.elapsed_display || window.MRTS.fmtTime(log.elapsed_ms);
      const when = log.created_at_display || new Date(log.created_at).toLocaleString('en-US', {
        month: '2-digit', day: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hour12: true
      });
      return `
        <div class="timeLog">
          <div class="timeLog__header">
            <span class="timeLog__type">${escapeHtml(log.labor_type || 'other')}</span>
            <span class="timeLog__time">${elapsed}</span>
          </div>
          <div class="timeLog__timestamp">${when}</div>
          <button class="timeLog__remove" type="button" data-remove-timelog="${log.id}" title="Remove">×</button>
        </div>
      `;
    }).join('');

    // ── Total row ────────────────────────────────────────────────
    if (totalRow) {
      totalRow.classList.remove('hidden');
      totalRow.style.display = 'flex';
    }
    if (totalValue)  totalValue.textContent  = totalFormatted;
    if (totalBadge)  {
      totalBadge.classList.remove('hidden');
      totalBadge.textContent = totalFormatted;
    }

    // ── Remove button listeners ──────────────────────────────────
    container.querySelectorAll('[data-remove-timelog]').forEach((btn) => {
      btn.addEventListener('click', () => {
        if (!canMutateOrWarn()) return;
        const id = btn.getAttribute('data-remove-timelog');
        draft.time_logs = draft.time_logs.filter((x) => x.id !== id);
        saveDraft(draft);
        window.MRTS.offline.queueAction('time_log_remove', woId, { id });
        renderTimeLogs();
      });
    });
  }

  // ── Sort button removed from Time Logs panel (moved to index filter bar) ──

  // ── Ratings Tab renderer ─────────────────────────────────────
  function renderRatingsTab() {
    const pane = document.getElementById('tab-ratings');
    if (!pane) return;

    // Pull latest satisfaction + feedback from the live wo object (updated by sync)
    const satisfaction = (wo && wo.signoff && wo.signoff.satisfaction != null)
      ? parseInt(wo.signoff.satisfaction, 10)
      : 0;
    const feedback = (wo && wo.signoff && wo.signoff.feedback)
      ? String(wo.signoff.feedback).trim()
      : '';

    const card = pane.querySelector('.tech-card');
    if (!card) return;

    const body = card.querySelector('.tech-card__body');
    if (!body) return;

    if (satisfaction >= 1 && satisfaction <= 5) {
      const stars = [1,2,3,4,5].map(s =>
        `<svg style="width:28px;height:28px;color:${s <= satisfaction ? '#f59e0b' : '#e5e7eb'};" fill="currentColor" viewBox="0 0 24 24">
          <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
        </svg>`
      ).join('');

      const feedbackHtml = feedback
        ? `<div style="padding:12px 14px;border-radius:8px;background:var(--tech-gray-50);border:1px solid var(--tech-gray-200);margin-top:12px;">
             <div style="font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--tech-gray-400);margin-bottom:6px;">Feedback</div>
             <p style="font-size:13px;color:var(--tech-gray-700);margin:0;line-height:1.6;">${escapeHtml(feedback)}</p>
           </div>`
        : '';

      body.innerHTML = `
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
          ${stars}
          <span style="font-size:13px;font-weight:600;color:var(--tech-gray-700);margin-left:4px;">${satisfaction} / 5</span>
        </div>
        ${feedbackHtml}`;
    } else {
      body.innerHTML = `
        <div style="padding:40px 20px;text-align:center;">
          <svg style="width:40px;height:40px;color:var(--tech-gray-200);margin:0 auto 12px;display:block;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
          </svg>
          <p style="font-size:13px;color:var(--tech-gray-400);font-style:italic;margin:0;">
            Ratings are submitted by the requester after work order review.<br>No rating has been submitted yet.
          </p>
        </div>`;
    }
  }

  function renderSignoff() {
    if (els.signatorySelect) {
      if (draft.signoff.signatoryUserId) {
        els.signatorySelect.value = String(draft.signoff.signatoryUserId);
      }
      // Update identity display
      const identityWrap = document.getElementById('signatoryIdentityWrap');
      const identityName = document.getElementById('signatoryIdentityName');
      if (identityWrap && identityName) {
        if (draft.signoff.signerName) {
          identityName.textContent = draft.signoff.signerName;
          identityWrap.style.display = '';
        } else {
          identityWrap.style.display = 'none';
        }
      }
    }
    updateSigStatus();
  }

  function updateSigStatus() {
    const dot  = els.sigStatus.querySelector('span:first-child');
    const text = els.sigStatus.querySelector('span:last-child');
    if (draft.signoff.signatureDataUrl) {
      if (dot)  { dot.style.background  = '#15803d'; }
      if (text) { text.textContent = 'Signature saved'; text.style.color = '#15803d'; }
    } else {
      if (dot)  { dot.style.background  = '#f87171'; }
      if (text) { text.textContent = 'Not signed'; text.style.color = '#6b7280'; }
    }
  }

  function updateCompletionBlocker() {
    const reasons = validateCompletion();
    if (reasons.length) {
      els.blocker.classList.remove('hidden');
      els.blocker.style.display = 'block';
      els.blocker.innerHTML = `<strong>Cannot complete yet:</strong><ul style="margin:8px 0 0 18px;list-style:disc;">${reasons.map(r=>`<li>${escapeHtml(r)}</li>`).join('')}</ul>`;
      return false;
    }
    els.blocker.classList.add('hidden');
    els.blocker.style.display = 'none';
    return true;
  }

function validateCompletion() {
  const reasons = [];
  
  // Use the live in-memory draft — it is always kept up to date by every
  // saveDraft() call. Calling loadDraft() here would return an empty object
  // (by design: page starts blank) and block completion incorrectly.
  const freshDraft = draft;
  
  // Debug: Log safety check status  
  const safetyItems = (wo && wo.safety) ? wo.safety : [];
  const safetyRequired = safetyItems.filter((it) => it.mandatory);
  const safetyDone = safetyRequired.every((it) => !!freshDraft.safety[it.id]);
  
  console.log('[v0] validateCompletion - Safety:', {
    totalSafety: safetyItems.length,
    required: safetyRequired.length,
    draftSafety: freshDraft.safety,
    requiredItems: safetyRequired.map(it => ({ id: it.id, done: !!freshDraft.safety[it.id] })),
    allDone: safetyDone
  });
  
  if (safetyRequired.length && !safetyDone) reasons.push('Complete all required safety checks');
  
  // Debug: Log checklist status
  const items = (wo && wo.checklist) ? wo.checklist : [];
  const required = items.filter((it) => it.required);
  const reqDone = required.every((it) => !!freshDraft.checklist[it.id]);
  
  console.log('[v0] validateCompletion - Checklist:', {
    totalChecklist: items.length,
    required: required.length,
    draftChecklist: freshDraft.checklist,
    requiredItems: required.map(it => ({ id: it.id, done: !!freshDraft.checklist[it.id] })),
    allDone: reqDone
  });
  
  if (required.length && !reqDone) reasons.push('Complete all required checklist items');

    const hasEvidence = (freshDraft.evidence.before.length + freshDraft.evidence.after.length) > 0;
    if (wo && wo.evidence_required && !hasEvidence) reasons.push('Add at least one photo/video evidence');

    const hasSig = !!freshDraft.signoff.signatureDataUrl;
    if (wo && wo.signature_required && !hasSig) reasons.push('Capture requester signature');

    // Check if time was tracked (either currently running or has logged entries)
    // Uses freshDraft to ensure we pick up latest time entries from localStorage
    const hasTimeLogs = (freshDraft.time_logs || []).length > 0;
    const hasTime = timerSeconds > 0 || hasTimeLogs;
    if (!hasTime) reasons.push('Start time tracking');

    return reasons;
  }

  // Wire actions
  els.beforeFiles.addEventListener('change', (e) => filesToEvidence(e.target.files, 'before'));
  els.afterFiles.addEventListener('change', (e) => filesToEvidence(e.target.files, 'after'));
  
  console.log('[v0] Setting up configFiles listener, element:', els.configFiles);
  els.configFiles.addEventListener('change', (e) => {
    console.log('[v0] configFiles change event fired!', e.target.files);
    filesToConfig(e.target.files);
  });

  // Note: Safety and Checklist handlers are attached in renderSafety() and renderChecklist()
  // since those functions dynamically render the lists after page load

  // Manual entry add
  els.btnAddPart && els.btnAddPart.addEventListener('click', () => {
    if (!canMutateOrWarn()) return;
    const partNumber = (els.partNumber.value || '').trim();
    const qty = Math.max(1, Number(els.partQty.value || 1));
    const serial = (els.partSerial.value || '').trim();
    if (!partNumber) return;
    // Merge with existing entry if same part number
    const existingIdx = draft.parts.findIndex(p => p.partNumber === partNumber && p.category === 'manual');
    if (existingIdx >= 0) {
      draft.parts[existingIdx].qty += qty;
    } else {
      const item = { id: `p_${Date.now()}_${Math.random().toString(16).slice(2)}`, partNumber, qty, serial, category: 'manual' };
      draft.parts.push(item);
    }
    els.partNumber.value = '';
    els.partQty.value = '1';
    els.partSerial.value = '';
    saveDraft(draft);
    window.MRTS.offline.queueAction('part_add', woId, item);
    renderParts();
  });

  // ── Browse-by-category parts picker ──────────────────────────
  (function () {
    // Live parts from DB injected by PHP via window.__PARTS_INVENTORY__
    const rawParts = window.__PARTS_INVENTORY__ || [];

    let activeCat    = 'all';
    let selectedPart = null;

    function renderChips() {
      const grid = document.getElementById('partsChipGrid');
      if (!grid) return;
      const items = activeCat === 'all'
        ? rawParts
        : rawParts.filter(p => p.cat === activeCat);

      if (!items.length) {
        grid.innerHTML = '<p style="font-size:12px;color:var(--tech-gray-400);font-style:italic;padding:8px 0;">No parts found. Run the seed script to populate inventory.</p>';
        return;
      }

      grid.innerHTML = items.map(p => {
        const on       = selectedPart && selectedPart.part_id === p.part_id;
        const out      = p.qty === 0;
        const low      = !out && p.qty <= p.reorder;
        const qtyColor = out ? '#dc2626' : low ? '#d97706' : '#6b7280';
        const qtyLabel = out ? '⊘ Out of stock' : (low ? '⚠ Low: ' + p.qty : 'Qty: ' + p.qty);
        return `<button type="button" class="part-chip${on ? ' part-chip--on' : ''}${out ? ' part-chip--disabled' : ''}"
                        onclick="window._pickPart(${p.part_id})"
                        ${out ? 'disabled title="Out of stock"' : ''}>
                  <div>
                    <div class="part-chip__label">${escapeHtml(p.name)}</div>
                    <div class="part-chip__sub">${escapeHtml(p.cat)}</div>
                    <div style="font-size:10px;color:${qtyColor};margin-top:2px;font-weight:600;">${qtyLabel}</div>
                  </div>
                </button>`;
      }).join('');
    }

    function renderPreview() {
      const prev = document.getElementById('partsSelPreview');
      const btn  = document.getElementById('btnAddBrowsePart');
      if (!prev || !btn) return;
      if (selectedPart) {
        const low = selectedPart.qty <= selectedPart.reorder;
        prev.innerHTML = `<span style="font-size:13px;font-weight:500;color:var(--tech-gray-700);">${escapeHtml(selectedPart.name)}</span>
                          <span style="margin-left:auto;font-size:10px;font-weight:500;padding:2px 8px;border-radius:999px;background:var(--tech-gray-100);color:var(--tech-gray-500);">${escapeHtml(selectedPart.cat)}</span>
                          ${low ? '<span style="font-size:10px;color:#d97706;font-weight:600;margin-left:6px;">⚠ Low stock</span>' : ''}`;
        btn.disabled = false;
        btn.style.background = 'var(--tech-green)';
        btn.style.cursor = 'pointer';
        btn.onmouseover = () => { btn.style.background = 'var(--tech-green-dk)'; };
        btn.onmouseout  = () => { btn.style.background = 'var(--tech-green)'; };
      } else {
        prev.innerHTML = '<span style="font-size:12px;font-style:italic;color:var(--tech-gray-400);">No part selected — tap a part above</span>';
        btn.disabled = true;
        btn.style.background = '#9ca3af';
        btn.style.cursor = 'not-allowed';
        btn.onmouseover = null;
        btn.onmouseout  = null;
      }
    }

    window._pickPart = function (partId) {
      const p = rawParts.find(x => x.part_id === partId);
      if (!p || p.qty === 0) return;
      selectedPart = (selectedPart && selectedPart.part_id === partId) ? null : p;
      renderChips();
      renderPreview();
    };

    window.setPartsCat = function (cat, el) {
      activeCat    = cat;
      selectedPart = null;
      document.querySelectorAll('.parts-cat-tab').forEach(t => t.classList.remove('parts-cat-tab--on'));
      if (el) el.classList.add('parts-cat-tab--on');
      renderChips();
      renderPreview();
    };

    window.setPartsMode = function (mode) {
      const browse = document.getElementById('partsPanelBrowse');
      const manual = document.getElementById('partsPanelManual');
      const btnB   = document.getElementById('partsModeBrowse');
      const btnM   = document.getElementById('partsModeManual');
      if (!browse || !manual) return;
      if (mode === 'browse') {
        browse.style.display = '';
        manual.style.display = 'none';
        if (btnB) { btnB.style.background = '#1a5c2a'; btnB.style.color = '#fff'; }
        if (btnM) { btnM.style.background = 'none';    btnM.style.color = 'var(--tech-gray-500)'; }
      } else {
        browse.style.display = 'none';
        manual.style.display = '';
        if (btnM) { btnM.style.background = '#1a5c2a'; btnM.style.color = '#fff'; }
        if (btnB) { btnB.style.background = 'none';    btnB.style.color = 'var(--tech-gray-500)'; }
      }
    };

    const btnAddBrowse = document.getElementById('btnAddBrowsePart');
    if (btnAddBrowse) {
      btnAddBrowse.addEventListener('click', async () => {
        if (!canMutateOrWarn()) return;
        if (!selectedPart) return;

        const qty    = Math.max(1, parseInt(document.getElementById('browsePartQty').value) || 1);
        const serial = (document.getElementById('browsePartSerial').value || '').trim();

        if (qty > selectedPart.qty) {
          showPartsToast('Only ' + selectedPart.qty + ' in stock for "' + selectedPart.name + '"', 'error');
          return;
        }

        const origText      = btnAddBrowse.textContent;
        btnAddBrowse.disabled     = true;
        btnAddBrowse.textContent  = 'Adding…';

        try {
          const res = await fetch(window.MRTS.APP_BASE + 'modules/technician/api/parts_use.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
              wo_id:         woId,
              part_id:       selectedPart.part_id,
              quantity_used: qty,
              serial_number: serial || null,
            }),
          });
          const data = await res.json();

          if (!data.success) {
            showPartsToast(data.error || 'Failed to record part usage', 'error');
            return;
          }

          // Update local qty so chips re-render without a page reload
          const local = rawParts.find(p => p.part_id === selectedPart.part_id);
          if (local) local.qty = data.current_stock;

          // Push into draft.parts — merge with existing entry if same part
          const existingIdx = draft.parts.findIndex(p =>
            p.partNumber === selectedPart.name && p.category === selectedPart.cat
          );
          if (existingIdx >= 0) {
            draft.parts[existingIdx].qty += qty;
          } else {
            draft.parts.push({
              id:         'db_' + data.usage_id,
              partNumber: selectedPart.name,
              qty:        qty,
              serial:     serial || '',
              category:   selectedPart.cat,
            });
          }
          saveDraft(draft);

          if (data.low_stock_alert) {
            showPartsToast('⚠ Low stock: "' + selectedPart.name + '" is now at ' + data.current_stock + ' (reorder at ' + data.reorder_level + ')', 'warning');
          } else {
            showPartsToast('Added ' + qty + '× ' + selectedPart.name, 'success');
          }

          selectedPart = null;
          document.getElementById('browsePartQty').value   = '1';
          document.getElementById('browsePartSerial').value = '';
          renderChips();
          renderPreview();
          renderParts();
        } catch (err) {
          showPartsToast('Network error — part not recorded', 'error');
        } finally {
          btnAddBrowse.disabled    = false;
          btnAddBrowse.textContent = origText;
        }
      });
    }

    renderChips();
    renderPreview();
  })();

  function showPartsToast(message, type) {
    const colors = { success: '#1a5c2a', warning: '#b45309', error: '#dc2626', info: '#1d4ed8' };
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;background:' +
      (colors[type] || colors.info) +
      ';color:#fff;padding:10px 16px;border-radius:8px;font-size:13px;' +
      'box-shadow:0 4px 12px rgba(0,0,0,.2);max-width:340px;line-height:1.4;';
    t.textContent = message;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 4500);
  }

  // Verify buttons exist
  if (!els.btnStart) console.error('[v0] btnStart element not found!');
  if (!els.btnStop) console.error('[v0] btnStop element not found!');
  
  if (els.btnStart) {
    els.btnStart.addEventListener('click', () => {
      console.log('[v0] btnStart clicked');
      startTimer();
    });
  }
  
  if (els.btnStop) {
    els.btnStop.addEventListener('click', () => {
      console.log('[v0] btnStop clicked');
      stopTimer();
    });
  }

  // Add Note
  els.btnAddNote.addEventListener('click', () => {
    if (!canMutateOrWarn()) return;
    const title = (els.noteTitle.value || '').trim();
    const text = (els.noteText.value || '').trim();
    if (!text) {
      MRTS.modal.toast('Please enter a note', { type: 'warning' });
      return;
    }
    const noteId = `n_${Date.now()}_${Math.random().toString(16).slice(2)}`;
    draft.notes.push({
      id: noteId,
      title: title,
      text: text,
      ts: Date.now(),
      source: 'local'
    });
    els.noteTitle.value = '';
    els.noteText.value = '';
    saveDraft(draft);
    window.MRTS.offline.queueAction('note_add', woId, { title, text });
    renderNotes();
    updateCompletionBlocker();
  });

  // Sign-off — wire all fields
  function wireSignoffField(el, key) {
    if (!el) return;
    el.addEventListener('input', () => {
      if (!isEditableNow) {
        el.value = draft.signoff[key] || '';
        return;
      }
      draft.signoff[key] = el.value;
      saveDraft(draft);
      if (key === 'signerName') renderChecklistTimeLogs(); // update checklist panel live
    });
  }
  wireSignoffField(els.signatorySelect, 'signatoryUserId');

  sig = window.MRTS.signature.setup(els.sigCanvas);
  els.btnClearSig.addEventListener('click', () => {
    if (!canMutateOrWarn()) return;
    sig.clear();
    draft.signoff.signatureDataUrl = null;
    saveDraft(draft);
    updateSigStatus();
    window.MRTS.offline.queueAction('signature_clear', woId, {});
    updateCompletionBlocker();
    renderChecklistTimeLogs(); // refresh signature status in checklist panel
    if (typeof updateCompletionBadges === 'function') updateCompletionBadges();
  });
  els.btnSaveSig.addEventListener('click', () => {
    if (!canMutateOrWarn()) return;
    if (sig.isBlank()) {
      MRTS.modal.toast('Please draw your signature first', { type: 'warning' });
      return;
    }
    // Derive signer name from the signatory dropdown
    const selectedOption = els.signatorySelect && els.signatorySelect.selectedOptions[0];
    const nameTrim = (selectedOption && selectedOption.dataset.name ? selectedOption.dataset.name : '').trim()
      || (draft.signoff.signerName || '').trim();
    const userIdVal = selectedOption && selectedOption.value ? parseInt(selectedOption.value, 10) : null;
    if (!nameTrim || !userIdVal) {
      MRTS.modal.toast('Select an authorized signatory before saving the signature', { type: 'warning', duration: 4500 });
      return;
    }
    draft.signoff.signerName = nameTrim;
    draft.signoff.signatoryUserId = userIdVal;
    draft.signoff.signatureDataUrl = sig.toDataUrl();
    saveDraft(draft);
    updateSigStatus();
    // Update identity display
    const identityWrap = document.getElementById('signatoryIdentityWrap');
    const identityName = document.getElementById('signatoryIdentityName');
    if (identityWrap && identityName) {
      identityName.textContent = nameTrim;
      identityWrap.style.display = '';
    }
    const woIdNum = parseInt(String(woId), 10);
    window.MRTS.offline.queueAction('signature_save', woIdNum > 0 ? woIdNum : woId, {
      signature_data_url: draft.signoff.signatureDataUrl,
      signer_name: nameTrim,
      signed_by_user_id: userIdVal,
    });
    updateCompletionBlocker();
    renderChecklistTimeLogs(); // refresh signature status in checklist panel
    if (typeof updateCompletionBadges === 'function') updateCompletionBadges();
  });

  els.btnSaveDraft.addEventListener('click', () => {
    if (!canMutateOrWarn()) return;
    saveDraft(draft);
    window.MRTS.offline.queueAction('draft_save', woId, {});
    MRTS.modal.toast('Draft saved (local + queued)', { type: 'success' });
  });

  // Signatory dropdown change handler
  if (els.signatorySelect) {
    els.signatorySelect.addEventListener('change', () => {
      if (!canMutateOrWarn()) {
        // Revert to previously saved value
        els.signatorySelect.value = draft.signoff.signatoryUserId ? String(draft.signoff.signatoryUserId) : '';
        return;
      }
      const selectedOption = els.signatorySelect.selectedOptions[0];
      const userId = selectedOption && selectedOption.value ? parseInt(selectedOption.value, 10) : null;
      const name = (selectedOption && selectedOption.dataset.name) ? selectedOption.dataset.name.trim() : '';
      draft.signoff.signatoryUserId = userId;
      draft.signoff.signerName = name;
      // Update identity display
      const identityWrap = document.getElementById('signatoryIdentityWrap');
      const identityName = document.getElementById('signatoryIdentityName');
      if (identityWrap && identityName) {
        if (name) {
          identityName.textContent = name;
          identityWrap.style.display = '';
        } else {
          identityWrap.style.display = 'none';
        }
      }
      saveDraft(draft);
      renderChecklistTimeLogs();
    });
  }

  els.btnComplete.addEventListener('click', async () => {
    if (!canMutateOrWarn()) return;
    const ok = updateCompletionBlocker();
    if (!ok) return;
    
    // Show loading state
    els.btnComplete.disabled = true;
    const originalText = els.btnComplete.innerHTML;
    els.btnComplete.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg> Completing...';
    
    try {
      // Flush queued offline actions (notes, evidence uploads, live time
      // events) BEFORE the completion call so they actually persist server-
      // side. Without this, notes and photos sit in localStorage forever.
      if (window.MRTS.offline && window.MRTS.offline.queueCount() > 0) {
        els.btnComplete.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/></svg> Syncing pending...';
        try {
          const syncRes = await window.MRTS.offline.syncNow();
          const syncErrs = (syncRes && syncRes.errors) || [];
          if (syncErrs.length) {
            const detail = syncErrs.slice(0, 3).map(e => '- ' + (e.action || '?') + ': ' + (e.error || 'failed')).join('\n');
            const proceed = await MRTS.modal.confirm(
              'Some pending changes failed to sync (' + syncErrs.length + '):\n\n' + detail +
              '\n\nComplete the work order anyway?',
              { title: 'Sync Issues Detected', okLabel: 'Complete Anyway', danger: true }
            );
            if (!proceed) {
              els.btnComplete.disabled = false;
              els.btnComplete.innerHTML = originalText;
              return;
            }
          }
        } catch (syncErr) {
          els.btnComplete.disabled = false;
          els.btnComplete.innerHTML = originalText;
          await MRTS.modal.alert('Could not sync pending changes — check your connection and try again.\n\n' + (syncErr.message || syncErr), { type: 'error', title: 'Sync Failed' });
          return;
        }
        els.btnComplete.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/></svg> Completing...';
      }

      // Gather time logs and calculate total
      const totalTimeMs = draft.timer.elapsedMs + (draft.timer.running ? (Date.now() - draft.timer.startedAt) : 0);
      const parsedWoId = parseInt(woId, 10);

      // Merge localStorage draft into the in-memory draft for the completion payload.
      // This ensures safety/checklist ticks saved in a previous session (before a refresh)
      // are included even if the technician refreshed the page before completing.
      const storedDraft = loadSyncedDraft();
      const mergedChecklist = Object.assign({}, storedDraft.checklist || {}, draft.checklist || {});
      const mergedSafety    = Object.assign({}, storedDraft.safety    || {}, draft.safety    || {});
      const mergedTimeLogs  = draft.time_logs && draft.time_logs.length > 0
        ? draft.time_logs
        : (storedDraft.time_logs || []);

      const completionPayload = {
        wo_id:               parsedWoId,
        checklist:           mergedChecklist,
        safety:              mergedSafety,
        time_logs:           mergedTimeLogs,
        total_time_ms:       totalTimeMs,
        signer_name:         draft.signoff.signerName         || storedDraft.signoff?.signerName         || '',
        signed_by_user_id:   draft.signoff.signatoryUserId    || storedDraft.signoff?.signatoryUserId    || null,
        signature_data_url:  draft.signoff.signatureDataUrl   || storedDraft.signoff?.signatureDataUrl   || '',
        resolution_notes:    draft.signoff.resolutionNotes    || storedDraft.signoff?.resolutionNotes    || '',
      };

      // Final completion must persist immediately on server; no prototype queue-only completion.
      await window.MRTS.api('/modules/technician/api/complete_work_order.php', {
        method: 'POST',
        body: JSON.stringify(completionPayload),
      });

      await MRTS.modal.alert('Work order completed and saved successfully.', { type: 'success', title: 'Completed!' });
      window.location.href = window.MRTS.APP_BASE + '/modules/technician/index.php';
    } catch (e) {
      els.btnComplete.disabled = false;
      els.btnComplete.innerHTML = originalText;
      await MRTS.modal.alert('Error: ' + (e.message || 'Failed to complete work order. Please try again.'), { type: 'error', title: 'Completion Failed' });
    }
  });

  // Load WO from PHP data
  async function load() {
    // #region agent log
    fetch('http://127.0.0.1:7640/ingest/480ae408-6ba6-451f-aaef-9603744d9d28',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'30aee9'},body:JSON.stringify({sessionId:'30aee9',runId:'pre-fix',hypothesisId:'H_WO_DATA',location:'public/assets/js/technician/workorder.js:562',message:'WO bootstrap data presence',data:{has_WO_DATA:!!window.__WO_DATA__,has_WO_ID:typeof window.__WO_ID__!=='undefined',has_MRTS:!!window.MRTS,app_base:(window.MRTS&&window.MRTS.APP_BASE)||null},timestamp:Date.now()})}).catch(()=>{});
    // #endregion
    wo = window.__WO_DATA__;
    if (!wo) {
      await MRTS.modal.alert('Work order data not available.', { type: 'error' });
      window.location.href = window.MRTS.APP_BASE + '/modules/technician/index.php';
      return;
    }

    console.log('[v0] WO loaded from __WO_DATA__:', {
      safety_count: (wo.safety || []).length,
      safety: wo.safety,
      checklist_count: (wo.checklist || []).length,
      checklist: wo.checklist,
      time_logs_count: (wo.time_logs || []).length
    });
    isEditableNow = !!wo.can_execute_now;

    // For resolved/closed work orders, seed the draft from server data immediately
    // so the technician sees their full work history on page load without clicking Sync.
    // For in-progress work, the draft stays blank — Sync restores it.
    const isResolved = (wo.status === 'resolved' || wo.status === 'closed');
    if (isResolved) {
      // Safety
      if (wo.safety && wo.safety.length > 0) {
        wo.safety.forEach((item) => {
          const key = String(item.id || item.safety_id || 0);
          draft.safety[key] = item.is_done === true || item.is_done === 1;
        });
      }
      // Checklist
      if (wo.checklist && wo.checklist.length > 0) {
        wo.checklist.forEach((item) => {
          draft.checklist[item.id] = item.is_done === true || item.is_done === 1;
        });
      }
      // Time logs
      if (wo.time_logs && wo.time_logs.length > 0) {
        const serverStops = wo.time_logs.filter((r) => (r.action || '') === 'stop');
        draft.time_logs = serverStops.map((row) => {
          const elapsed = parseInt(row.elapsed_ms, 10) || 0;
          const logId = row.log_id != null ? row.log_id : row.id;
          return {
            id: logId != null ? `srv_${logId}` : `srv_ms_${elapsed}`,
            labor_type: row.labor_type || 'other',
            elapsed_ms: elapsed,
            elapsed_display: window.MRTS.fmtTime(elapsed),
            created_at: row.logged_at
              ? new Date(String(row.logged_at).replace(' ', 'T')).toISOString()
              : new Date().toISOString(),
            created_at_display: row.logged_at || '',
            status: 'synced',
            source: 'server',
          };
        });
      }
      // Signoff
      if (wo.signoff && (wo.signoff.signer_name || wo.signoff.signature_path)) {
        if (wo.signoff.signer_name) draft.signoff.signerName = wo.signoff.signer_name;
        if (wo.signoff.signed_by_user_id) draft.signoff.signatoryUserId = wo.signoff.signed_by_user_id;
        const p = wo.signoff.signature_path || '';
        if (p && p !== 'data:inline') draft.signoff.signatureDataUrl = p;
      }
    }

    // Hydrate notes and before/after photos from the server so any user
    // viewing the WO sees them — not just the one whose localStorage holds the
    // original draft. Tag with source:'server' so we can drop and re-hydrate
    // on subsequent loads. Dedupe by text (notes) / serverUrl (photos).
    draft.notes = (draft.notes || []).filter((n) => n.source !== 'server');
    if (wo.notes && wo.notes.length > 0) {
      const localTexts = new Set(draft.notes.map((n) => (n.text || '').trim()));
      wo.notes.forEach((n) => {
        const raw = (n.note_text || '').trim();
        if (!raw || localTexts.has(raw)) return;
        // Parse [title] prefix stored by the sync handler: "[title] text"
        let noteTitle = '';
        let noteText  = raw;
        const titleMatch = raw.match(/^\[(.+?)\]\s*([\s\S]*)$/);
        if (titleMatch) {
          noteTitle = titleMatch[1];
          noteText  = titleMatch[2];
        }
        draft.notes.push({
          id: 'srv_' + n.note_id,
          title: noteTitle,
          text: noteText,
          ts: n.created_at ? new Date(n.created_at).getTime() : Date.now(),
          source: 'server',
        });
      });
    }
    if (!draft.evidence) draft.evidence = { before: [], after: [] };
    ['before', 'after'].forEach((side) => {
      if (!Array.isArray(draft.evidence[side])) draft.evidence[side] = [];
      draft.evidence[side] = draft.evidence[side].filter((m) => m.source !== 'server');
    });
    if (wo.media && wo.media.length > 0) {
      const localUrls = {
        before: new Set(draft.evidence.before.map((m) => m.serverUrl).filter(Boolean)),
        after:  new Set(draft.evidence.after .map((m) => m.serverUrl).filter(Boolean)),
      };
      wo.media.forEach((m) => {
        const side = m.media_type === 'photo_before' ? 'before'
                   : m.media_type === 'photo_after'  ? 'after'
                   : null;
        if (!side || !m.file_path) return;
        if (localUrls[side].has(m.file_path)) return;
        draft.evidence[side].push({
          id: 'srv_' + m.media_id,
          kind: 'image',
          name: m.caption || ('image_' + m.media_id),
          serverUrl: m.file_path,
          state: 'synced',
          source: 'server',
        });
      });
    }

    // Hydrate server time logs + sign-off into draft so checklist auto-rows match DB after resolve/reload.
    mergeServerStateIntoDraft({
      success: true,
      time_logs: wo.time_logs || [],
      signoff:
        wo.signoff &&
        ((wo.signoff.signature_path && wo.signoff.signature_path !== 'data:inline') ||
          String(wo.signoff.signer_name || '').trim())
          ? {
              signer_name: wo.signoff.signer_name || '',
              signature_path: wo.signoff.signature_path || '',
              satisfaction:
                wo.signoff.satisfaction != null && wo.signoff.satisfaction !== ''
                  ? parseInt(String(wo.signoff.satisfaction), 10)
                  : null,
              feedback: wo.signoff.feedback || '',
            }
          : null,
      checklist: wo.checklist || [],
    });

    // NOTE: Do NOT call saveDraft(draft) here on page load.
    // The draft starts empty by design. Saving here would overwrite the
    // localStorage draft with blank data, destroying everything Sync relies on.

    // Migrate old Base64 dataURLs to IndexedDB on first load
    if (!migrationDone) {
      draft = await migrateDraftToIndexedDB(draft);
      migrationDone = true;
    }

    console.log('[v0] Draft loaded before render:', {
      safety: draft.safety,
      checklist: draft.checklist,
      time_logs: draft.time_logs
    });

    renderHeader();
    renderSafety();
    renderChecklist();
    renderNotes();
    await loadBlobUrlsForEvidence();
    await loadBlobUrlsForConfig();
    renderEvidence();
    renderConfig();
    renderParts();
    renderSignoff();
    renderRatingsTab();
    // Note: timerSeconds, timerRunning, timerInterval are declared at module scope (line 549-551)
    renderTimeLogs();
    updateTimerDisplay();
    applyReadOnlyState();
    updateCompletionBlocker();
    if (typeof updateCompletionBadges === 'function') updateCompletionBadges();

    // For resolved/closed work orders, auto-fetch the latest server state so the
    // technician sees their full work history without needing to click Sync.
    if (isResolved && window.MRTS && window.MRTS.api) {
      try {
        const parsedWoId = parseInt(String(woId), 10);
        if (parsedWoId > 0) {
          const state = await window.MRTS.api(
            'modules/technician/api/sync.php?action=get_state&wo_id=' + parsedWoId,
            { method: 'GET' }
          );
          if (state && state.success) {
            // Seed safety from server
            if (Array.isArray(state.safety) && state.safety.length > 0) {
              state.safety.forEach((s) => {
                const key = String(s.id || s.safety_id || 0);
                draft.safety[key] = !!(s.is_done === true || s.is_done === 1 || s.is_done === '1');
              });
            }
            // Seed checklist from server
            if (Array.isArray(state.checklist) && state.checklist.length > 0) {
              state.checklist.forEach((item) => {
                draft.checklist[item.id] = !!(item.is_done === true || item.is_done === 1 || item.is_done === '1');
              });
            }
            // Merge signoff + time logs
            mergeServerStateIntoDraft(state);
            // Re-render with fresh data
            renderSafety();
            renderChecklist();
            renderTimeLogs();
            renderSignoff();
            renderRatingsTab();
            if (typeof updateCompletionBadges === 'function') updateCompletionBadges();
          }
        }
      } catch (e) {
        console.warn('[v0] Auto-sync for resolved WO failed:', e);
      }
    }
  }

  /** Repair legacy/half-filled signature_save rows before POST (DOM + draft win over stale queue data). */
  window.enrichOfflineQueueBeforeSync = function (queueItems) {
    if (!Array.isArray(queueItems)) return queueItems;
    const wid = parseInt(String(woId), 10);
    const selectedOption = els.signatorySelect && els.signatorySelect.selectedOptions[0];
    const name = (selectedOption && selectedOption.dataset.name ? selectedOption.dataset.name : '').trim()
      || String(draft.signoff.signerName || '').trim();
    const userIdVal = (selectedOption && selectedOption.value) ? parseInt(selectedOption.value, 10) : (draft.signoff.signatoryUserId || null);
    const sigUrl = draft.signoff.signatureDataUrl || '';
    return queueItems.map((it) => {
      const actionType = it.type || it.action || '';
      if (actionType !== 'signature_save') return it;
      const data = { ...(it.data || {}) };
      if (name) data.signer_name = name;
      if (userIdVal) data.signed_by_user_id = userIdVal;
      if (sigUrl && !String(data.signature_data_url || '').trim()) data.signature_data_url = sigUrl;
      let workOrderId = it.workOrderId ?? it.wo_id;
      const n = parseInt(String(workOrderId), 10);
      if (n > 0) workOrderId = n;
      else if (wid > 0) workOrderId = wid;
      const meta = { ...(it.meta || {}) };
      if (meta.hasBlob && !meta.blobId) delete meta.hasBlob;
      return { ...it, workOrderId, data, meta };
    });
  };

  load();

  /** Merge GET get_state payload into live draft + wo checklist flags (after sync push). */
  function mergeServerStateIntoDraft(state) {
    if (!state || !state.success) return;

    if (Array.isArray(state.time_logs) && state.time_logs.length) {
      const serverStops = state.time_logs.filter((r) => (r.action || '') === 'stop');
      const keyOf = (l) =>
        `${l.labor_type || 'other'}|${l.elapsed_ms}|${l.created_at_display || ''}|${String(l.id || '')}`;
      const fromServer = serverStops.map((row) => {
        const elapsed = parseInt(row.elapsed_ms, 10) || 0;
        const logId = row.log_id != null ? row.log_id : row.id;
        return {
          id: logId != null ? `srv_${logId}` : `srv_ms_${elapsed}_${row.logged_at || ''}`,
          labor_type: row.labor_type || 'other',
          elapsed_ms: elapsed,
          elapsed_display: window.MRTS.fmtTime(elapsed),
          created_at: row.logged_at
            ? new Date(String(row.logged_at).replace(' ', 'T')).toISOString()
            : new Date().toISOString(),
          created_at_display: row.logged_at || '',
          status: 'synced',
          source: 'server',
        };
      });
      const serverKeys = new Set(fromServer.map(keyOf));
      const locals = draft.time_logs || [];
      const merged = fromServer.slice();
      for (const loc of locals) {
        if (String(loc.id || '').startsWith('srv_')) {
          if (!merged.some((m) => m.id === loc.id)) merged.push(loc);
          continue;
        }
        if (!serverKeys.has(keyOf(loc))) merged.push(loc);
      }
      draft.time_logs = merged;
    }

    // Merge signoff — handle both signature updates and rating-only updates
    if (state.signoff) {
      // Update signature fields if present
      if (state.signoff.signer_name) draft.signoff.signerName = state.signoff.signer_name;
      if (state.signoff.signed_by_user_id) draft.signoff.signatoryUserId = state.signoff.signed_by_user_id;
      const p = state.signoff.signature_path || '';
      if (p && p !== 'data:inline') draft.signoff.signatureDataUrl = p;

      // Always update wo.signoff with latest satisfaction + feedback from server
      // so renderRatingsTab() shows the requester's rating after Sync
      if (wo) {
        if (!wo.signoff) wo.signoff = {};
        wo.signoff.satisfaction = state.signoff.satisfaction != null
          ? (parseInt(String(state.signoff.satisfaction), 10) || null)
          : wo.signoff.satisfaction;
        wo.signoff.feedback = state.signoff.feedback != null
          ? state.signoff.feedback
          : wo.signoff.feedback;
      }
      renderRatingsTab();
    }

    if (wo && Array.isArray(state.checklist) && wo.checklist && wo.checklist.length) {      const byId = new Map(state.checklist.map((it) => [it.id, it]));
      wo.checklist.forEach((item) => {
        const st = byId.get(item.id);
        if (st) item.is_done = !!(st.is_done === true || st.is_done === 1 || st.is_done === '1');
      });
    }
  }

  // Called when the user clicks Sync. Restores the previously saved draft
  // from localStorage and re-renders all UI sections so the technician
  // sees their work back after a page refresh.
  window.restoreSyncedDraft = async function() {
    // Step 1: Load stored draft from localStorage
    const stored = loadSyncedDraft();

    if (!stored || Object.keys(stored).length === 0) {
      console.log('[v0] restoreSyncedDraft: no stored draft found');
      return;
    }

    // Step 2: Assign to the live draft variable
    draft = stored;

    const parsedWoId = parseInt(String(woId), 10);
    if (parsedWoId > 0 && window.MRTS && window.MRTS.api && window.MRTS.offline && window.MRTS.offline.isReallyOnline) {
      try {
        if (await window.MRTS.offline.isReallyOnline()) {
          const state = await window.MRTS.api(
            'modules/technician/api/sync.php?action=get_state&wo_id=' + parsedWoId,
            { method: 'GET' }
          );
          mergeServerStateIntoDraft(state);
          saveDraft(draft);
        }
      } catch (e) {
        console.warn('[v0] restoreSyncedDraft: get_state failed', e);
      }
    }

    // Step 3: Migrate any old Base64 dataURLs to IndexedDB blobs
    if (!migrationDone) {
      draft = await migrateDraftToIndexedDB(draft);
      migrationDone = true;
    }

    // Step 4: Fill in any safety/checklist items that exist on the server
    // but were never explicitly ticked (undefined in the stored draft).
    // Items the user DID interact with are already in the draft and kept as-is.
    if (wo && wo.safety) {
      wo.safety.forEach((item) => {
        const key = String(item.id || item.safety_id || 0);
        if (!draft.safety[key]) {
          draft.safety[key] = (item.is_done === true || item.is_done === 1 || item.is_done === "1");
        }
      });
    }
    if (wo && wo.checklist) {
      wo.checklist.forEach((item) => {
        // Prioritize server status if local draft is missing or false
        if (!draft.checklist[item.id]) {
          draft.checklist[item.id] = (item.is_done === true || item.is_done === 1 || item.is_done === "1");
        }
      });
    }

    // Hydrate notes and before/after photos from server (same as load())
    draft.notes = (draft.notes || []).filter((n) => n.source !== 'server');
    if (wo && wo.notes && wo.notes.length > 0) {
      const localTexts = new Set(draft.notes.map((n) => (n.text || '').trim()));
      wo.notes.forEach((n) => {
        const raw = (n.note_text || '').trim();
        if (!raw || localTexts.has(raw)) return;
        let noteTitle = '';
        let noteText  = raw;
        const titleMatch = raw.match(/^\[(.+?)\]\s*([\s\S]*)$/);
        if (titleMatch) {
          noteTitle = titleMatch[1];
          noteText  = titleMatch[2];
        }
        draft.notes.push({
          id: 'srv_' + n.note_id,
          title: noteTitle,
          text: noteText,
          ts: n.created_at ? new Date(n.created_at).getTime() : Date.now(),
          source: 'server',
        });
      });
    }
    if (!draft.evidence) draft.evidence = { before: [], after: [] };
    ['before', 'after'].forEach((side) => {
      if (!Array.isArray(draft.evidence[side])) draft.evidence[side] = [];
      draft.evidence[side] = draft.evidence[side].filter((m) => m.source !== 'server');
    });
    if (wo && wo.media && wo.media.length > 0) {
      const localUrls = {
        before: new Set(draft.evidence.before.map((m) => m.serverUrl).filter(Boolean)),
        after:  new Set(draft.evidence.after .map((m) => m.serverUrl).filter(Boolean)),
      };
      wo.media.forEach((m) => {
        const side = m.media_type === 'photo_before' ? 'before'
                   : m.media_type === 'photo_after'  ? 'after'
                   : null;
        if (!side || !m.file_path) return;
        if (localUrls[side].has(m.file_path)) return;
        draft.evidence[side].push({
          id: 'srv_' + m.media_id,
          kind: 'image',
          name: m.caption || ('image_' + m.media_id),
          serverUrl: m.file_path,
          state: 'synced',
          source: 'server',
        });
      });
    }

    // Step 5: Restore timer state from draft
    if (draft.timer) {
      timerSeconds = Math.floor((draft.timer.elapsedMs || 0) / 1000);
      if (draft.timer.running && draft.timer.startedAt) {
        // Add elapsed time since the timer was started (page was refreshed mid-run)
        const extra = Math.floor((Date.now() - draft.timer.startedAt) / 1000);
        timerSeconds += extra;
        if (!timerRunning) {
          timerRunning = true;
          timerInterval = setInterval(() => {
            timerSeconds++;
            updateTimerDisplay();
          }, 1000);
        }
        if (els.timerState) els.timerState.textContent = 'Running';
        if (els.btnStart)  els.btnStart.textContent   = 'PAUSE';
        if (els.btnStop)   els.btnStop.disabled        = false;
      } else {
        // Timer was paused or stopped — just show the elapsed time
        if (timerRunning && timerInterval) {
          clearInterval(timerInterval);
          timerRunning = false;
          timerInterval = null;
        }
      }
    }

    // Step 6: Re-render every UI section
    renderSafety();
    renderChecklist();
    renderNotes();
    await loadBlobUrlsForEvidence();
    await loadBlobUrlsForConfig();
    renderEvidence();
    renderConfig();
    renderParts();
    renderSignoff();
    renderRatingsTab();
    renderTimeLogs();
    updateTimerDisplay();
    updateCompletionBlocker();
    if (typeof updateCompletionBadges === 'function') updateCompletionBadges();

    console.log('[v0] restoreSyncedDraft: UI restored', {
      safety:   Object.keys(draft.safety).length,
      checklist:Object.keys(draft.checklist).length,
      notes:    draft.notes.length,
      parts:    draft.parts.length,
      time_logs:draft.time_logs.length,
    });
  };
  
  // Expose debug utilities to window for testing
  window.timerDebug = {
    getState: () => ({
      timerSeconds,
      timerRunning,
      draftTimer: draft?.timer,
      timeLogs: draft?.time_logs?.length
    }),
    testStop: () => stopTimer(),
    testStart: () => startTimer(),
    getDraft: () => draft
  };
  console.log('[v0] Timer debug utilities available at window.timerDebug');

  // ── Queue sanitizer ──────────────────────────────────────────
  // Remove stale malformed queue items that can never succeed.
  // Runs once on page load so they don't block the Complete flow.
  (function sanitizeQueue() {
    try {
      const LS_KEY = 'mrtsp.queue.v1';
      const raw = JSON.parse(localStorage.getItem(LS_KEY) || '[]');
      if (!Array.isArray(raw) || !raw.length) return;

      const clean = raw.filter((item) => {
        const type = item.type || item.action || '';
        const data = item.data || {};
        const wid  = parseInt(String(item.workOrderId || item.wo_id || 0), 10);

        // Drop items with no valid work order ID
        if (!wid) return false;

        // Drop safety_update with safetyId = 0 or undefined
        if (type === 'safety_update') {
          const sid = parseInt(String(data.safetyId || data.safety_id || data.id || 0), 10);
          if (!sid) return false;
        }

        // Drop checklist_update with itemId = 0 or undefined
        if (type === 'checklist_update') {
          const iid = parseInt(String(data.itemId || data.item_id || data.id || 0), 10);
          if (!iid) return false;
        }

        return true;
      });

      if (clean.length !== raw.length) {
        localStorage.setItem(LS_KEY, JSON.stringify(clean));
        console.log('[v0] Queue sanitizer: removed', raw.length - clean.length, 'malformed item(s)');
      }
    } catch (e) {
      console.warn('[v0] Queue sanitizer failed:', e);
    }
  })();
});
