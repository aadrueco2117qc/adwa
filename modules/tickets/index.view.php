<!-- Page header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight">Request Submission & Intake</h2>
    <p class="text-sm text-gray-400 mt-0.5">Submit and track media technology repair requests.</p>
  </div>
  <div class="flex gap-2 flex-wrap">
    <a href="add.php"
       class="inline-flex items-center gap-1.5 bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150">
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
      New Ticket
    </a>
  </div>
</div>

<?php if ($is_staff): ?>
<!-- Status chips (Staff only) -->
<div class="flex flex-wrap gap-2 mb-3" id="status-chips">
  <?php
  $chip_status = $filters['status'];
  $chip_defs = [
    ''            => ['All',          (int)$stats['total']],
    'new'         => ['New',          (int)$stats['t_new']],
    'assigned'    => ['Assigned',     (int)$stats['assigned']],
    'in_progress' => ['In Progress',  (int)$stats['in_progress']],
    'on_hold'     => ['On Hold',      (int)$stats['on_hold']],
    'resolved'    => ['Resolved',     (int)$stats['resolved']],
    'closed'      => ['Closed',       (int)$stats['closed']],
    'cancelled'   => ['Cancelled',    (int)$stats['cancelled'] ?? 0],
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
<?php endif; ?>

<!-- Filter bar -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-3 mb-3 flex flex-wrap items-center gap-2">
  <div class="relative flex-1 min-w-48">
    <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
    </svg>
    <input type="text" id="q-input" value="<?= htmlspecialchars($filters['q']) ?>"
           placeholder="Search by ticket #, requester, asset tag, or title…"
           class="fin pr-8 text-sm" />
  </div>
  <?php if (!$is_staff): ?>
  <select id="status-select" class="fsel text-sm" style="width:auto;min-width:130px">
    <option value="">All Statuses</option>
    <?php foreach (['new'=>'New','assigned'=>'Assigned','in_progress'=>'In Progress','resolved'=>'Resolved','closed'=>'Closed','cancelled'=>'Cancelled'] as $k=>$v): ?>
      <option value="<?= $k ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select>
  <?php endif; ?>
  <select id="priority-select" class="fsel text-sm" style="width:auto;min-width:120px">
    <option value="">All Priorities</option>
    <?php foreach (['low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'Critical'] as $k=>$v): ?>
      <option value="<?= $k ?>" <?= $filters['priority'] === $k ? 'selected' : '' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select>
</div>

<!-- Table -->
<div id="tk-table-wrap">
  <?php require __DIR__ . '/_table.php'; ?>
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
  fetchTickets();
}

function goToPage(p) {
  _currentPage = p;
  fetchTickets();
}

function sortBy(col, dir) {
  _currentSort = col;
  _currentDir  = dir;
  _currentPage = 1;
  fetchTickets();
}

function fetchTickets() {
  const params = new URLSearchParams({
    q:        document.getElementById('q-input').value,
    status:   document.getElementById('status-select') ? document.getElementById('status-select').value : _currentStatus,
    priority: document.getElementById('priority-select').value,
    p:        _currentPage,
    sort_col: _currentSort,
    sort_dir: _currentDir,
  });
  const wrap = document.getElementById('tk-table-wrap');
  wrap.style.opacity = '.5';
  fetch('search_ajax.php?' + params.toString())
    .then(r => r.text())
    .then(html => { wrap.innerHTML = html; wrap.style.opacity = '1'; })
    .catch(() => { wrap.style.opacity = '1'; });
}

document.getElementById('q-input').addEventListener('input', () => {
  clearTimeout(_debounce);
  _currentPage = 1;
  _debounce = setTimeout(fetchTickets, 280);
});

['status-select', 'priority-select'].forEach(id => {
  const el = document.getElementById(id);
  if (el) {
    el.addEventListener('change', () => {
      if (id === 'status-select') _currentStatus = el.value;
      _currentPage = 1;
      fetchTickets();
    });
  }
});
</script>
