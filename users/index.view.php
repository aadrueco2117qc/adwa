<?php
// modules/users/index.view.php — User Access Control listing view.
$csrf = htmlspecialchars($_SESSION['csrf_token'] ??= bin2hex(random_bytes(16)));
?>

<?php if ($flash_ok): ?>
<div class="flash flash-ok mb-4">
  <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
  </svg>
  <span><?= htmlspecialchars($flash_ok) ?></span>
</div>
<?php endif; ?>
<?php if ($flash_err): ?>
<div class="flash flash-err mb-4">
  <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span><?= htmlspecialchars($flash_err) ?></span>
</div>
<?php endif; ?>

<!-- Stats row -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
  <div class="stat-card">
    <div class="stat-ico bg-green-50">
      <svg class="w-5 h-5 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
      </svg>
    </div>
    <div>
      <div class="stat-num"><?= (int)$stats['total'] ?></div>
      <div class="stat-lbl">Total Users</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-ico bg-green-50">
      <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
      </svg>
    </div>
    <div>
      <div class="stat-num"><?= (int)$stats['active'] ?></div>
      <div class="stat-lbl">Active</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-ico bg-gray-50">
      <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>
      </svg>
    </div>
    <div>
      <div class="stat-num"><?= (int)$stats['inactive'] ?></div>
      <div class="stat-lbl">Inactive</div>
    </div>
  </div>
  <!-- Role breakdown mini card -->
  <div class="stat-card flex-col items-start gap-2">
    <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">By Role</div>
    <div class="flex flex-wrap gap-1.5">
      <?php foreach ($stats['by_role'] as $r): if ((int)$r['cnt'] === 0) continue; ?>
        <span class="text-xs bg-gray-100 text-gray-600 font-medium px-2 py-0.5 rounded-full">
          <?= htmlspecialchars(role_display_name($r['role_name'])) ?>
          <span class="font-bold"><?= (int)$r['cnt'] ?></span>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Page header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight">User Access Control</h2>
    <p class="text-sm text-gray-400 mt-0.5">Manage user accounts, roles, and permissions.</p>
  </div>
  <div class="flex gap-2 flex-wrap">
    <a href="add.php"
       class="inline-flex items-center gap-1.5 bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150">
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
      </svg>
      Add User
    </a>
    <button type="button" onclick="openImport()"
      class="inline-flex items-center gap-1.5 border border-olfu-green text-olfu-green hover:bg-green-50 text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150">
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
      </svg>
      Import CSV
    </button>
    <button type="button" id="bulk-toggle-btn" onclick="enterBulkMode()"
      class="inline-flex items-center gap-1.5 border border-gray-200 text-gray-600 hover:border-gray-400 hover:text-gray-800 text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150">
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
      </svg>
      Bulk Update
    </button>
  </div>
</div>

