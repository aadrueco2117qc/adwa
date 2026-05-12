/* Shared helpers */
(function () {
  const APP_BASE =
    (window.MRTS && window.MRTS.APP_BASE) ||
    window.__APP_BASE__ ||
    '/';

  async function api(path, options = {}) {
    const res = await fetch(APP_BASE + path, {
      credentials: 'same-origin',
      ...options,
      headers: { ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }), ...(options.headers || {}) },
    });
    const isJson = (res.headers.get('content-type') || '').includes('application/json');
    const data = isJson ? await res.json().catch(() => ({})) : await res.text();
    if (!res.ok) {
      const msg = (data && data.message) ? data.message : `Request failed (${res.status})`;
      throw new Error(msg);
    }
    return data;
  }

  function fmtTime(ms) {
    const s = Math.max(0, Math.floor(ms / 1000));
    const hh = String(Math.floor(s / 3600)).padStart(2, '0');
    const mm = String(Math.floor((s % 3600) / 60)).padStart(2, '0');
    const ss = String(s % 60).padStart(2, '0');
    return `${hh}:${mm}:${ss}`;
  }

  window.MRTS = window.MRTS || {};
  window.MRTS.APP_BASE = window.MRTS.APP_BASE || APP_BASE;
  window.MRTS.api = window.MRTS.api || api;
  window.MRTS.fmtTime = window.MRTS.fmtTime || fmtTime;
})();

