<style>
/* ═══════════════════════════════════════════════════════════════
   Technician Ops — Redesigned Styles
   Font: Outfit (display/headings) + JetBrains Mono (codes)
   Palette: OLFU Green brand system + refined neutrals
   ═══════════════════════════════════════════════════════════════ */

@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap');

/* ── Design tokens ──────────────────────────────────────────── */
:root {
  /* OLFU Brand greens */
  --olfu-green:       #1a5c2a;
  --olfu-green-md:    #1f6e32;
  --olfu-green-lt:    #256b38;
  --olfu-green-700:   #15803d;
  --olfu-green-600:   #16a34a;
  --olfu-green-100:   #dcfce7;
  --olfu-green-300:   #86efac;
  --olfu-green-50:    #f0fdf4;

  /* Semantic aliases */
  --tech-green:       #1a5c2a;
  --tech-green-lt:    #f0fdf4;
  --tech-green-mid:   #1f6e32;
  --tech-green-dk:    #0f3d1c;
  --tech-green-bd:    #86efac;
  --tech-green-100:   #dcfce7;

  /* Status colors */
  --tech-red:         #b91c1c;
  --tech-red-lt:      #fef2f2;
  --tech-red-bd:      #fecaca;
  --tech-amber:       #b45309;
  --tech-amber-lt:    #fef3c7;
  --tech-amber-bd:    #fde68a;
  --tech-blue:        #185FA5;
  --tech-blue-lt:     #eff6ff;
  --tech-blue-bd:     #bfdbfe;

  /* Neutrals */
  --tech-gray-900:    #111827;
  --tech-gray-800:    #1f2937;
  --tech-gray-700:    #374151;
  --tech-gray-600:    #4b5563;
  --tech-gray-500:    #6b7280;
  --tech-gray-400:    #9ca3af;
  --tech-gray-300:    #d1d5db;
  --tech-gray-200:    #e5e7eb;
  --tech-gray-100:    #f3f4f6;
  --tech-gray-50:     #f9fafb;

  --tech-surface:     #ffffff;
  --tech-page:        #f9fafb;

  --tech-radius:      8px;
  --tech-radius-lg:   12px;
  --tech-radius-xl:   16px;

  --tech-mono:  'JetBrains Mono', ui-monospace, monospace;
  --tech-sans:  'Outfit', system-ui, sans-serif;

  /* Shadows */
  --shadow-sm:  0 1px 2px rgba(0,0,0,.05);
  --shadow-md:  0 2px 8px rgba(0,0,0,.07);
  --shadow-lg:  0 4px 16px rgba(0,0,0,.08);
}

/* Base font */
body, .tech-module * {
  font-family: system-ui,sans-serif;
}