<!-- Status chips -->
<div class="flex flex-wrap gap-2 mb-3" id="status-chips">
  <?php
  $chip_status = $filters['status'];
  $chip_defs = [
    ''         => ['All',      (int)$stats['total']],
    'active'   => ['Active',   (int)$stats['active']],
    'inactive' => ['Inactive', (int)$stats['inactive']],
  ];
  foreach ($chip_defs as $val => $info):
    [$label, $count] = $info;
    $is_on = ($chip_status === $val);
  ?>
  <button type="button"
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
           placeholder="Search by name, email, ID number, position…"
           class="fin pr-8 text-sm" />
  </div>
  <select id="role-select" class="fsel text-sm" style="width:auto;min-width:140px">
    <option value="">All Roles</option>
    <?php foreach ($roles as $r): ?>
      <option value="<?= $r['role_id'] ?>" <?= $filters['role_id'] == $r['role_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars(role_display_name($r['role_name'])) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <select id="dept-select" class="fsel text-sm" style="width:auto;min-width:150px">
    <option value="">All Departments</option>
    <?php foreach ($departments as $d): ?>
      <option value="<?= $d['department_id'] ?>" <?= $filters['dept_id'] == $d['department_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($d['department_name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

<!-- Bulk action bar (hidden until bulk mode) -->
<div id="bulk-bar" class="hidden mb-3 bg-olfu-green text-white rounded-xl px-4 py-2.5 flex items-center gap-3 flex-wrap">
  <span class="text-sm font-semibold" id="bulk-count-label">0 users selected</span>
  <div class="flex-1"></div>
  <button type="button" onclick="openBulkModal()"
    class="bg-white text-olfu-green text-xs font-bold px-3 py-1.5 rounded-lg hover:bg-green-50 transition-colors">
    Apply Bulk Update
  </button>
  <button type="button" onclick="exitBulkMode()"
    class="text-white/80 hover:text-white text-xs font-semibold px-2 py-1.5 rounded-lg hover:bg-white/10 transition-colors">
    Cancel
  </button>
</div>

<!-- Table -->
<div id="user-table-wrap">
  <?php require __DIR__ . '/_table.php'; ?>
</div>

<!-- ── IMPORT CSV MODAL ─────────────────────────────────────── -->
<div id="import-modal" class="u-modal-overlay" onclick="if(event.target===this)closeImport()">
  <div class="u-modal" style="max-width:560px">
    <div class="u-modal-hdr">
      <span class="u-modal-title">Import Users from CSV</span>
      <button onclick="closeImport()" class="w-7 h-7 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="u-modal-body">
      <!-- Info -->
      <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 mb-4 text-xs text-blue-700">
        <strong>Required columns:</strong> full_name, email, role, password<br>
        <strong>Optional:</strong> id_number, contact_number, position, department<br>
        Roles: <code class="bg-blue-100 px-1 rounded">admin</code>
               <code class="bg-blue-100 px-1 rounded">it_manager</code>
               <code class="bg-blue-100 px-1 rounded">it_staff</code>
               <code class="bg-blue-100 px-1 rounded">technician</code>
               <code class="bg-blue-100 px-1 rounded">faculty</code>
               <code class="bg-blue-100 px-1 rounded">department_staff</code>
               <code class="bg-blue-100 px-1 rounded">student</code>
      </div>
      <a href="<?= BASE_URL ?>public/assets/sample_users_import.csv" download
         class="inline-flex items-center gap-1.5 text-xs text-olfu-green font-semibold mb-4 hover:underline">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        Download sample CSV template
      </a>
      <!-- Drop zone -->
      <div id="import-dropzone" class="upzone" onclick="document.getElementById('import-file').click()"
           ondragover="event.preventDefault();this.classList.add('drag-over')"
           ondragleave="this.classList.remove('drag-over')"
           ondrop="handleImportDrop(event)">
        <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
        </svg>
        <p class="text-sm text-gray-500 font-medium" id="import-file-label">Drop CSV here or <span class="text-olfu-green font-semibold">click to browse</span></p>
        <p class="text-xs text-gray-400 mt-1">Max 5 MB</p>
      </div>
      <input type="file" id="import-file" accept=".csv" class="hidden" onchange="previewImportFile(this)">

      <!-- Results area -->
      <div id="import-results" class="hidden mt-4">
        <div id="import-summary" class="text-sm font-semibold text-gray-800 mb-2"></div>
        <div id="import-log" class="max-h-40 overflow-y-auto text-xs space-y-0.5"></div>
      </div>
    </div>
    <div class="u-modal-foot">
      <button onclick="closeImport()" class="px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Close</button>
      <button id="import-btn" onclick="submitImport()" disabled
        class="px-4 py-2 text-sm font-semibold text-white bg-olfu-green rounded-lg hover:bg-olfu-green-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
        Import
      </button>
    </div>
  </div>
</div>

<!-- ── BULK UPDATE MODAL ────────────────────────────────────── -->
<div id="bulk-modal" class="u-modal-overlay" onclick="if(event.target===this)closeBulkModal()">
  <div class="u-modal" style="max-width:420px">
    <div class="u-modal-hdr">
      <span class="u-modal-title">Bulk Update</span>
      <button onclick="closeBulkModal()" class="w-7 h-7 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="u-modal-body space-y-4">
      <p class="text-sm text-gray-500">Applies to <strong id="bulk-modal-count">0</strong> selected user(s).</p>
      <div>
        <label class="flbl">Field to update</label>
        <select id="bulk-field" class="fsel text-sm" onchange="updateBulkValueField()">
          <option value="">— Select field —</option>
          <option value="role_id">Role</option>
          <option value="department_id">Department</option>
          <option value="is_active">Status</option>
        </select>
      </div>
      <div id="bulk-value-wrap" class="hidden">
        <label class="flbl">New value</label>
        <select id="bulk-value-role"   class="fsel text-sm hidden">
          <option value="">— Select role —</option>
          <?php foreach ($roles as $r): ?>
            <option value="<?= $r['role_id'] ?>"><?= htmlspecialchars(role_display_name($r['role_name'])) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="bulk-value-dept" class="fsel text-sm hidden">
          <option value="">— Clear department —</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= $d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="bulk-value-status" class="fsel text-sm hidden">
          <option value="1">Active</option>
          <option value="0">Inactive</option>
        </select>
      </div>
    </div>
    <div class="u-modal-foot">
      <button onclick="closeBulkModal()" class="px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Cancel</button>
      <button id="bulk-apply-btn" onclick="applyBulkUpdate()"
        class="px-4 py-2 text-sm font-semibold text-white bg-olfu-green rounded-lg hover:bg-olfu-green-md transition-colors">
        Apply
      </button>
    </div>
  </div>
</div>

<script>
// ── State ─────────────────────────────────────────────────────
let bulkMode   = false;
let currentFilters = {
  q:        <?= json_encode($filters['q']) ?>,
  role_id:  <?= json_encode((string)$filters['role_id']) ?>,
  dept_id:  <?= json_encode((string)$filters['dept_id']) ?>,
  status:   <?= json_encode($filters['status']) ?>,
  sort_col: <?= json_encode($filters['sort_col']) ?>,
  sort_dir: <?= json_encode($filters['sort_dir']) ?>,
  p:        1,
};
const csrf = <?= json_encode($_SESSION['csrf_token'] ??= bin2hex(random_bytes(16))) ?>;

// ── Filtering & AJAX ──────────────────────────────────────────
let searchTimer;
document.getElementById('q-input').addEventListener('input', function() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { currentFilters.q = this.value; currentFilters.p = 1; fetchTable(); }, 300);
});
document.getElementById('role-select').addEventListener('change', function() {
  currentFilters.role_id = this.value; currentFilters.p = 1; fetchTable();
});
document.getElementById('dept-select').addEventListener('change', function() {
  currentFilters.dept_id = this.value; currentFilters.p = 1; fetchTable();
});

