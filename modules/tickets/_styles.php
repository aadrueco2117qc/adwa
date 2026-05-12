<style>
/* modules/tickets/_styles.php */
.wo-badge { display: inline-flex; items-center; gap: 4px; padding: 2px 8px; border-radius: 9999px; font-weight: 600; font-size: 0.75rem; white-space: nowrap; }
.bdot { width: 6px; height: 6px; border-radius: 50%; opacity: 0.8; }
.badge-new { background-color: #f3f4f6; color: #4b5563; }
.badge-new .bdot { background-color: #6b7280; }
.badge-assigned { background-color: #dbeafe; color: #1e40af; }
.badge-assigned .bdot { background-color: #2563eb; }
.badge-scheduled { background-color: #fef3c7; color: #b45309; }
.badge-scheduled .bdot { background-color: #d97706; }
.badge-in_progress { background-color: #e0e7ff; color: #3730a3; }
.badge-in_progress .bdot { background-color: #4f46e5; }
.badge-on_hold { background-color: #fee2e2; color: #991b1b; }
.badge-on_hold .bdot { background-color: #dc2626; }
.badge-resolved { background-color: #dcfce7; color: #166534; }
.badge-resolved .bdot { background-color: #16a34a; }
.badge-closed { background-color: #f3f4f6; color: #4b5563; }
.badge-closed .bdot { background-color: #9ca3af; }
.badge-cancelled { background-color: #f3f4f6; color: #4b5563; }
.badge-cancelled .bdot { background-color: #9ca3af; }

.badge-priority {  }
.badge-critical { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.badge-high { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
.badge-medium { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
.badge-low { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }

/* Filter components */
.fin, .fsel { 
  width: 100%; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.375rem 0.75rem; 
  outline: none; transition: border-color 0.15s, box-shadow 0.15s; background: #fff; 
}
.fin:focus, .fsel:focus { border-color: #1a5c2a; box-shadow: 0 0 0 3px rgba(26,92,42,0.1); }
.fin::placeholder { color: #9ca3af; }
.fsel { appearance: none; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem; }

/* Chips */
.chip { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.875rem; font-size: 0.8125rem; font-weight: 500; color: #4b5563; background: #fff; border: 1px solid #e5e7eb; border-radius: 9999px; transition: all 0.15s; }
.chip:hover { border-color: #d1d5db; background: #f9fafb; }
.chip-on { color: #1a5c2a; background: #f0fdf4; border-color: #86efac; }
.chip-on:hover { background: #dcfce7; }

/* Table styles */
.wo-table { width: 100%; text-align: left; border-collapse: separate; border-spacing: 0; }
.wo-table th { padding: 0.875rem 1rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; border-bottom: 1px solid #e5e7eb; background: #f9fafb; white-space: nowrap; }
.wo-table th:first-child { border-top-left-radius: 0.75rem; }
.wo-table th:last-child { border-top-right-radius: 0.75rem; }
.wo-table td { padding: 0.875rem 1rem; vertical-align: top; border-bottom: 1px solid #e5e7eb; transition: background-color 0.15s; }
.wo-table tr:last-child td { border-bottom: none; }
.wo-table tbody tr { cursor: pointer; }
.wo-table tbody tr:hover td { background-color: #f0fdf4; }

.trow-link { display: contents; }
.sort-btn { cursor: pointer; display: inline-flex; align-items: center; gap: 4px; }
.sort-btn:hover { color: #111827; }
</style>
