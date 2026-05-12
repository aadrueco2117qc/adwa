<?php if ((int)($stats['overdue'] ?? 0) > 0): ?>
<div class="wo-banner banner-warn mb-4">
  <svg class="flex-shrink-0 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span><strong><?= (int)$stats['overdue'] ?> work order<?= (int)$stats['overdue'] !== 1 ? 's' : '' ?></strong> are past their scheduled end date.</span>
  <button class="banner-link text-red-700"
    onclick="setChip('overdue')">Review now →</button>
</div>
<?php endif; ?>

<!-- Page header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight">Work Orders</h2>
    <p class="text-sm text-gray-400 mt-0.5">Manage, assign, and track all work orders. Click any row to view details.</p>
  </div>
  <div class="flex gap-2 flex-wrap">
    <a href="calendar.php"
       class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      Calendar
    </a>
    <a href="add.php"
       class="inline-flex items-center gap-1.5 bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150">
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
      Create Work Order
    </a>
  </div>
</div>

<!-- Status chips -->
<div class="flex flex-wrap gap-2 mb-3" id="status-chips">
  <?php
  $chip_status = $filters['status'];
  $chip_defs = [
    ''            => ['All',          (int)$stats['total']],
    'new'         => ['New',          (int)$stats['wo_new']],
    'assigned'    => ['Assigned',     (int)$stats['assigned']],
    'scheduled'   => ['Scheduled',    (int)$stats['scheduled']],
    'in_progress' => ['In Progress',  (int)$stats['in_progress']],
    'on_hold'     => ['On Hold',      (int)$stats['on_hold']],
    'resolved'    => ['Resolved',     (int)$stats['resolved']],
    'closed'      => ['Closed',       (int)$stats['closed']],
    'overdue'     => ['⚠ Overdue',    (int)$stats['overdue']],
  ];
  foreach ($chip_defs as $val => $info):
    [$label, $count] = $info;
    $is_on = ($chip_status === $val);
  ?>
  <button type="button"
    id="chip-<?= $val === '' ? 'all' : $val ?>"
    onclick="setChip('<?= $val ?>')"
    class="chip <?= $is_on ? 'chip-on' : '' ?>">
    <?= $label ?> <span class="opacity-70 font-normal">(<?= $count ?>)</span>
  </button>
  <?php endforeach; ?>
</div>

<!-- Filter bar -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-3 mb-3 flex flex-wrap items-center gap-2">
  <div class="relative flex-1 min-w-48">
    <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
    </svg>
    <input type="text" id="q-input" value="<?= htmlspecialchars($filters['q']) ?>"
           placeholder="Search by WO #, ticket #, technician, notes…"
           class="fin pr-8 text-sm" />
  </div>
  <select id="type-select" class="fsel text-sm" style="width:auto;min-width:130px">
    <option value="">All Types</option>
    <?php foreach (['diagnosis'=>'Diagnosis','repair'=>'Repair','maintenance'=>'Maintenance','follow_up'=>'Follow-up'] as $k=>$v): ?>
      <option value="<?= $k ?>" <?= $filters['wo_type'] === $k ? 'selected' : '' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select>
  <select id="tech-select" class="fsel text-sm" style="width:auto;min-width:150px">
    <option value="">All Technicians</option>
    <?php foreach ($technicians as $t): ?>
      <option value="<?= $t['user_id'] ?>" <?= $filters['assigned_to'] == $t['user_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($t['full_name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <select id="priority-select" class="fsel text-sm" style="width:auto;min-width:120px">
    <option value="">All Priorities</option>
    <?php foreach (['low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'Critical'] as $k=>$v): ?>
      <option value="<?= $k ?>" <?= $filters['priority'] === $k ? 'selected' : '' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select>
</div>

<!-- Table -->
<div id="wo-table-wrap">
  <?php require __DIR__ . '/_table.php'; ?>
</div>

<!-- Stats cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
  <?php
  $cards = [
    [(int)$stats['total'],       'Total WOs',     '+'.((int)$stats['total']).' created',    'text-green-600', 'bg-green-100 text-green-700',
     'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z'],
    [(int)$stats['in_progress'], 'In Progress',   'Being worked on',   'text-blue-600',   'bg-blue-100 text-blue-700',
     'M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z'],
    [(int)$stats['on_hold'],     'On Hold',        'Awaiting action',   'text-amber-600',  'bg-amber-100 text-amber-700',
     'M14.25 9v6m-4.5 0V9M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    [(int)$stats['overdue'],     'Overdue',        'Past scheduled end','text-red-600',    'bg-red-100 text-red-700',
     'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
  ];
  foreach ($cards as [$num,$lbl,$hint,$hcls,$icls,$path]):
  ?>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
    <div class="stat-ico <?= $icls ?>">
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="<?= $path ?>"/>
      </svg>
    </div>
    <div>
      <div class="text-2xl font-bold text-gray-900 tracking-tight leading-none"><?= $num ?></div>
      <div class="text-xs text-gray-500 mt-1"><?= $lbl ?></div>
      <div class="text-xs font-medium mt-1 <?= $hcls ?>"><?= $hint ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<script>
let _currentPage   = <?= $current_page ?>;
let _currentStatus = <?= json_encode($filters['status']) ?>;
let _currentSort   = <?= json_encode($filters['sort_col']) ?>;
let _currentDir    = <?= json_encode($filters['sort_dir']) ?>;
let _debounce      = null;

function setChip(status) {
  _currentStatus = status;
  _currentPage   = 1;
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('chip-on'));
  const id = status === '' ? 'chip-all' : 'chip-' + status;
  const el = document.getElementById(id);
  if (el) el.classList.add('chip-on');
  fetchWOs();
}

function goToPage(p) {
  _currentPage = p;
  fetchWOs();
}

function sortBy(col, dir) {
  _currentSort = col;
  _currentDir  = dir;
  _currentPage = 1;
  fetchWOs();
}

function fetchWOs() {
  const params = new URLSearchParams({
    q:           document.getElementById('q-input').value,
    status:      _currentStatus,
    wo_type:     document.getElementById('type-select').value,
    assigned_to: document.getElementById('tech-select').value,
    priority:    document.getElementById('priority-select').value,
    p:           _currentPage,
    sort_col:    _currentSort,
    sort_dir:    _currentDir,
  });
  const wrap = document.getElementById('wo-table-wrap');
  wrap.style.opacity = '.5';
  fetch('search_ajax.php?' + params.toString())
    .then(r => r.text())
    .then(html => { wrap.innerHTML = html; wrap.style.opacity = '1'; })
    .catch(() => { wrap.style.opacity = '1'; });
}

document.getElementById('q-input').addEventListener('input', () => {
  clearTimeout(_debounce);
  _currentPage = 1;
  _debounce = setTimeout(fetchWOs, 280);
});

['type-select','tech-select','priority-select'].forEach(id => {
  document.getElementById(id).addEventListener('change', () => {
    _currentPage = 1;
    fetchWOs();
  });
});
</script>
