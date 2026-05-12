Color Palette
Name	                Hex	                   Used for
OLFU Green	          #1a5c2a	              Primary brand color — buttons, active states, headers
OLFU Green Mid	      #1f6e32	              Hover state on green buttons
OLFU Green Light      #256b38	              Accent variant
#15803d	         Tailwind green-700       Asset tags, active badges, tab underlines, right panel header
#16a34a	         Tailwind green-600       Active badge dot
#dcfce7	         Tailwind green-100       Active badge background, doc icon background
#86efac             Tailwind green-300       Hover borders, chip hover
#f0fdf4	         Tailwind green-50        Hover row background, dropzone hover
Status badge colors:

Status	Background	Text
Active	#dcfce7	#166534
Spare	#fef3c7	#b45309
Retired	#f3f4f6	#4b5563
Warning	#fef2f2	#b91c1c
Neutrals (Tailwind grays): #111827 #1f2937 #4b5563 #6b7280 #9ca3af #d1d5db #e5e7eb #f3f4f6 #f9fafb


<!-- Implemented at @public/assets/css/typography.css -->

/* ── Font weight reference ────────────────────────────────────
   300  — Light       thin labels, secondary text
   400  — Regular     body text, table cells, descriptions
   500  — Medium      nav items, filter labels, metadata
   600  — SemiBold    buttons, badges, field values, subheadings
   700  — Bold        section headings, card titles, column headers
   800  — ExtraBold   page titles, stat numbers
   ─────────────────────────────────────────────────────────── */

/* ── Usage map ───────────────────────────────────────────────

   Page title (e.g. "Asset Registry")      → font-bold (700)     text-xl/2xl
   Card / section heading                   → font-bold (700)     text-base
   Column header (table <th>)               → font-bold (700)     text-xs uppercase tracking-wider
   Stat number (e.g. "128 assets")          → font-extrabold (800) text-2xl/3xl
   Subheading / group label                 → font-semibold (600)  text-sm
   Button text                              → font-semibold (600)  text-sm
   Badge / chip text                        → font-semibold (600)  text-xs
   Field value (e.g. model, serial)         → font-medium (500)   text-sm
   Body / description text                  → font-normal (400)   text-sm
   Metadata (date, file size, hints)        → font-normal (400)   text-xs text-gray-400
   Placeholder / empty state                → font-normal (400)   text-sm italic text-gray-400
   Asset tag (monospace override)           → font-mono font-semibold (600) text-xs text-olfu-green

   ─────────────────────────────────────────────────────────── */
