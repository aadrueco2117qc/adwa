/**
 * MRTS Custom Modal — replaces all native alert() / confirm() dialogs
 * with styled, accessible overlays that match the Technician Ops design.
 *
 * API:
 *   MRTS.modal.alert(message, { title, type })          → Promise<void>
 *   MRTS.modal.confirm(message, { title, type })        → Promise<boolean>
 *   MRTS.modal.toast(message, { type, duration })       → void
 */
(function () {
  'use strict';

  /* ── CSS injected once ─────────────────────────────────────────────────── */
  const STYLE_ID = 'mrts-modal-styles';
  if (!document.getElementById(STYLE_ID)) {
    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = `
      /* ── Overlay ── */
      .mrts-overlay {
        position: fixed; inset: 0; z-index: 99990;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(3px);
        display: flex; align-items: center; justify-content: center;
        padding: 16px;
        animation: mrts-fade-in 0.15s ease;
      }
      @keyframes mrts-fade-in  { from { opacity: 0; } to { opacity: 1; } }
      @keyframes mrts-slide-up { from { opacity: 0; transform: translateY(12px) scale(.97); } to { opacity: 1; transform: translateY(0) scale(1); } }

      /* ── Dialog card ── */
      .mrts-dialog {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 20px 60px rgba(0,0,0,.18), 0 4px 16px rgba(0,0,0,.08);
        width: 100%; max-width: 400px;
        overflow: hidden;
        animation: mrts-slide-up 0.18s cubic-bezier(.34,1.56,.64,1);
        font-family: system-ui, -apple-system, sans-serif;
      }

      /* ── Icon strip ── */
      .mrts-dialog__icon-strip {
        display: flex; align-items: center; justify-content: center;
        padding: 24px 24px 0;
      }
      .mrts-dialog__icon {
        width: 48px; height: 48px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
      }
      .mrts-dialog__icon svg { width: 24px; height: 24px; }

      /* type colours */
      .mrts-icon--info    { background: #eff6ff; color: #2563eb; }
      .mrts-icon--success { background: #f0fdf4; color: #16a34a; }
      .mrts-icon--warning { background: #fffbeb; color: #d97706; }
      .mrts-icon--error   { background: #fef2f2; color: #dc2626; }
      .mrts-icon--confirm { background: #f0fdf4; color: #1a5c2a; }

      /* ── Body ── */
      .mrts-dialog__body {
        padding: 16px 24px 20px;
        text-align: center;
      }
      .mrts-dialog__title {
        font-size: 15px; font-weight: 700; color: #111827;
        margin: 0 0 6px; line-height: 1.3;
      }
      .mrts-dialog__message {
        font-size: 13.5px; color: #4b5563; line-height: 1.55;
        margin: 0; white-space: pre-wrap; word-break: break-word;
      }

      /* ── Footer ── */
      .mrts-dialog__footer {
        display: flex; gap: 8px; justify-content: center;
        padding: 0 24px 20px;
      }
      .mrts-dialog__footer--split { justify-content: stretch; }
      .mrts-dialog__footer--split .mrts-btn { flex: 1; }

      /* ── Buttons ── */
      .mrts-btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 6px; padding: 9px 20px;
        border-radius: 8px; border: none; cursor: pointer;
        font-size: 13.5px; font-weight: 600; font-family: inherit;
        transition: opacity .15s, transform .1s;
        white-space: nowrap;
      }
      .mrts-btn:active { transform: scale(.97); }
      .mrts-btn:focus-visible { outline: 2px solid #1a5c2a; outline-offset: 2px; }

      .mrts-btn--primary   { background: #1a5c2a; color: #fff; }
      .mrts-btn--primary:hover { background: #14471f; }
      .mrts-btn--danger    { background: #dc2626; color: #fff; }
      .mrts-btn--danger:hover { background: #b91c1c; }
      .mrts-btn--secondary {
        background: #f3f4f6; color: #374151;
        border: 1px solid #e5e7eb;
      }
      .mrts-btn--secondary:hover { background: #e5e7eb; }

      /* ── Toast ── */
      #mrts-toast-container {
        position: fixed; bottom: 24px; right: 24px;
        z-index: 99999;
        display: flex; flex-direction: column; gap: 8px;
        pointer-events: none;
      }
      .mrts-toast {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 12px 16px;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,.15);
        font-family: system-ui, -apple-system, sans-serif;
        font-size: 13px; font-weight: 500; line-height: 1.45;
        max-width: 340px; pointer-events: all;
        animation: mrts-toast-in .22s cubic-bezier(.34,1.56,.64,1);
        color: #fff;
      }
      @keyframes mrts-toast-in {
        from { opacity: 0; transform: translateX(24px) scale(.95); }
        to   { opacity: 1; transform: translateX(0) scale(1); }
      }
      .mrts-toast--out {
        animation: mrts-toast-out .2s ease forwards;
      }
      @keyframes mrts-toast-out {
        to { opacity: 0; transform: translateX(24px) scale(.95); max-height: 0; padding: 0; margin: 0; }
      }
      .mrts-toast--info    { background: #1d4ed8; }
      .mrts-toast--success { background: #1a5c2a; }
      .mrts-toast--warning { background: #b45309; }
      .mrts-toast--error   { background: #dc2626; }
      .mrts-toast__icon { flex-shrink: 0; width: 18px; height: 18px; margin-top: 1px; }
      .mrts-toast__text { flex: 1; }
    `;
    document.head.appendChild(style);
  }

  /* ── SVG icons ─────────────────────────────────────────────────────────── */
  const ICONS = {
    info: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>`,
    success: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
    </svg>`,
    warning: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
    </svg>`,
    error: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
    </svg>`,
    confirm: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
    </svg>`,
  };

  /* ── Default titles ─────────────────────────────────────────────────────── */
  const DEFAULT_TITLES = {
    info:    'Notice',
    success: 'Success',
    warning: 'Warning',
    error:   'Error',
    confirm: 'Confirm Action',
  };

  /* ── Build overlay DOM ──────────────────────────────────────────────────── */
  function buildOverlay(html) {
    const overlay = document.createElement('div');
    overlay.className = 'mrts-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.innerHTML = html;
    return overlay;
  }

  /* ── alert(message, opts) → Promise<void> ──────────────────────────────── */
  function modalAlert(message, opts = {}) {
    return new Promise((resolve) => {
      const type  = opts.type  || 'info';
      const title = opts.title || DEFAULT_TITLES[type] || 'Notice';
      const btnLabel = opts.btnLabel || 'OK';
      const btnClass = type === 'error' ? 'mrts-btn--danger'
                     : type === 'warning' ? 'mrts-btn--primary'
                     : 'mrts-btn--primary';

      const overlay = buildOverlay(`
        <div class="mrts-dialog" role="alertdialog" aria-labelledby="mrts-title" aria-describedby="mrts-msg">
          <div class="mrts-dialog__icon-strip">
            <div class="mrts-dialog__icon mrts-icon--${type}">${ICONS[type] || ICONS.info}</div>
          </div>
          <div class="mrts-dialog__body">
            <p class="mrts-dialog__title" id="mrts-title">${escHtml(title)}</p>
            <p class="mrts-dialog__message" id="mrts-msg">${escHtml(message)}</p>
          </div>
          <div class="mrts-dialog__footer">
            <button class="mrts-btn ${btnClass}" id="mrts-ok-btn">${escHtml(btnLabel)}</button>
          </div>
        </div>
      `);

      document.body.appendChild(overlay);

      const okBtn = overlay.querySelector('#mrts-ok-btn');
      okBtn.focus();

      function close() {
        overlay.remove();
        resolve();
      }

      okBtn.addEventListener('click', close);
      overlay.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' || e.key === 'Enter') close();
      });
    });
  }

  /* ── confirm(message, opts) → Promise<boolean> ─────────────────────────── */
  function modalConfirm(message, opts = {}) {
    return new Promise((resolve) => {
      const type       = opts.type       || 'confirm';
      const title      = opts.title      || DEFAULT_TITLES[type] || 'Confirm';
      const okLabel    = opts.okLabel    || 'Confirm';
      const cancelLabel= opts.cancelLabel|| 'Cancel';
      const okClass    = opts.danger     ? 'mrts-btn--danger' : 'mrts-btn--primary';

      const overlay = buildOverlay(`
        <div class="mrts-dialog" role="alertdialog" aria-labelledby="mrts-title" aria-describedby="mrts-msg">
          <div class="mrts-dialog__icon-strip">
            <div class="mrts-dialog__icon mrts-icon--${type}">${ICONS[type] || ICONS.confirm}</div>
          </div>
          <div class="mrts-dialog__body">
            <p class="mrts-dialog__title" id="mrts-title">${escHtml(title)}</p>
            <p class="mrts-dialog__message" id="mrts-msg">${escHtml(message)}</p>
          </div>
          <div class="mrts-dialog__footer mrts-dialog__footer--split">
            <button class="mrts-btn mrts-btn--secondary" id="mrts-cancel-btn">${escHtml(cancelLabel)}</button>
            <button class="mrts-btn ${okClass}" id="mrts-ok-btn">${escHtml(okLabel)}</button>
          </div>
        </div>
      `);

      document.body.appendChild(overlay);

      const okBtn     = overlay.querySelector('#mrts-ok-btn');
      const cancelBtn = overlay.querySelector('#mrts-cancel-btn');
      okBtn.focus();

      function close(result) {
        overlay.remove();
        resolve(result);
      }

      okBtn.addEventListener('click',     () => close(true));
      cancelBtn.addEventListener('click', () => close(false));
      overlay.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close(false);
        if (e.key === 'Enter')  close(true);
      });
    });
  }

  /* ── toast(message, opts) → void ───────────────────────────────────────── */
  function getToastContainer() {
    let c = document.getElementById('mrts-toast-container');
    if (!c) {
      c = document.createElement('div');
      c.id = 'mrts-toast-container';
      document.body.appendChild(c);
    }
    return c;
  }

  function modalToast(message, opts = {}) {
    const type     = opts.type     || 'info';
    const duration = opts.duration != null ? opts.duration : 4000;
    const container = getToastContainer();

    const toast = document.createElement('div');
    toast.className = `mrts-toast mrts-toast--${type}`;
    toast.innerHTML = `
      <span class="mrts-toast__icon">${ICONS[type] || ICONS.info}</span>
      <span class="mrts-toast__text">${escHtml(message)}</span>
    `;
    container.appendChild(toast);

    if (duration > 0) {
      setTimeout(() => {
        toast.classList.add('mrts-toast--out');
        setTimeout(() => toast.remove(), 220);
      }, duration);
    }
  }

  /* ── HTML escape helper ─────────────────────────────────────────────────── */
  function escHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  /* ── Export ─────────────────────────────────────────────────────────────── */
  window.MRTS = window.MRTS || {};
  window.MRTS.modal = {
    alert:   modalAlert,
    confirm: modalConfirm,
    toast:   modalToast,
  };
})();