function setChip(val) {
  currentFilters.status = val;
  currentFilters.p = 1;
  fetchTable();
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('chip-on'));
  const chips = document.querySelectorAll('.chip');
  const vals  = ['', 'active', 'inactive'];
  const idx   = vals.indexOf(val);
  if (idx >= 0) chips[idx].classList.add('chip-on');
}

function sortBy(col, dir) {
  currentFilters.sort_col = col;
  currentFilters.sort_dir = dir;
  fetchTable();
}

function goPage(p) {
  currentFilters.p = p;
  fetchTable();
}

function fetchTable() {
  const params = new URLSearchParams(currentFilters);
  fetch('search_ajax.php?' + params)
    .then(r => r.text())
    .then(html => {
      document.getElementById('user-table-wrap').innerHTML = html;
      if (bulkMode) {
        document.getElementById('user-table-wrap').querySelector('table')?.classList.add('bulk-mode');
        updateBulkCount();
      }
    });
}

// ── Bulk mode ─────────────────────────────────────────────────
function enterBulkMode() {
  bulkMode = true;
  document.getElementById('user-table-wrap').querySelector('table')?.classList.add('bulk-mode');
  document.getElementById('bulk-bar').classList.remove('hidden');
  document.getElementById('bulk-toggle-btn').classList.add('hidden');
  updateBulkCount();
}

function exitBulkMode() {
  bulkMode = false;
  document.getElementById('user-table-wrap').querySelector('table')?.classList.remove('bulk-mode');
  document.getElementById('bulk-bar').classList.add('hidden');
  document.getElementById('bulk-toggle-btn').classList.remove('hidden');
  document.querySelectorAll('.bulk-chk').forEach(c => c.checked = false);
  const selectAll = document.getElementById('select-all-chk');
  if (selectAll) selectAll.checked = false;
  updateBulkCount();
}

function updateBulkCount() {
  const n = document.querySelectorAll('.bulk-chk:checked').length;
  document.getElementById('bulk-count-label').textContent = n + ' user' + (n !== 1 ? 's' : '') + ' selected';
  document.getElementById('bulk-modal-count').textContent = n;
}

function selectAllUsers(checked) {
  document.querySelectorAll('.bulk-chk').forEach(c => c.checked = checked);
  updateBulkCount();
}

function openBulkModal() {
  const n = document.querySelectorAll('.bulk-chk:checked').length;
  if (n === 0) { alert('Select at least one user.'); return; }
  document.getElementById('bulk-modal-count').textContent = n;
  document.getElementById('bulk-field').value = '';
  document.getElementById('bulk-value-wrap').classList.add('hidden');
  document.getElementById('bulk-modal').classList.add('open');
}
function closeBulkModal() {
  document.getElementById('bulk-modal').classList.remove('open');
}

function updateBulkValueField() {
  const field = document.getElementById('bulk-field').value;
  const wrap  = document.getElementById('bulk-value-wrap');
  ['bulk-value-role','bulk-value-dept','bulk-value-status'].forEach(id =>
    document.getElementById(id).classList.add('hidden'));
  if (!field) { wrap.classList.add('hidden'); return; }
  wrap.classList.remove('hidden');
  const map = { role_id: 'bulk-value-role', department_id: 'bulk-value-dept', is_active: 'bulk-value-status' };
  if (map[field]) document.getElementById(map[field]).classList.remove('hidden');
}

