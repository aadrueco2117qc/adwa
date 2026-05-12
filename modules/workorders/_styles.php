<style>
/* ── Work Orders module shared styles ──────────────────────── */

/* ── Reused patterns from the design system ────────────────── */

/* Badges */
.wo-badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:11.5px;font-weight:600;white-space:nowrap;line-height:1.4;}
.bdot{width:6px;height:6px;border-radius:50%;margin-right:5px;flex-shrink:0;display:inline-block;}

/* WO Status badges */
.badge-new{background:#dbeafe;color:#1e40af;}.badge-new .bdot{background:#3b82f6;}
.badge-assigned{background:#e0e7ff;color:#3730a3;}.badge-assigned .bdot{background:#6366f1;}
.badge-scheduled{background:#fef3c7;color:#b45309;}.badge-scheduled .bdot{background:#d97706;}
.badge-in_progress{background:#dcfce7;color:#166534;}.badge-in_progress .bdot{background:#16a34a;}
.badge-on_hold{background:#fef2f2;color:#b91c1c;}.badge-on_hold .bdot{background:#dc2626;}
.badge-resolved{background:#d1fae5;color:#065f46;}.badge-resolved .bdot{background:#10b981;}
.badge-closed{background:#f3f4f6;color:#4b5563;}.badge-closed .bdot{background:#9ca3af;}

/* WO Type badges */
.badge-type{background:#f3f4f6;color:#4b5563;font-weight:500;}
.badge-type-diagnosis{background:#ede9fe;color:#5b21b6;}
.badge-type-repair{background:#dbeafe;color:#1e40af;}
.badge-type-maintenance{background:#fef3c7;color:#92400e;}
.badge-type-follow_up{background:#fce7f3;color:#9d174d;}

/* Priority badges */
.badge-priority{font-weight:600;}
.badge-low{background:#f3f4f6;color:#4b5563;}
.badge-medium{background:#fef3c7;color:#b45309;}
.badge-high{background:#fee2e2;color:#b91c1c;}
.badge-critical{background:#fecaca;color:#991b1b;animation:pulse-critical 2s ease-in-out infinite;}
@keyframes pulse-critical{0%,100%{opacity:1;}50%{opacity:.75;}}

/* WO number monospace */
.wo-tag{font-family:ui-monospace,'Cascadia Code','Source Code Pro',Menlo,monospace;font-size:12.5px;color:#15803d;font-weight:600;}

/* Status chips */
.chip{padding:5px 13px;border-radius:20px;font-size:12.5px;font-weight:600;cursor:pointer;
      border:1.5px solid #e5e7eb;background:#fff;color:#4b5563;transition:all .15s;white-space:nowrap;display:inline-flex;align-items:center;gap:4px;}
.chip:hover{border-color:#86efac;color:#15803d;}
.chip.chip-on{background:#15803d;color:#fff;border-color:#15803d;}

/* Table hover */
tbody tr.row-link{cursor:pointer;}
tbody tr.row-link:hover td{background:#f0fdf4;color:#111827;}

/* Alert banners */
.wo-banner{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;margin-bottom:14px;font-size:13px;font-weight:500;}
.banner-warn{background:#fef2f2;border:1px solid #fee2e2;border-left:3.5px solid #dc2626;color:#b91c1c;}
.banner-info{background:#fffbeb;border:1px solid #fef3c7;border-left:3.5px solid #d97706;color:#b45309;}
.banner-success{background:#f0fdf4;border:1px solid #dcfce7;border-left:3.5px solid #16a34a;color:#166534;}
.banner-link{margin-left:auto;font-size:12px;text-decoration:underline;cursor:pointer;white-space:nowrap;}

/* Pagination */
.pg-wrap{display:flex;align-items:center;justify-content:space-between;padding:11px 16px;border-top:1px solid #f3f4f6;font-size:12.5px;color:#6b7280;}
.pg-btns{display:flex;gap:3px;}
.pg-btn{min-width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:5px;
        border:1px solid #e5e7eb;background:#fff;cursor:pointer;font-size:12.5px;font-weight:500;color:#4b5563;transition:all .12s;padding:0 5px;}
.pg-btn:hover{background:#f9fafb;}
.pg-btn.pg-on{background:#15803d;color:#fff;border-color:#15803d;}

/* View-mode field labels/values */
.vf-lbl{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:2px;}
.vf-val{font-size:13.5px;color:#111827;font-weight:500;}
.vf-mono{font-family:ui-monospace,'Cascadia Code','Source Code Pro',Menlo,monospace;font-size:12.5px;color:#15803d;font-weight:600;}
.vf-empty{color:#d1d5db;font-style:italic;font-weight:400;}

/* Section dividers */
.sdiv{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;
      padding:14px 0 8px;border-bottom:1px solid #f3f4f6;margin-bottom:12px;}

/* Right panel header */
.rp-hdr{background:#15803d;color:#fff;padding:10px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;}
.rp-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f3f4f6;font-size:13px;}
.rp-row:last-child{border-bottom:none;}
.rp-lbl{color:#6b7280;font-weight:500;}
.rp-val{font-weight:600;color:#1f2937;}

/* Tabs */
.tab-nav{display:flex;border-bottom:1px solid #e5e7eb;padding:0 20px;background:#fff;overflow-x:auto;}
.tab-btn{padding:11px 14px;font-size:13px;font-weight:500;color:#6b7280;cursor:pointer;
         border-bottom:2px solid transparent;margin-bottom:-1px;transition:all .15s;white-space:nowrap;
         background:none;border-top:none;border-left:none;border-right:none;}
.tab-btn:hover{color:#1f2937;}
.tab-btn.tab-on{color:#15803d;border-bottom-color:#15803d;font-weight:600;}

/* Form inputs */
.flbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;display:block;margin-bottom:4px;}
.fin{width:100%;padding:8px 11px;border:1.5px solid #e5e7eb;border-radius:6px;font-size:13.5px;font-family:inherit;color:#1f2937;outline:none;transition:border .15s,box-shadow .15s;background:#fff;}
.fin:focus{border-color:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.08);}
.fin.fin-err{border-color:#dc2626;}
.fin[readonly]{background:#f9fafb;color:#6b7280;cursor:default;}
.fsel{width:100%;padding:8px 28px 8px 11px;border:1.5px solid #e5e7eb;border-radius:6px;font-size:13.5px;font-family:inherit;color:#1f2937;outline:none;
      background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") no-repeat right 8px center;
      appearance:none;cursor:pointer;transition:border .15s;}
.fsel:focus{border-color:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.08);}
.fsel.fsel-err{border-color:#dc2626;}
.ferr-msg{font-size:11px;color:#dc2626;display:flex;align-items:center;gap:3px;margin-top:3px;}
.fhint{font-size:11px;color:#9ca3af;margin-top:3px;}

/* Stat icon containers */
.stat-ico{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}

/* ── WO-specific: Checklist ────────────────────────────────── */
.cl-item{display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid #f3f4f6;}
.cl-item:last-child{border-bottom:none;}
.cl-check{width:18px;height:18px;border-radius:4px;border:2px solid #d1d5db;flex-shrink:0;margin-top:1px;display:flex;align-items:center;justify-content:center;transition:all .15s;}
.cl-check.cl-done{background:#15803d;border-color:#15803d;}
.cl-check.cl-done svg{display:block;}
.cl-check svg{display:none;width:12px;height:12px;color:#fff;}
.cl-text{font-size:13.5px;color:#1f2937;font-weight:500;flex:1;}
.cl-mandatory{color:#dc2626;font-size:10px;margin-left:4px;font-weight:700;}
.cl-meta{font-size:11px;color:#9ca3af;margin-top:2px;}

/* Checklist progress bar */
.cl-progress{height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;}
.cl-progress-fill{height:100%;background:#15803d;border-radius:3px;transition:width .3s ease;}

/* ── WO-specific: Timeline ─────────────────────────────────── */
.tl-wrap{position:relative;padding-left:24px;}
.tl-wrap::before{content:'';position:absolute;left:7px;top:8px;bottom:8px;width:2px;background:#e5e7eb;}
.tl-entry{position:relative;padding-bottom:16px;}
.tl-entry:last-child{padding-bottom:0;}
.tl-dot{position:absolute;left:-24px;top:4px;width:16px;height:16px;border-radius:50%;border:2px solid #e5e7eb;background:#fff;display:flex;align-items:center;justify-content:center;}
.tl-dot.tl-start{border-color:#16a34a;background:#dcfce7;}
.tl-dot.tl-pause{border-color:#d97706;background:#fef3c7;}
.tl-dot.tl-resume{border-color:#3b82f6;background:#dbeafe;}
.tl-dot.tl-stop{border-color:#dc2626;background:#fef2f2;}
.tl-action{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;}
.tl-time{font-size:11px;color:#9ca3af;}
.tl-notes{font-size:13px;color:#4b5563;margin-top:2px;}

/* ── WO-specific: Media grid ──────────────────────────────── */
.media-card{border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;background:#fff;transition:box-shadow .15s;}
.media-card:hover{box-shadow:0 2px 8px rgba(0,0,0,.08);}
.media-thumb{width:100%;aspect-ratio:4/3;object-fit:cover;background:#f3f4f6;}
.media-info{padding:10px 12px;}
.media-type{display:inline-flex;align-items:center;padding:2px 7px;border-radius:10px;font-size:10px;font-weight:600;text-transform:uppercase;}
.media-before{background:#dbeafe;color:#1e40af;}
.media-after{background:#dcfce7;color:#166534;}
.media-evidence{background:#fef3c7;color:#92400e;}

/* ── WO-specific: Sign-off ─────────────────────────────────── */
.signoff-card{border:1px solid #e5e7eb;border-radius:10px;padding:20px;background:#f9fafb;}
.star{color:#d1d5db;font-size:18px;}
.star.star-on{color:#f59e0b;}

/* On-hold reason tag */
.hold-reason{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:600;background:#fef2f2;color:#b91c1c;}
</style>
