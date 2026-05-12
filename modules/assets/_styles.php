<style>
/* ── Asset module shared styles ─────────────────────────────── */

/* Badges */
.asset-badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:11.5px;font-weight:600;white-space:nowrap;line-height:1.4;}
.bdot{width:6px;height:6px;border-radius:50%;margin-right:5px;flex-shrink:0;display:inline-block;}
.badge-active{background:#dcfce7;color:#166534;}.badge-active .bdot{background:#16a34a;}
.badge-spare{background:#fef3c7;color:#b45309;}.badge-spare .bdot{background:#d97706;}
.badge-retired{background:#f3f4f6;color:#4b5563;}.badge-retired .bdot{background:#9ca3af;}
.badge-cat{background:#f3f4f6;color:#4b5563;font-weight:500;}
.badge-warn{background:#fef2f2;color:#b91c1c;}.badge-warn .bdot{background:#dc2626;}

/* Status chips */
.chip{padding:5px 13px;border-radius:20px;font-size:12.5px;font-weight:600;cursor:pointer;
      border:1.5px solid #e5e7eb;background:#fff;color:#4b5563;transition:all .15s;white-space:nowrap;display:inline-flex;align-items:center;gap:4px;}
.chip:hover{border-color:#86efac;color:#15803d;}
.chip.chip-on{background:#15803d;color:#fff;border-color:#15803d;}

/* Warranty expiry warning cell */
.warn-cell{display:inline-flex;align-items:center;gap:5px;color:#b45309;font-weight:500;}
.warn-cell svg{color:#d97706;flex-shrink:0;}

/* Asset tag monospace */
.asset-tag{font-family:ui-monospace,'Cascadia Code','Source Code Pro',Menlo,monospace;font-size:12.5px;color:#15803d;font-weight:600;}

/* Table hover */
tbody tr.row-link{cursor:pointer;}
tbody tr.row-link:hover td{background:#f0fdf4;color:#111827;}

/* Alert banners */
.asset-banner{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;margin-bottom:14px;font-size:13px;font-weight:500;}
.banner-warn{background:#fef2f2;border:1px solid #fee2e2;border-left:3.5px solid #dc2626;color:#b91c1c;}
.banner-info{background:#fffbeb;border:1px solid #fef3c7;border-left:3.5px solid #d97706;color:#b45309;}
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
.tab-nav{display:flex;border-bottom:1px solid #e5e7eb;padding:0 20px;background:#fff;}
.tab-btn{padding:11px 14px;font-size:13px;font-weight:500;color:#6b7280;cursor:pointer;
         border-bottom:2px solid transparent;margin-bottom:-1px;transition:all .15s;white-space:nowrap;
         background:none;border-top:none;border-left:none;border-right:none;}
.tab-btn:hover{color:#1f2937;}
.tab-btn.tab-on{color:#15803d;border-bottom-color:#15803d;font-weight:600;}

/* Document list */
.doc-row{display:flex;align-items:center;gap:9px;padding:9px 12px;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb;}
.doc-ic{width:30px;height:30px;border-radius:6px;background:#dcfce7;display:flex;align-items:center;justify-content:center;color:#15803d;font-size:10px;font-weight:700;flex-shrink:0;}
.doc-meta{font-size:11px;color:#9ca3af;}

/* Upload zone */
.upzone{border:2px dashed #d1d5db;border-radius:10px;padding:24px;text-align:center;cursor:pointer;transition:all .15s;}
.upzone:hover{border-color:#86efac;background:#f0fdf4;}

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
</style>
