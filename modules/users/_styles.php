<style>
/* ── User module shared styles ───────────────────────────────── */

/* Status badges */
.u-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11.5px;font-weight:600;white-space:nowrap;line-height:1.4;}
.u-badge .bdot{width:6px;height:6px;border-radius:50%;flex-shrink:0;display:inline-block;}
.u-badge-active{background:#dcfce7;color:#166534;}.u-badge-active .bdot{background:#16a34a;}
.u-badge-inactive{background:#f3f4f6;color:#6b7280;}.u-badge-inactive .bdot{background:#9ca3af;}

/* Status chips */
.chip{padding:5px 13px;border-radius:20px;font-size:12.5px;font-weight:600;cursor:pointer;
      border:1.5px solid #e5e7eb;background:#fff;color:#4b5563;transition:all .15s;white-space:nowrap;display:inline-flex;align-items:center;gap:4px;}
.chip:hover{border-color:#86efac;color:#15803d;}
.chip.chip-on{background:#15803d;color:#fff;border-color:#15803d;}

/* Stat cards */
.stat-card{background:#fff;border:1px solid #f3f4f6;border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 3px rgba(0,0,0,.04);}
.stat-ico{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.stat-num{font-size:22px;font-weight:800;color:#111827;line-height:1.1;}
.stat-lbl{font-size:12px;color:#6b7280;font-weight:500;margin-top:1px;}

/* Table rows */
.u-row:hover td{background:#f0fdf4;}

/* User avatar circle */
.u-avatar{width:32px;height:32px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;color:#fff;background:#1a5c2a;}

/* Pagination */
.pg-wrap{display:flex;align-items:center;justify-content:space-between;padding:11px 16px;border-top:1px solid #f3f4f6;font-size:12.5px;color:#6b7280;}
.pg-btns{display:flex;gap:3px;}
.pg-btn{min-width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:5px;
        border:1px solid #e5e7eb;background:#fff;cursor:pointer;font-size:12.5px;font-weight:500;color:#4b5563;transition:all .12s;padding:0 5px;}
.pg-btn:hover{background:#f9fafb;}
.pg-btn.pg-on{background:#15803d;color:#fff;border-color:#15803d;}
.pg-btn:disabled{opacity:.4;cursor:not-allowed;}

/* Modal overlay */
.u-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:50;display:none;align-items:center;justify-content:center;padding:16px;}
.u-modal-overlay.open{display:flex;}
.u-modal{background:#fff;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:520px;max-height:90vh;overflow-y:auto;}
.u-modal-hdr{display:flex;align-items:center;justify-content:space-between;padding:18px 22px 0;border-bottom:1px solid #f3f4f6;padding-bottom:14px;}
.u-modal-title{font-size:15px;font-weight:700;color:#111827;}
.u-modal-body{padding:20px 22px;}
.u-modal-foot{padding:14px 22px;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end;gap:8px;}

/* Form inputs */
.flbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;display:block;margin-bottom:4px;}
.fin{width:100%;padding:8px 11px;border:1.5px solid #e5e7eb;border-radius:6px;font-size:13.5px;font-family:inherit;color:#1f2937;outline:none;transition:border .15s,box-shadow .15s;background:#fff;}
.fin:focus{border-color:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.08);}
.fin.fin-err{border-color:#dc2626;}
.fsel{width:100%;padding:8px 28px 8px 11px;border:1.5px solid #e5e7eb;border-radius:6px;font-size:13.5px;font-family:inherit;color:#1f2937;outline:none;
      background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") no-repeat right 8px center;
      appearance:none;cursor:pointer;transition:border .15s;}
.fsel:focus{border-color:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.08);}
.fsel.fsel-err{border-color:#dc2626;}
.ferr-msg{font-size:11px;color:#dc2626;display:flex;align-items:center;gap:3px;margin-top:3px;}
.fhint{font-size:11px;color:#9ca3af;margin-top:3px;}

/* Upload zone */
.upzone{border:2px dashed #d1d5db;border-radius:10px;padding:24px;text-align:center;cursor:pointer;transition:all .15s;}
.upzone:hover,.upzone.drag-over{border-color:#86efac;background:#f0fdf4;}

/* Import result rows */
.import-ok{color:#15803d;font-size:12px;padding:4px 0;}
.import-err{color:#b91c1c;font-size:12px;padding:4px 0;}

/* Bulk mode */
.bulk-col{display:none;}
.bulk-mode .bulk-col{display:table-cell;}

/* Action buttons in table rows */
.row-action{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;
            border:1px solid #e5e7eb;background:#fff;color:#6b7280;cursor:pointer;transition:all .12s;flex-shrink:0;}
.row-action:hover{border-color:#86efac;color:#15803d;background:#f0fdf4;}
.row-action.danger:hover{border-color:#fca5a5;color:#dc2626;background:#fef2f2;}

/* Back link row */
.back-row{display:flex;align-items:center;gap:6px;margin-bottom:16px;font-size:13px;color:#6b7280;}
.back-row a{color:#6b7280;text-decoration:none;display:flex;align-items:center;gap:4px;}
.back-row a:hover{color:#111827;}

/* Form section dividers */
.sdiv{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;
      padding:14px 0 8px;border-bottom:1px solid #f3f4f6;margin-bottom:12px;}

/* Flash notice */
.flash{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:8px;margin-bottom:14px;font-size:13px;font-weight:500;}
.flash-ok{background:#f0fdf4;border:1px solid #bbf7d0;border-left:3.5px solid #16a34a;color:#15803d;}
.flash-err{background:#fef2f2;border:1px solid #fee2e2;border-left:3.5px solid #dc2626;color:#b91c1c;}
</style>