function applyBulkUpdate() {
  const field  = document.getElementById('bulk-field').value;
  if (!field) { alert('Select a field to update.'); return; }
  const map    = { role_id: 'bulk-value-role', department_id: 'bulk-value-dept', is_active: 'bulk-value-status' };
  const valEl  = document.getElementById(map[field]);
  const value  = valEl ? valEl.value : '';
  const ids    = [...document.querySelectorAll('.bulk-chk:checked')].map(c => c.value);

  const fd = new FormData();
  fd.append('csrf_token', csrf);
  fd.append('field', field);
  fd.append('value', value);
  ids.forEach(id => fd.append('user_ids[]', id));

  document.getElementById('bulk-apply-btn').disabled = true;
  fetch('bulk_update.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      closeBulkModal();
      exitBulkMode();
      fetchTable();
      if (data.success) {
        showFlash('Updated ' + data.updated + ' user(s) successfully.', 'ok');
      } else {
        showFlash(data.message || 'Bulk update failed.', 'err');
      }
    })
    .finally(() => { document.getElementById('bulk-apply-btn').disabled = false; });
}

// ── Toggle active ─────────────────────────────────────────────
function toggleActive(userId, newActive, btn) {
  const fd = new FormData();
  fd.append('csrf_token', csrf);
  fd.append('user_id', userId);
  fd.append('active', newActive);
  btn.disabled = true;
  fetch('toggle_active.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        fetchTable();
        showFlash('User ' + (newActive ? 'activated' : 'deactivated') + '.', 'ok');
      } else {
        showFlash(data.message || 'Action failed.', 'err');
        btn.disabled = false;
      }
    });
}

// ── Import CSV ────────────────────────────────────────────────
let importFile = null;

function openImport() {
  importFile = null;
  document.getElementById('import-file').value = '';
  document.getElementById('import-file-label').innerHTML = 'Drop CSV here or <span class="text-olfu-green font-semibold">click to browse</span>';
  document.getElementById('import-results').classList.add('hidden');
  document.getElementById('import-btn').disabled = true;
  document.getElementById('import-modal').classList.add('open');
}
function closeImport() {
  document.getElementById('import-modal').classList.remove('open');
  if (importFile) fetchTable();
  importFile = null;
}

function handleImportDrop(e) {
  e.preventDefault();
  document.getElementById('import-dropzone').classList.remove('drag-over');
  const file = e.dataTransfer.files[0];
  if (file) { importFile = file; updateImportLabel(file.name); document.getElementById('import-btn').disabled = false; }
}

function previewImportFile(input) {
  if (input.files[0]) {
    importFile = input.files[0];
    updateImportLabel(importFile.name);
    document.getElementById('import-btn').disabled = false;
    document.getElementById('import-results').classList.add('hidden');
  }
}

function updateImportLabel(name) {
  document.getElementById('import-file-label').innerHTML =
    '<svg class="w-4 h-4 inline text-green-600 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
    + '<span class="font-semibold text-gray-800">' + name + '</span>';
}

function submitImport() {
  if (!importFile) return;
  const fd = new FormData();
  fd.append('csrf_token', csrf);
  fd.append('csv_file', importFile);

  const btn = document.getElementById('import-btn');
  btn.disabled = true;
  btn.textContent = 'Importing…';

  fetch('import_csv.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      const resDiv = document.getElementById('import-results');
      const sumDiv = document.getElementById('import-summary');
      const logDiv = document.getElementById('import-log');
      resDiv.classList.remove('hidden');

      if (!data.success) {
        sumDiv.textContent = data.message;
        sumDiv.className = 'text-sm font-semibold text-red-600 mb-2';
        logDiv.innerHTML = '';
        return;
      }
      sumDiv.textContent = data.imported + ' user(s) imported' +
        (data.skipped.length ? ', ' + data.skipped.length + ' skipped.' : '.');
      sumDiv.className = 'text-sm font-semibold text-gray-800 mb-2';

      let html = '';
      data.skipped.forEach(s => {
        html += '<div class="import-err">⚠ Row ' + s.row + ' (' + s.email + '): ' + s.reasons.join('; ') + '</div>';
      });
      logDiv.innerHTML = html;
    })
    .finally(() => {
      btn.textContent = 'Import';
      btn.disabled = false;
      importFile = null;
    });
}

// ── Flash message ─────────────────────────────────────────────
function showFlash(msg, type) {
  const existing = document.getElementById('js-flash');
  if (existing) existing.remove();
  const cls = type === 'ok' ? 'flash-ok' : 'flash-err';
  const icon = type === 'ok'
    ? '<svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
    : '<svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01"/></svg>';
  const div = document.createElement('div');
  div.id = 'js-flash';
  div.className = 'flash ' + cls;
  div.innerHTML = icon + '<span>' + msg + '</span>';
  document.querySelector('main').prepend(div);
  setTimeout(() => div.remove(), 4000);
}
</script>