/* ── Sync status pill ───────────────────────────────────────── */
.sync-badge {
  display: inline-flex; align-items: center; gap: 6px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 20px;
  padding: 5px 12px;
  font-size: 11.5px; color: #6b7280; font-weight: 500;
}
.sync-dot {
  width: 6px; height: 6px; border-radius: 50%; display: inline-block; flex-shrink: 0;
  background: #F59E0B;
}
.sync-dot.sync-dot-checking { background: #F59E0B; }
.sync-dot.sync-dot-online   { background: #22C55E; }
.sync-dot.sync-dot-offline  { background: #EF4444; }

/* ── WO Header card ─────────────────────────────────────────── */
.wo-header-card {
  background: #fff;
  border: 1px solid #f3f4f6;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
  padding: 20px 22px 18px;
  margin-bottom: 12px;
  position: relative;
  overflow: hidden;
}
.wo-header-card__accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, #15803d 0%, #16a34a 60%, #86efac 100%);
}

/* ── Metadata labels/values ─────────────────────────────────── */
.vf-lbl {
  font-size: 10.5px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .06em;
  color: #9ca3af; margin-bottom: 2px;
}
.vf-val {
  font-size: 13.5px; color: #111827; font-weight: 500;
}
.vf-empty { color: #d1d5db; font-style: italic; font-weight: 400; }

/* ── WO number pill ─────────────────────────────────────────── */
.vf-mono {
  font-family: ui-monospace,'Cascadia Code','Source Code Pro',Menlo,monospace;
  font-size: 12.5px; font-weight: 600;
  color: #15803d;
  background: #dcfce7;
  padding: 3px 10px; border-radius: 6px;
  letter-spacing: .3px;
}
.wo-badge {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 600;
  padding: 3px 10px; border-radius: 999px;
  line-height: 1.2; white-space: nowrap;
}
.bdot {
  width: 5px; height: 5px; border-radius: 50%; display: inline-block; flex-shrink: 0;
}
.badge-new        { background: #f3f4f6; color: #4b5563; }
.badge-new        .bdot { background: #9ca3af; }
.badge-assigned   { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.badge-assigned   .bdot { background: #3b82f6; }
.badge-scheduled  { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.badge-scheduled  .bdot { background: #3b82f6; }
.badge-in_progress{ background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.badge-in_progress .bdot { background: #d97706; }
.badge-on_hold    { background: #fdf2f8; color: #9d174d; border: 1px solid #fbcfe8; }
.badge-on_hold    .bdot { background: #ec4899; }
.badge-resolved   { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
.badge-resolved   .bdot { background: #64748b; }
.badge-closed     { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
.badge-closed     .bdot { background: #9ca3af; }

/* ── Metadata labels/values ─────────────────────────────────── */
.vf-lbl {
  font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .7px;
  color: #9ca3af; margin-bottom: 3px;
}
.vf-val {
  font-size: 13px; font-weight: 500; color: #1f2937;
}
.vf-empty { color: #9ca3af; font-style: italic; font-weight: 400; }

/* ── Offline notice ─────────────────────────────────────────── */
.offline-notice {
  display: flex; align-items: center; gap: 8px;
  margin-top: 14px; padding: 9px 14px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  font-size: 12px; color: #1d4ed8; font-weight: 500;
}
.offline-notice svg { width: 14px; height: 14px; flex-shrink: 0; }

/* ── Tab navigation ─────────────────────────────────────────── */
.tab-nav {
  display: flex;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  border-bottom: 1px solid #e5e7eb;
  background: #fff;
  padding: 0;
}
.tab-nav::-webkit-scrollbar { display: none; }

.tab-nav.primary-tabs {
  border-bottom: 1px solid #e5e7eb;
}
.primary-tab-btn {
  display: flex; align-items: center; gap: 7px;
  padding: 11px 14px;
  font-size: 13px; font-weight: 500;
  color: #6b7280;
  background: transparent; border: none;
  cursor: pointer; position: relative;
  transition: color .15s;
  white-space: nowrap;
  flex-shrink: 0;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
}
.primary-tab-btn:hover { color: #1f2937; }
.primary-tab-btn.tab-on {
  color: #15803d;
  font-weight: 600;
  border-bottom-color: #15803d;
}

/* Secondary tabs */
.tab-nav.secondary-tabs {
  border-bottom: 1px solid #e5e7eb;
}
.secondary-tab-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 10px 14px;
  font-size: 12.5px; font-weight: 500;
  color: #6b7280;
  background: transparent; border: none;
  cursor: pointer; transition: all .15s;
  white-space: nowrap;
  flex-shrink: 0;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
}
.secondary-tab-btn:hover { color: #1f2937; }
.secondary-tab-btn.tab-on {
  color: #15803d; font-weight: 600;
  border-bottom-color: #15803d;
}
.secondary-tab-btn svg { width: 13px; height: 13px; flex-shrink: 0; }

.tab-count-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 20px; height: 18px; padding: 0 5px;
  border-radius: 99px;
  font-size: 10px; font-weight: 700;
  background: #f3f4f6; color: #6b7280;
}
.tab-count-badge.done { background: #dcfce7; color: #15803d; }
.tab-count-badge.warn { background: #fef3c7; color: #b45309; }

/* ── Tech card ──────────────────────────────────────────────── */
.tech-card {
  background: #fff;
  border: 1px solid #f3f4f6;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
  overflow: hidden;
  margin-bottom: 14px;
}
.tech-card__head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 13px 18px 11px;
  border-bottom: 1px solid #f3f4f6;
  background: #fafafa;
}
.tech-card__title {
  display: flex; align-items: center; gap: 7px;
  font-size: 13px; font-weight: 700;
  color: #111827;
  font-family: system-ui,sans-serif;
}
.tech-card__title svg { width: 14px; height: 14px; flex-shrink: 0; }
.tech-card__meta {
  font-size: 11.5px; color: #9ca3af; font-weight: 500;
}
.tech-card__body { padding: 16px 18px; }

/* ── Progress bar ───────────────────────────────────────────── */
.tech-progress {
  height: 5px; border-radius: 99px;
  background: #f3f4f6;
  overflow: hidden; margin: 0 18px 12px;
}
.tech-progress-fill {
  height: 100%; border-radius: 99px;
  background: #16a34a;
  transition: width .4s ease;
}
.tech-progress-fill.red { background: #b91c1c; }

/* ── Safety info banner ─────────────────────────────────────── */
.safety-info-banner {
  display: flex; align-items: flex-start; gap: 8px;
  margin: 0 18px 14px;
  padding: 10px 13px;
  background: #fef3c7;
  border: 1px solid #fde68a;
  border-radius: 8px;
  font-size: 12px; color: #b45309; font-weight: 500;
  line-height: 1.5;
}
.safety-info-banner svg { width: 14px; height: 14px; flex-shrink: 0; margin-top: 1px; }

/* ── Form inputs ────────────────────────────────────────────── */
.fin {
  border: 1.5px solid #e5e7eb;
  border-radius: 6px;
  background: #fff;
  padding: 8px 11px;
  color: #1f2937;
  font-family: system-ui,sans-serif;
  font-size: 13.5px;
  transition: border-color .15s, box-shadow .15s;
  outline: none;
}
.fin::placeholder { color: #9ca3af; }
.fin:focus {
  border-color: #22c55e;
  box-shadow: 0 0 0 3px rgba(34,197,94,.08);
}
.fin[disabled], .fin[readonly] {
  background: #f9fafb; color: #6b7280; cursor: default;
}

/* ── Checklist items ────────────────────────────────────────── */
.checklist-item {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 13px 18px;
  transition: background .12s;
  cursor: pointer;
}
.checklist-item:hover { background: #f9fafb; }
.checklist-item.item-done .checklist-text {
  color: #9ca3af;
  text-decoration: line-through;
}
.checklist-cb {
  width: 18px; height: 18px; border-radius: 5px;
  border: 1.5px solid #d1d5db;
  background: white; flex-shrink: 0;
  margin-top: 1px; cursor: pointer;
  accent-color: #15803d;
  transition: all .15s;
}
.checklist-cb:checked { border-color: #15803d; }
.checklist-text { font-size: 13px; font-weight: 500; color: #374151; line-height: 1.5; }
.checklist-meta { display: flex; align-items: center; gap: 6px; margin-top: 3px; }
.checklist-tag {
  font-size: 10px; font-weight: 600; padding: 1px 7px; border-radius: 99px;
  background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;
}
.checklist-tag.photo {
  background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe;
}

/* ── Time log entries ───────────────────────────────────────── */
.time-log-entry {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 14px;
  background: #f9fafb;
  border: 1px solid #f3f4f6;
  border-radius: 8px;
  font-size: 12.5px;
}
.time-log-entry__dur {
  font-family: ui-monospace,'Cascadia Code','Source Code Pro',Menlo,monospace; font-weight: 700;
  color: #111827; font-size: 13px;
}
.time-log-entry__meta { color: #9ca3af; font-size: 11.5px; }

/* ── Part items ─────────────────────────────────────────────── */
.part-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 13px;
  background: #f9fafb;
  border: 1px solid #f3f4f6;
  border-radius: 8px; font-size: 13px; transition: all .2s;
}
.part-item:hover {
  background: #ffffff;
  border-color: #e5e7eb;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.part-item__icon {
  width: 30px; height: 30px; border-radius: 8px;
  background: #dcfce7; color: #1a5c2a;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.part-item__icon svg { width: 14px; height: 14px; }
.part-item__info { flex: 1; min-width: 0; }
.part-item__name {
  font-weight: 700; color: #1f2937;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.part-item__meta { font-size: 11.5px; color: #9ca3af; margin-top: 1px; }
.part-item__qty {
  font-family: ui-monospace,'Cascadia Code','Source Code Pro',Menlo,monospace; font-size: 11.5px; font-weight: 700;
  color: #1a5c2a;
  background: #dcfce7; border: 1px solid #86efac;
  border-radius: 6px; padding: 2px 8px; white-space: nowrap;
}
.part-item__remove {
  background: none; border: none; color: #d1d5db; font-size: 16px;
  cursor: pointer; padding: 2px; width: 22px; height: 22px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; transition: color .15s, background .15s; border-radius: 50%;
}
.part-item__remove:hover { color: #ef4444; background: #fee2e2; }

/* ── Media tiles ────────────────────────────────────────────── */
.mediaTile {
  width: 100%; aspect-ratio: 1;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  position: relative; overflow: hidden;
  display: flex; flex-direction: column; transition: all .2s;
}
.mediaTile:hover { border-color: #86efac; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
.mediaTile__content {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center; padding: 8px;
}
.mediaTile__x {
  position: absolute; top: 4px; right: 4px;
  background: rgba(0,0,0,.45); color: #fff; border: none;
  width: 20px; height: 20px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; cursor: pointer; transition: background .2s;
}
.mediaTile__x:hover { background: rgba(0,0,0,.7); }
.mediaTile--error   { border-color: #ef4444; background: #fef2f2; }
.mediaTile--loading { border-color: #f59e0b; background: #fffbeb; }

#configMedia { grid-template-columns: 1fr !important; gap: 6px !important; }
#configMedia .mediaTile {
  aspect-ratio: unset !important; flex-direction: row !important;
  padding: 8px 12px !important; align-items: center !important;
  gap: 10px !important; height: auto !important;
}
#configMedia .mediaTile__content {
  flex-direction: row !important; justify-content: flex-start !important;
  padding: 0 !important; gap: 8px !important;
  font-size: 12.5px !important; color: #374151 !important;
  font-weight: 600 !important; flex: 1 !important;
}
#configMedia .mediaTile__x {
  position: static !important; background: none !important;
  color: #d1d5db !important; font-size: 18px !important;
  margin-left: auto !important;
}
#configMedia .mediaTile__x:hover { color: #ef4444 !important; }

/* ── Alert boxes ────────────────────────────────────────────── */
.alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; }
.alert--warn {
  background: #fef3c7;
  border: 1px solid #fde68a;
  color: #6d3f0a;
}
.alert--warn strong { color: #b45309; }
.alert--warn ul { list-style: disc; padding-left: 16px; }
.alert--warn li { margin-top: 3px; }

/* ── Stat cards (index page) ────────────────────────────────── */
.stat-card {
  background: #ffffff;
  border: 1px solid #f3f4f6;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
  padding: 18px;
  display: flex; flex-direction: column; gap: 10px;
  position: relative; overflow: hidden;
  transition: border-color .15s, box-shadow .15s;
}
.stat-card:hover { border-color: #e5e7eb; box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.stat-card__accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  border-radius: 12px 12px 0 0;
}
.stat-card--blue   .stat-card__accent { background: #1d4ed8; }
.stat-card--amber  .stat-card__accent { background: #d97706; }
.stat-card--purple .stat-card__accent { background: #7c3aed; }
.stat-card--green  .stat-card__accent { background: #16a34a; }

.stat-card__icon {
  width: 36px; height: 36px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.stat-card__icon svg { width: 17px; height: 17px; }
.stat-card--blue   .stat-card__icon { background: #eff6ff;   color: #1d4ed8; }
.stat-card--amber  .stat-card__icon { background: #fef3c7;  color: #b45309; }
.stat-card--purple .stat-card__icon { background: #ede9fe; color: #7c3aed; }
.stat-card--green  .stat-card__icon { background: #dcfce7; color: #1a5c2a; }

.stat-card__value {
  font-size: 28px; font-weight: 800; color: #111827; line-height: 1;
  font-family: system-ui,sans-serif;
}
.stat-card__label {
  font-size: 12px; color: #6b7280; font-weight: 400; margin-top: 2px;
}

/* ── Filter chips ────────────────────────────────────────────── */
.chip {
  padding: 5px 13px; border-radius: 20px;
  font-size: 12.5px; font-weight: 600; cursor: pointer;
  border: 1.5px solid #e5e7eb;
  background: #fff;
  color: #4b5563;
  transition: all .15s;
  white-space: nowrap; display: inline-flex; align-items: center; gap: 4px;
}
.chip:hover { border-color: #86efac; color: #15803d; }
.chip.chip-on {
  background: #15803d; color: #fff;
  border-color: #15803d;
}

/* ── Work type filter ────────────────────────────────────────── */
.tech-filter-select {
  border: 1px solid #e5e7eb;
  background: #ffffff;
  color: #374151;
  font-size: 12.5px;
  border-radius: 8px;
  padding: 7px 11px;
  min-width: 160px;
  font-family: system-ui,sans-serif;
  font-weight: 500;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
  transition: all .15s;
  outline: none;
}
.tech-filter-select:hover { border-color: #86efac; }
.tech-filter-select:focus {
  border-color: #16a34a;
  box-shadow: 0 0 0 3px rgba(22,163,74,.1);
}

.tech-filter-group {
  display: flex; align-items: center; gap: 8px;
}
.tech-filter-icon {
  display: flex; align-items: center; justify-content: center;
  width: 30px; height: 30px;
  background: #ffffff; color: #1a5c2a;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  flex-shrink: 0;
}

/* ── Job cards (index page) ─────────────────────────────────── */
.job-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
  padding: 0;
  display: flex; flex-direction: column; height: 100%;
  transition: border-color .15s, box-shadow .15s, transform .12s;
  overflow: hidden;
}
.job-card:hover {
  border-color: #86efac;
  box-shadow: 0 4px 12px rgba(21,128,61,.08);
  transform: translateY(-1px);
}
.job-card-header {
  padding: 14px 16px 10px;
  border-bottom: 1px solid #f3f4f6;
}
.job-card-tag {
  font-family: ui-monospace,'Cascadia Code','Source Code Pro',Menlo,monospace;
  font-size: 11px;
  color: #15803d; font-weight: 700;
  background: #dcfce7; padding: 2px 8px; border-radius: 5px;
  letter-spacing: .3px;
}
.job-card-body  { flex: 1; display: flex; flex-direction: column; padding: 12px 16px; }
.job-card-footer { padding: 0 16px 14px; }

.claim-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 9px; border-radius: 99px;
  background: #f3f4f6; color: #6b7280;
  font-size: 11px; font-weight: 600;
}

/* ── Primary action buttons ─────────────────────────────────── */
.btn-primary {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  background: #15803d; color: #fff;
  font-size: 13px; font-weight: 600;
  padding: 8px 16px; border-radius: 8px;
  border: none; cursor: pointer; transition: background .15s;
  font-family: system-ui,sans-serif;
  text-decoration: none;
}
.btn-primary:hover { background: #166534; }
.btn-secondary {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  background: #fff; color: #374151;
  font-size: 13px; font-weight: 500;
  padding: 8px 16px; border-radius: 8px;
  border: 1px solid #e5e7eb; cursor: pointer;
  transition: all .15s; font-family: system-ui,sans-serif;
  text-decoration: none;
}
.btn-secondary:hover { background: #f9fafb; border-color: #d1d5db; }

/* ── Modals ─────────────────────────────────────────────────── */
@keyframes slideUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}
.modal-overlay { animation: fadeIn .2s ease-out; }
.modal-dialog  { font-family: system-ui,sans-serif; animation: slideUp .25s ease-out; }

/* ── Note items ─────────────────────────────────────────────── */
.note-item {
  padding: 12px 14px;
  background: #f9fafb;
  border: 1px solid #f3f4f6;
  border-radius: 8px;
  font-size: 13px; color: #374151;
  line-height: 1.55;
}
.note-item__title {
  font-weight: 700; color: #1f2937; margin-bottom: 4px; font-size: 13px;
}
.note-item__meta { font-size: 11px; color: #9ca3af; margin-top: 6px; }

/* ── Signoff section ────────────────────────────────────────── */
.satisfaction-star svg {
  width: 28px; height: 28px; cursor: pointer; transition: color .12s;
}

/* ── Dividers ───────────────────────────────────────────────── */
.divide-y > * + * { border-top: 1px solid #f3f4f6; }

/* ── Page header card (index) ───────────────────────────────── */
.page-header-card {
  background: #fff;
  border: 1px solid #f3f4f6;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
  padding: 18px 22px;
  margin-bottom: 14px;
  display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;
}

/* ── Work type filter panel ─────────────────────────────────── */
.filter-panel {
  background: #fff;
  border: 1px solid #f3f4f6;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
  padding: 12px 16px;
  margin-bottom: 14px;
  display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
}
.filter-panel__label {
  width: 100%;
  font-size: 11px; font-weight: 700; color: #9ca3af;
  display: flex; align-items: center; gap: 6px;
  text-transform: uppercase; letter-spacing: .6px;
  margin-bottom: 0;
}

/* ── My Jobs Only toggle ────────────────────────────────────── */
.my-jobs-toggle {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 12px; border-radius: 8px;
  border: 1px solid #e5e7eb;
  background: #fff; color: #4b5563;
  font-size: 12.5px; font-weight: 600; font-family: system-ui,sans-serif;
  cursor: pointer; transition: all .15s;
  white-space: nowrap;
}
.my-jobs-toggle:hover {
  border-color: #86efac;
  color: #15803d;
  background: #f0fdf4;
}
.my-jobs-toggle--on {
  background: #15803d;
  border-color: #15803d;
  color: #fff;
}
.my-jobs-toggle--on:hover {
  background: #166534;
  border-color: #166534;
  color: #fff;
}

/* ── bg-olfu-green helper ───────────────────────────────────── */
.bg-olfu-green     { background-color: #1a5c2a !important; }
.bg-olfu-green-md  { background-color: #1f6e32 !important; }
.text-olfu-green   { color: #1a5c2a !important; }

/* ── Checklist / Safety rows ────────────────────────────────── */
.checklist-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  cursor: pointer;
  transition: background .15s;
  user-select: none;
}
.checklist-row:hover {
  background: #f9fafb;
}
.checklist-row--done .checklist-text {
  color: #9ca3af;
  text-decoration: line-through;
  text-decoration-color: #d1d5db;
}
.checklist-row--auto {
  opacity: .75;
  cursor: default;
}
.checklist-text {
  flex: 1;
  font-size: 13px;
  font-weight: 500;
  color: #1f2937;
  line-height: 1.4;
}

/* Custom checkbox — OLFU green, compact */
.checklist-cb {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
  cursor: pointer;
  accent-color: #1a5c2a;
}
.checklist-cb:disabled {
  opacity: .6;
  cursor: not-allowed;
}

/* ── Time logs ───────────────────────────────────────────────── */
.timeLog {
  position: relative;
  display: flex; align-items: center; gap: 10px;
  padding: 9px 12px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  margin-bottom: 6px;
  transition: border-color .15s;
}
.timeLog:hover { border-color: #d1d5db; }

.timeLog__header { display: contents; }

.timeLog__type {
  display: inline-flex; align-items: center;
  font-size: 11px; font-weight: 600;
  text-transform: capitalize; letter-spacing: .2px;
  padding: 2px 9px; border-radius: 99px;
  background: #f3f4f6; color: #374151;
  white-space: nowrap; flex-shrink: 0;
  border: 1px solid #e5e7eb;
}

.timeLog__timestamp {
  flex: 1;
  font-size: 11px; color: #9ca3af; font-weight: 400;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.timeLog__time {
  font-family: ui-monospace,'Cascadia Code','Source Code Pro',Menlo,monospace;
  font-size: 12.5px; font-weight: 700;
  color: #0f5132; flex-shrink: 0; letter-spacing: .5px;
}

.timeLog__remove {
  width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: transparent; border: none; cursor: pointer;
  font-size: 15px; line-height: 1; color: #d1d5db;
  transition: background .15s, color .15s; padding: 0;
}
.timeLog__remove:hover { background: #fee2e2; color: #ef4444; }

/* ── Total row ────────────────────────────────────────────────── */
#timeTotalRow {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 14px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  margin-top: 4px;
}
#timeTotalRow span:first-child {
  font-size: 11px; font-weight: 700; color: #6b7280;
  text-transform: uppercase; letter-spacing: .6px;
}
#timeTotalValue {
  font-family: ui-monospace,'Cascadia Code','Source Code Pro',Menlo,monospace;
  font-size: 14px; font-weight: 800;
  color: #0f5132;
}

/* ── Empty state ──────────────────────────────────────────────── */
#laborEmptyState {
  text-align: center; padding: 40px 20px;
  color: #9ca3af; font-size: 13px; font-style: italic;
}

/* ── Timer display ──────────────────────────────────────────── */
.timer-display {
  text-align: center;
  padding: 18px 0 16px;
  border-bottom: 1px solid #f3f4f6;
  margin-bottom: 16px;
}
.timer-display span {
  font-family: ui-monospace,'Cascadia Code','Source Code Pro',Menlo,monospace;
  font-size: 36px; font-weight: 700;
  color: #111827;
  letter-spacing: 2px; line-height: 1;
  display: block;
}
.timer-display__sub {
  font-size: 10px; font-weight: 600;
  text-transform: uppercase; letter-spacing: .7px;
  color: #9ca3af; margin-top: 5px;
}


/* ── Note cards ─────────────────────────────────────────────── */
.note {
  position: relative;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-left: 3px solid #86efac;
  border-radius: 8px;
  padding: 11px 38px 11px 14px;
  margin-bottom: 8px;
  transition: border-color .15s;
}
.note:hover { border-color: #86efac; border-left-color: #1a5c2a; }

.note__header { margin-bottom: 4px; }
.note__title { font-size: 13px; font-weight: 700; color: #1f2937; line-height: 1.3; }
.note__title em { font-style: italic; font-weight: 400; color: #9ca3af; }

.note__remove {
  position: absolute; top: 9px; right: 10px;
  width: 22px; height: 22px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  background: transparent; border: none; cursor: pointer;
  font-size: 15px; line-height: 1; color: #d1d5db;
  transition: background .15s, color .15s; padding: 0;
}
.note__remove:hover { background: #e5e7eb; color: #4b5563; }

.note__meta {
  display: flex; align-items: center; gap: 7px;
  font-size: 10.5px; color: #9ca3af;
  margin-bottom: 6px;
}
.note__meta-source {
  display: inline-flex; align-items: center;
  background: #f3f4f6; color: #6b7280;
  font-size: 10px; font-weight: 600;
  padding: 1px 7px; border-radius: 99px;
}

.note__text {
  font-size: 13px; color: #374151;
  line-height: 1.55; font-weight: 400;
}
</style>