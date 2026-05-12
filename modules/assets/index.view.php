<?php if ($expiring > 0): ?>
<div class="asset-banner banner-warn mb-4">
  <svg class="flex-shrink-0 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span><strong><?= $expiring ?> asset<?= $expiring !== 1 ? 's' : '' ?></strong> have warranties expiring within 30 days.</span>
  <button class="banner-link text-red-700"
    onclick="document.getElementById('chip-expiring').click()">Review now →</button>
</div>
<?php endif; ?>

<!-- Page header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight">Asset Registry</h2>
    <p class="text-sm text-gray-400 mt-0.5">Manage and track all media and AV equipment. Click any row to view details.</p>
  </div>
  <div class="flex gap-2 flex-wrap">
    <a href="add.php"
       class="inline-flex items-center gap-1.5 bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150">
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
      Add Asset
    </a>
    <button type="button" onclick="openImport()"
      class="inline-flex items-center gap-1.5 border border-olfu-green text-olfu-green hover:bg-green-50 text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150">
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
      Import CSV
    </button>
    <button type="button" id="bulk-toggle-btn" onclick="enterBulkMode()"
      class="inline-flex items-center gap-1.5 border border-gray-200 text-gray-600 hover:border-gray-400 hover:text-gray-800 text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150">
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
      Bulk Update
    </button>
    <button type="button" onclick="openLocationsModal()"
      class="inline-flex items-center gap-1.5 border border-gray-200 text-gray-600 hover:border-gray-400 hover:text-gray-800 text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150">
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3"/></svg>
      Manage Locations
    </button>
    <button type="button" onclick="openCategoriesModal()"
      class="inline-flex items-center gap-1.5 border border-gray-200 text-gray-600 hover:border-gray-400 hover:text-gray-800 text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150">
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
      Manage Categories
    </button>
  </div>
</div>

<!-- Status chips -->
<div class="flex flex-wrap gap-2 mb-3" id="status-chips">
  <?php
  $chip_status = $filters['status'];
  $chip_defs = [
    ''         => ['All',              (int)$stats['total']],
    'active'   => ['Active',           (int)$stats['active']],
    'spare'    => ['Spare',            (int)$stats['spare']],
    'retired'  => ['Retired',          (int)$stats['retired']],
    'expiring' => ['⚠ Expiring Soon',  $expiring],
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
           placeholder="Search by asset tag, model, serial number…"
           class="fin pr-8 text-sm" />
  </div>
  <select id="cat-select" class="fsel text-sm" style="width:auto;min-width:140px">
    <option value="">All Categories</option>
    <?php foreach ($categories as $c): ?>
      <option value="<?= $c['category_id'] ?>" <?= $filters['category_id'] == $c['category_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($c['category_name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <select id="bld-select" class="fsel text-sm" style="width:auto;min-width:140px">
    <option value="">All Buildings</option>
    <?php foreach ($buildings as $b): ?>
      <option value="<?= htmlspecialchars($b) ?>" <?= $filters['building'] === $b ? 'selected' : '' ?>>
        <?= htmlspecialchars($b) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <select id="flr-select" class="fsel text-sm" style="width:auto;min-width:130px">
    <option value="">All Floors</option>
    <?php foreach ($floors as $f): ?>
      <option value="<?= htmlspecialchars($f) ?>" <?= $filters['floor'] === $f ? 'selected' : '' ?>>
        <?= htmlspecialchars($f) ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

<!-- Table -->
<div id="asset-table-wrap">
  <?php require __DIR__ . '/_table.php'; ?>
</div>

<!-- Stats cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
  <?php
  $cards = [
    ['total',    (int)$stats['total'],   'Total Assets',        '+'.((int)$stats['total']).' registered',   'text-green-600', 'bg-green-100 text-green-700',
     'M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z'],
    ['active',   (int)$stats['active'],  'Active',              (int)$stats['total'] ? round((int)$stats['active']/(int)$stats['total']*100).'% of fleet' : '—', 'text-green-600', 'bg-blue-100 text-blue-700',
     'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    ['expiring', $expiring,              'Expiring Warranties', 'Within 30 days',     'text-red-600',   'bg-amber-100 text-amber-700',
     'M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z'],
    ['retired',  (int)$stats['retired'], 'Retired',             'End-of-life assets', 'text-gray-400',  'bg-gray-100 text-gray-600',
     'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
  ];
  foreach ($cards as [,$num,$lbl,$hint,$hcls,$icls,$path]):
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
  fetchAssets();
}

function goToPage(p) {
  _currentPage = p;
  fetchAssets();
}

function sortBy(col, dir) {
  _currentSort = col;
  _currentDir  = dir;
  _currentPage = 1;
  fetchAssets();
}

function fetchAssets() {
  const params = new URLSearchParams({
    q:           document.getElementById('q-input').value,
    status:      _currentStatus,
    category_id: document.getElementById('cat-select').value,
    building:    document.getElementById('bld-select').value,
    floor:       document.getElementById('flr-select').value,
    p:           _currentPage,
    sort_col:    _currentSort,
    sort_dir:    _currentDir,
  });
  const wrap = document.getElementById('asset-table-wrap');
  wrap.style.opacity = '.5';
  fetch('search_ajax.php?' + params.toString())
    .then(r => r.text())
    .then(html => { wrap.innerHTML = html; wrap.style.opacity = '1'; })
    .catch(() => { wrap.style.opacity = '1'; });
}

document.getElementById('q-input').addEventListener('input', () => {
  clearTimeout(_debounce);
  _currentPage = 1;
  _debounce = setTimeout(fetchAssets, 280);
});

['cat-select','bld-select','flr-select'].forEach(id => {
  document.getElementById(id).addEventListener('change', () => {
    _currentPage = 1;
    fetchAssets();
  });
});

// ── Bulk Update ───────────────────────────────────────────────
<?php
$bulk_loc_data = [];
foreach ($all_locations as $l) {
    $bulk_loc_data[$l['building']][$l['floor']][] = ['id' => $l['location_id'], 'room' => $l['room']];
}
?>
const bulkLocData = <?= json_encode($bulk_loc_data) ?>;

function bulkPopulateFloors() {
  const b    = document.getElementById('bulk-building').value;
  const fsel = document.getElementById('bulk-floor');
  fsel.innerHTML = '<option value="">— Select floor —</option>';
  fsel.disabled  = !b;
  if (b && bulkLocData[b]) {
    Object.keys(bulkLocData[b]).forEach(f => {
      const opt = document.createElement('option');
      opt.value = f; opt.textContent = f;
      fsel.appendChild(opt);
    });
  }
  bulkPopulateRooms();
}

function bulkPopulateRooms() {
  const b    = document.getElementById('bulk-building').value;
  const f    = document.getElementById('bulk-floor').value;
  const rsel = document.getElementById('bulk-location');
  rsel.innerHTML = '<option value="">— Select room —</option>';
  rsel.disabled  = !f;
  if (b && f && bulkLocData[b] && bulkLocData[b][f]) {
    bulkLocData[b][f].forEach(r => {
      const opt = document.createElement('option');
      opt.value = r.id; opt.textContent = r.room;
      rsel.appendChild(opt);
    });
  }
}
let _bulkMode = false;

function enterBulkMode() {
  _bulkMode = true;
  document.querySelectorAll('.bulk-col').forEach(el => el.classList.remove('hidden'));
  document.getElementById('bulk-bar').classList.remove('hidden');
  document.getElementById('bulk-toggle-btn').innerHTML = `
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
    </svg> Exit Bulk Mode`;
  document.getElementById('bulk-toggle-btn').onclick = exitBulkMode;
  document.getElementById('bulk-toggle-btn').className =
    'inline-flex items-center gap-1.5 border border-red-200 text-red-600 hover:bg-red-50 text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150';
  updateBulkCount();
}

function exitBulkMode() {
  _bulkMode = false;
  document.querySelectorAll('.bulk-col').forEach(el => el.classList.add('hidden'));
  document.getElementById('bulk-bar').classList.add('hidden');
  document.querySelectorAll('.bulk-chk').forEach(c => c.checked = false);
  const sac = document.getElementById('select-all-chk');
  if (sac) sac.checked = false;
  const btn = document.getElementById('bulk-toggle-btn');
  btn.innerHTML = `
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
    </svg> Bulk Update`;
  btn.onclick = enterBulkMode;
  btn.className = 'inline-flex items-center gap-1.5 border border-gray-200 text-gray-600 hover:border-gray-400 hover:text-gray-800 text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150';
}

function handleRowClick(event, id) {
  if (_bulkMode) {
    // In bulk mode: clicking a row toggles its checkbox
    const chk = event.currentTarget.querySelector('.bulk-chk');
    if (chk && event.target !== chk) { chk.checked = !chk.checked; updateBulkCount(); }
  } else {
    window.location = 'view.php?id=' + id;
  }
}

function updateBulkCount() {
  const checked = document.querySelectorAll('.bulk-chk:checked').length;
  document.getElementById('bulk-count').textContent = checked;
  document.getElementById('bulk-apply-btn').disabled = checked === 0;
  // Sync select-all checkbox state
  const all  = document.querySelectorAll('.bulk-chk').length;
  const sac  = document.getElementById('select-all-chk');
  if (sac) sac.checked = all > 0 && checked === all;
}

function selectAllAssets(checked) {
  document.querySelectorAll('.bulk-chk').forEach(c => c.checked = checked);
  updateBulkCount();
}

function openBulkModal() {
  const count = document.querySelectorAll('.bulk-chk:checked').length;
  if (count === 0) return;
  document.getElementById('bulk-modal-count').textContent = count;
  document.getElementById('bulk-modal').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
  switchBulkTab('status'); // reset to first tab
}

function closeBulkModal() {
  document.getElementById('bulk-modal').classList.add('hidden');
  document.body.style.overflow = '';
  document.getElementById('bulk-result').classList.add('hidden');
}

function switchBulkTab(tab) {
  document.querySelectorAll('.bulk-tab-btn').forEach(b => {
    b.classList.toggle('border-olfu-green', b.dataset.tab === tab);
    b.classList.toggle('text-olfu-green',   b.dataset.tab === tab);
    b.classList.toggle('border-transparent', b.dataset.tab !== tab);
    b.classList.toggle('text-gray-500',      b.dataset.tab !== tab);
  });
  document.querySelectorAll('.bulk-tab-pane').forEach(p => {
    p.classList.toggle('hidden', p.dataset.pane !== tab);
  });
}

function submitBulkUpdate() {
  const ids    = [...document.querySelectorAll('.bulk-chk:checked')].map(c => c.value);
  const active = document.querySelector('.bulk-tab-btn:not(.border-transparent)');
  if (!active || ids.length === 0) return;

  const tab    = active.dataset.tab;
  let value    = '';
  if (tab === 'status')      value = document.getElementById('bulk-status').value;
  if (tab === 'location_id') value = document.getElementById('bulk-location').value;
  if (tab === 'owner_id')    value = document.getElementById('bulk-owner').value;
  if (tab === 'department_id') value = document.getElementById('bulk-department').value;

  const btn = document.getElementById('bulk-submit-btn');
  btn.disabled = true;
  btn.textContent = 'Saving…';

  const body = new FormData();
  body.append('csrf_token', <?= json_encode($_SESSION['csrf_token'] ??= bin2hex(random_bytes(16))) ?>);
  body.append('field', tab);
  body.append('value', value);
  ids.forEach(id => body.append('asset_ids[]', id));

  fetch('bulk_update.php', { method: 'POST', body })
    .then(r => r.json())
    .then(data => {
      const result = document.getElementById('bulk-result');
      result.classList.remove('hidden', 'text-red-600', 'text-green-700');
      if (!data.success) {
        result.classList.add('text-red-600');
        result.textContent = data.message;
      } else {
        result.classList.add('text-green-700');
        let msg = `${data.updated} asset${data.updated !== 1 ? 's' : ''} updated successfully.`;
        if (data.skipped.length > 0) {
          msg += ` ${data.skipped.length} skipped (open tickets): ${data.skipped.join(', ')}.`;
        }
        result.textContent = msg;
        // Refresh table and exit bulk mode after short delay
        setTimeout(() => {
          closeBulkModal();
          exitBulkMode();
          fetchAssets();
        }, 1800);
      }
    })
    .catch(() => {
      const result = document.getElementById('bulk-result');
      result.classList.remove('hidden');
      result.classList.add('text-red-600');
      result.textContent = 'A network error occurred. Please try again.';
    })
    .finally(() => {
      btn.disabled = false;
      btn.textContent = 'Apply Changes';
    });
}

// Re-apply bulk column visibility after AJAX table refresh
const _origFetchAssets = fetchAssets;
fetchAssets = function() {
  _origFetchAssets();
  // After fetch completes, re-apply bulk mode visibility
  // The fetch is async so we hook into the promise chain via a MutationObserver
};

// MutationObserver to re-apply bulk columns after AJAX refresh
document.addEventListener('DOMContentLoaded', () => {
  const wrap = document.getElementById('asset-table-wrap');
  if (!wrap) return;
  new MutationObserver(() => {
    if (_bulkMode) {
      document.querySelectorAll('.bulk-col').forEach(el => el.classList.remove('hidden'));
      document.querySelectorAll('.bulk-chk').forEach(c => c.addEventListener('change', updateBulkCount));
      updateBulkCount();
    }
  }).observe(wrap, { childList: true, subtree: false });
});

// ── Import CSV Modal ──────────────────────────────────────────
function openImport() {
  document.getElementById('import-modal').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeImport() {
  document.getElementById('import-modal').classList.add('hidden');
  document.body.style.overflow = '';
  resetDropzone();
}

function resetDropzone() {
  document.getElementById('import-file').value = '';
  document.getElementById('dropzone-idle').classList.remove('hidden');
  document.getElementById('dropzone-selected').classList.add('hidden');
  document.getElementById('import-submit').disabled = true;
  document.getElementById('import-error').classList.add('hidden');
  document.getElementById('import-result').classList.add('hidden');
  document.getElementById('import-result').innerHTML = '';
}

function submitImport() {
  const input = document.getElementById('import-file');
  const btn   = document.getElementById('import-submit');
  const res   = document.getElementById('import-result');
  if (!input.files[0]) return;

  btn.disabled    = true;
  btn.textContent = 'Importing…';
  res.classList.add('hidden');

  const fd = new FormData();
  fd.append('csrf_token', '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>');
  fd.append('csv_file',   input.files[0]);

  fetch('import_csv.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      btn.disabled    = false;
      btn.innerHTML   = 'Upload &amp; Import';

      if (!data.success) {
        res.className = 'mt-4 p-3 rounded-lg border text-sm bg-red-50 border-red-200 text-red-700';
        res.textContent = data.message;
        res.classList.remove('hidden');
        return;
      }

      let html = '';
      if (data.imported > 0) {
        html += `<p class="font-semibold text-green-700">✓ ${data.imported} asset${data.imported !== 1 ? 's' : ''} imported successfully.</p>`;
      }
      if (data.skipped.length > 0) {
        html += `<p class="font-semibold text-amber-700 mt-1">⚠ ${data.skipped.length} row${data.skipped.length !== 1 ? 's' : ''} skipped:</p>`;
        html += '<ul class="mt-1 space-y-1">';
        data.skipped.forEach(s => {
          html += `<li class="text-xs text-gray-600"><strong>${s.asset_tag}</strong> (row ${s.row}): ${s.reasons.join('; ')}</li>`;
        });
        html += '</ul>';
      }

      const hasBoth = data.imported > 0 && data.skipped.length > 0;
      const allFail = data.imported === 0 && data.skipped.length > 0;
      res.className = `mt-4 p-3 rounded-lg border text-sm ${allFail ? 'bg-red-50 border-red-200' : hasBoth ? 'bg-amber-50 border-amber-200' : 'bg-green-50 border-green-200'}`;
      res.innerHTML = html;
      res.classList.remove('hidden');

      if (data.imported > 0) {
        resetDropzone();
        fetchAssets(); // refresh the table
      }
    })
    .catch(() => {
      btn.disabled  = false;
      btn.innerHTML = 'Upload &amp; Import';
      res.className = 'mt-4 p-3 rounded-lg border text-sm bg-red-50 border-red-200 text-red-700';
      res.textContent = 'Network error. Please try again.';
      res.classList.remove('hidden');
    });
}

function handleFileSelect(file) {
  if (!file) return;
  if (!file.name.toLowerCase().endsWith('.csv')) {
    document.getElementById('import-error').classList.remove('hidden');
    document.getElementById('import-error').textContent = 'Only CSV files are accepted.';
    return;
  }
  document.getElementById('import-error').classList.add('hidden');
  document.getElementById('dropzone-idle').classList.add('hidden');
  document.getElementById('dropzone-selected').classList.remove('hidden');
  document.getElementById('selected-filename').textContent = file.name;
  document.getElementById('selected-filesize').textContent = (file.size / 1024).toFixed(1) + ' KB';
  document.getElementById('import-submit').disabled = false;
}

document.addEventListener('DOMContentLoaded', () => {
  const zone  = document.getElementById('dropzone');
  const input = document.getElementById('import-file');

  zone.addEventListener('dragover', e => {
    e.preventDefault();
    zone.classList.add('border-olfu-green', 'bg-green-50');
  });
  zone.addEventListener('dragleave', () => {
    zone.classList.remove('border-olfu-green', 'bg-green-50');
  });
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('border-olfu-green', 'bg-green-50');
    const file = e.dataTransfer.files[0];
    if (file) { input.files = e.dataTransfer.files; handleFileSelect(file); }
  });
  zone.addEventListener('click', () => input.click());
  input.addEventListener('change', () => handleFileSelect(input.files[0]));

  // Close on backdrop click
  document.getElementById('import-modal').addEventListener('click', function(e) {
    if (e.target === this) closeImport();
  });

  // Close on Escape
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeImport();
  });
});
</script>

<!-- ── Bulk Update: Floating Action Bar ─────────────────────── -->
<div id="bulk-bar"
     class="hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t-2 border-olfu-green shadow-2xl px-6 py-3 flex items-center justify-between gap-4">
  <div class="flex items-center gap-4">
    <span class="text-sm font-bold text-gray-900">
      <span id="bulk-count">0</span> asset(s) selected
    </span>
    <button onclick="selectAllAssets(true)"
            class="text-xs text-olfu-green hover:underline font-medium">Select all on page</button>
    <button onclick="selectAllAssets(false)"
            class="text-xs text-gray-400 hover:underline">Clear selection</button>
  </div>
  <div class="flex items-center gap-2">
    <button onclick="exitBulkMode()"
            class="text-sm font-medium text-gray-500 hover:text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors">
      Cancel
    </button>
    <button id="bulk-apply-btn" onclick="openBulkModal()" disabled
            class="inline-flex items-center gap-2 bg-olfu-green text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed hover:bg-olfu-green-md">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
      </svg>
      Apply Changes
    </button>
  </div>
</div>

<!-- ── Bulk Update Modal ──────────────────────────────────────── -->
<div id="bulk-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.45)">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md" onclick="event.stopPropagation()">

    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
      <div>
        <h3 class="text-base font-bold text-gray-900">Bulk Update Assets</h3>
        <p class="text-xs text-gray-400 mt-0.5">
          Updating <strong id="bulk-modal-count">0</strong> selected asset(s).
          Choose a field and the new value.
        </p>
      </div>
      <button onclick="closeBulkModal()"
              class="text-gray-400 hover:text-gray-700 transition-colors p-1 rounded-lg hover:bg-gray-100">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-100 px-6">
      <?php
      $bulk_tabs = [
        'status'      => 'Status',
        'location_id' => 'Location',
        'owner_id'    => 'Owner',
        'department_id' => 'Cost Center',
      ];
      foreach ($bulk_tabs as $key => $label): ?>
      <button data-tab="<?= $key ?>"
              onclick="switchBulkTab('<?= $key ?>')"
              class="bulk-tab-btn text-xs font-semibold py-3 px-3 border-b-2 border-transparent text-gray-500 hover:text-gray-800 transition-colors whitespace-nowrap">
        <?= $label ?>
      </button>
      <?php endforeach; ?>
    </div>

    <!-- Tab panes -->
    <div class="px-6 py-5" style="height:195px;overflow:hidden">

      <!-- Status -->
      <div class="bulk-tab-pane" data-pane="status">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">New Status</label>
        <select id="bulk-status" class="fsel w-full">
          <option value="active">Active</option>
          <option value="spare">Spare</option>
          <option value="retired">Retired</option>
        </select>
        <p class="text-xs text-gray-400 mt-2">
          Assets with open tickets cannot be set to Retired — they will be skipped and reported.
        </p>
      </div>

      <!-- Location (cascading: building → floor → room) -->
      <div class="bulk-tab-pane hidden" data-pane="location_id">
        <div class="flex flex-col gap-2">
          <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider w-16 shrink-0">Building</label>
            <select id="bulk-building" class="fsel flex-1" onchange="bulkPopulateFloors()">
              <option value="">— Select building —</option>
              <?php
              $bulk_buildings = array_unique(array_column($all_locations, 'building'));
              sort($bulk_buildings);
              foreach ($bulk_buildings as $b): ?>
                <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider w-16 shrink-0">Floor</label>
            <select id="bulk-floor" class="fsel flex-1" onchange="bulkPopulateRooms()" disabled>
              <option value="">— Select floor —</option>
            </select>
          </div>
          <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider w-16 shrink-0">Room</label>
            <select id="bulk-location" class="fsel flex-1" disabled>
              <option value="">— Select room —</option>
            </select>
          </div>
        </div>
        <p class="text-xs text-gray-400 mt-1">Leave all unselected to clear the location on selected assets.</p>
      </div>

      <!-- Owner -->
      <div class="bulk-tab-pane hidden" data-pane="owner_id">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">New Owner</label>
        <select id="bulk-owner" class="fsel w-full">
          <option value="">— Clear owner —</option>
          <?php foreach ($owners as $o): ?>
            <option value="<?= $o['user_id'] ?>"><?= htmlspecialchars($o['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Cost Center -->
      <div class="bulk-tab-pane hidden" data-pane="department_id">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">New Cost Center</label>
        <select id="bulk-department" class="fsel w-full">
          <option value="">— Clear cost center —</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?= $dept['department_id'] ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-gray-400 mt-2">Which department is financially responsible for the selected assets.</p>
      </div>

    </div>

    <!-- Result message -->
    <p id="bulk-result" class="hidden px-6 pb-2 text-sm font-medium"></p>

    <!-- Footer -->
    <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-100">
      <button onclick="closeBulkModal()"
              class="text-sm font-medium text-gray-500 hover:text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">
        Cancel
      </button>
      <button id="bulk-submit-btn" onclick="submitBulkUpdate()"
              class="bg-olfu-green text-white text-sm font-semibold px-5 py-2 rounded-lg hover:bg-olfu-green-md transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
        Apply Changes
      </button>
    </div>

  </div>
</div>

<!-- ── Import CSV Modal ──────────────────────────────────────── -->
<div id="import-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.45)">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg" onclick="event.stopPropagation()">

    <!-- Modal header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
      <div>
        <h3 class="text-base font-bold text-gray-900">Import Assets from CSV</h3>
        <p class="text-xs text-gray-400 mt-0.5">Bulk-add assets by uploading a formatted CSV file.</p>
      </div>
      <button onclick="closeImport()"
              class="text-gray-400 hover:text-gray-700 transition-colors p-1 rounded-lg hover:bg-gray-100">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Modal body -->
    <div class="px-6 py-5">

      <!-- Drop zone -->
      <div id="dropzone"
           class="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center cursor-pointer transition-colors duration-150 hover:border-olfu-green hover:bg-green-50 select-none">

        <!-- Idle state -->
        <div id="dropzone-idle">
          <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
          </svg>
          <p class="text-sm font-semibold text-gray-700">Drag &amp; drop your CSV file here</p>
          <p class="text-xs text-gray-400 mt-1">or <span class="text-olfu-green underline">click to browse</span></p>
          <p class="text-xs text-gray-300 mt-3">Only <strong>.csv</strong> files · Max 5 MB</p>
        </div>

        <!-- File selected state -->
        <div id="dropzone-selected" class="hidden">
          <svg class="w-10 h-10 mx-auto mb-3 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="text-sm font-semibold text-gray-800 truncate" id="selected-filename"></p>
          <p class="text-xs text-gray-400 mt-1" id="selected-filesize"></p>
          <button type="button" onclick="event.stopPropagation(); resetDropzone()"
                  class="mt-3 text-xs text-red-500 hover:underline">Remove file</button>
        </div>
      </div>

      <!-- Hidden file input -->
      <input type="file" id="import-file" accept=".csv" class="hidden">

      <!-- Error message -->
      <p id="import-error" class="hidden text-xs text-red-600 mt-2"></p>

      <!-- Template download hint -->
      <p class="text-xs text-gray-400 mt-3">
        Need a template?
        <a href="<?= BASE_URL ?>public/assets/sample_import.csv" download class="text-olfu-green hover:underline">Download sample CSV</a>
        to see the required column format.
      </p>

      <!-- Import result -->
      <div id="import-result" class="hidden mt-4 p-3 rounded-lg border text-sm"></div>
    </div>

    <!-- Modal footer -->
    <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-100">
      <button type="button" onclick="closeImport()"
              class="text-sm font-medium text-gray-500 hover:text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">
        Cancel
      </button>
      <button type="button" id="import-submit" disabled
              onclick="submitImport()"
              class="inline-flex items-center gap-2 bg-olfu-green text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors disabled:opacity-40 disabled:cursor-not-allowed hover:bg-olfu-green-md">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
        </svg>
        Upload &amp; Import
      </button>
    </div>

  </div>
</div>

<!-- Manage Locations Modal -->
<div id="locations-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.45)" onclick="if(event.target===this)closeLocationsModal()">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col" onclick="event.stopPropagation()">

    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
      <div>
        <h3 class="text-base font-bold text-gray-900">Manage Locations</h3>
        <p class="text-xs text-gray-400 mt-0.5">Add, edit, or remove rooms. Locations with assets cannot be deleted.</p>
      </div>
      <button type="button" onclick="closeLocationsModal()" class="text-gray-400 hover:text-gray-700 transition-colors">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Add form -->
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
      <div class="grid grid-cols-12 gap-2 items-end">
        <div class="col-span-3">
          <label class="block text-xs font-semibold text-gray-600 mb-1">Building</label>
          <input type="text" id="loc-add-building" class="fin w-full text-sm" placeholder="e.g. Main Building" maxlength="100">
        </div>
        <div class="col-span-3">
          <label class="block text-xs font-semibold text-gray-600 mb-1">Floor</label>
          <input type="text" id="loc-add-floor" class="fin w-full text-sm" placeholder="e.g. 2" maxlength="50">
        </div>
        <div class="col-span-4">
          <label class="block text-xs font-semibold text-gray-600 mb-1">Room</label>
          <input type="text" id="loc-add-room" class="fin w-full text-sm" placeholder="e.g. Lab 201" maxlength="100">
        </div>
        <div class="col-span-2">
          <button type="button" onclick="addLocation()"
            class="w-full inline-flex items-center justify-center gap-1.5 bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-3 py-2 rounded-lg transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            Add
          </button>
        </div>
      </div>
      <div id="loc-msg" class="hidden mt-3 p-2 rounded-lg text-xs font-medium"></div>
    </div>

    <!-- Locations table -->
    <div class="flex-1 overflow-auto px-6 py-4">
      <table class="w-full text-sm">
        <thead class="text-xs uppercase tracking-wider text-gray-500 border-b border-gray-100">
          <tr>
            <th class="text-left py-2 font-bold">Building</th>
            <th class="text-left py-2 font-bold">Floor</th>
            <th class="text-left py-2 font-bold">Room</th>
            <th class="text-center py-2 font-bold">Assets</th>
            <th class="text-right py-2 font-bold">Actions</th>
          </tr>
        </thead>
        <tbody id="loc-tbody">
          <tr><td colspan="5" class="py-6 text-center text-gray-400 italic">Loading…</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-100">
      <button type="button" onclick="closeLocationsModal()"
              class="text-sm font-medium text-gray-500 hover:text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">
        Close
      </button>
    </div>
  </div>
</div>

<script>
const _locCsrf = <?= json_encode($_SESSION['csrf_token'] ??= bin2hex(random_bytes(16))) ?>;

function openLocationsModal() {
  document.getElementById('locations-modal').classList.remove('hidden');
  loadLocations();
}
function closeLocationsModal() {
  document.getElementById('locations-modal').classList.add('hidden');
  hideLocMsg();
}

function showLocMsg(text, isError) {
  const el = document.getElementById('loc-msg');
  el.textContent = text;
  el.className = 'mt-3 p-2 rounded-lg text-xs font-medium ' +
    (isError ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200');
}
function hideLocMsg() {
  document.getElementById('loc-msg').className = 'hidden mt-3 p-2 rounded-lg text-xs font-medium';
}

function loadLocations() {
  fetch('locations_list.php')
    .then(r => r.json())
    .then(rows => renderLocations(rows))
    .catch(() => {
      document.getElementById('loc-tbody').innerHTML =
        '<tr><td colspan="5" class="py-6 text-center text-red-500">Failed to load locations.</td></tr>';
    });
}

function renderLocations(rows) {
  const tb = document.getElementById('loc-tbody');
  if (!rows.length) {
    tb.innerHTML = '<tr><td colspan="5" class="py-6 text-center text-gray-400 italic">No locations yet. Add one above.</td></tr>';
    return;
  }
  tb.innerHTML = rows.map(r => {
    const id = r.location_id;
    const cnt = parseInt(r.asset_count, 10) || 0;
    const canDelete = cnt === 0;
    const delTitle = canDelete ? 'Delete location' : `Cannot delete — ${cnt} asset${cnt === 1 ? '' : 's'} assigned`;
    return `
      <tr class="border-b border-gray-50 hover:bg-gray-50/60" data-id="${id}">
        <td class="py-2 pr-2"><span class="js-cell" data-field="building">${escapeHtml(r.building)}</span></td>
        <td class="py-2 pr-2"><span class="js-cell" data-field="floor">${escapeHtml(r.floor)}</span></td>
        <td class="py-2 pr-2"><span class="js-cell" data-field="room">${escapeHtml(r.room)}</span></td>
        <td class="py-2 text-center">
          <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold ${cnt > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}">${cnt}</span>
        </td>
        <td class="py-2 text-right whitespace-nowrap">
          <button type="button" onclick="editLocation(${id})"
                  class="text-xs font-semibold text-amber-700 hover:bg-amber-50 px-2 py-1 rounded">Edit</button>
          <button type="button" onclick="deleteLocation(${id})" ${canDelete ? '' : 'disabled'}
                  title="${escapeHtml(delTitle)}"
                  class="text-xs font-semibold text-red-600 hover:bg-red-50 px-2 py-1 rounded disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-transparent">Delete</button>
        </td>
      </tr>`;
  }).join('');
}

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function addLocation() {
  hideLocMsg();
  const fd = new FormData();
  fd.append('csrf_token', _locCsrf);
  fd.append('building', document.getElementById('loc-add-building').value.trim());
  fd.append('floor',    document.getElementById('loc-add-floor').value.trim());
  fd.append('room',     document.getElementById('loc-add-room').value.trim());

  fetch('locations_save.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showLocMsg(res.message, false);
        ['loc-add-building', 'loc-add-floor', 'loc-add-room'].forEach(id => document.getElementById(id).value = '');
        loadLocations();
      } else {
        showLocMsg(res.message || 'Failed to add location.', true);
      }
    })
    .catch(() => showLocMsg('Network error. Please try again.', true));
}

function editLocation(id) {
  const row = document.querySelector(`#loc-tbody tr[data-id="${id}"]`);
  if (!row) return;
  const cells = row.querySelectorAll('.js-cell');
  cells.forEach(c => {
    const field = c.dataset.field;
    const val = c.textContent;
    c.outerHTML = `<input type="text" class="fin w-full text-sm js-edit" data-field="${field}" value="${escapeHtml(val)}" maxlength="${field === 'floor' ? 50 : 100}">`;
  });
  const actionsCell = row.querySelector('td:last-child');
  actionsCell.innerHTML = `
    <button type="button" onclick="saveEdit(${id})"
            class="text-xs font-semibold text-olfu-green hover:bg-green-50 px-2 py-1 rounded">Save</button>
    <button type="button" onclick="loadLocations()"
            class="text-xs font-semibold text-gray-500 hover:bg-gray-50 px-2 py-1 rounded">Cancel</button>`;
}

function saveEdit(id) {
  hideLocMsg();
  const row = document.querySelector(`#loc-tbody tr[data-id="${id}"]`);
  if (!row) return;
  const fd = new FormData();
  fd.append('csrf_token', _locCsrf);
  fd.append('location_id', id);
  row.querySelectorAll('.js-edit').forEach(inp => fd.append(inp.dataset.field, inp.value.trim()));

  fetch('locations_save.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showLocMsg(res.message, false);
        loadLocations();
      } else {
        showLocMsg(res.message || 'Failed to update location.', true);
      }
    })
    .catch(() => showLocMsg('Network error. Please try again.', true));
}

function deleteLocation(id) {
  if (!confirm('Delete this location? This cannot be undone.')) return;
  hideLocMsg();
  const fd = new FormData();
  fd.append('csrf_token', _locCsrf);
  fd.append('location_id', id);

  fetch('locations_delete.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showLocMsg(res.message, false);
        loadLocations();
      } else {
        showLocMsg(res.message || 'Failed to delete location.', true);
      }
    })
    .catch(() => showLocMsg('Network error. Please try again.', true));
}
</script>

<!-- Manage Categories Modal -->
<div id="categories-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.45)" onclick="if(event.target===this)closeCategoriesModal()">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col" onclick="event.stopPropagation()">

    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
      <div>
        <h3 class="text-base font-bold text-gray-900">Manage Categories</h3>
        <p class="text-xs text-gray-400 mt-0.5">Add, edit, or remove asset categories. Categories with assets cannot be deleted.</p>
      </div>
      <button type="button" onclick="closeCategoriesModal()" class="text-gray-400 hover:text-gray-700 transition-colors">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Add form -->
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
      <div class="grid grid-cols-12 gap-2 items-end">
        <div class="col-span-4">
          <label class="block text-xs font-semibold text-gray-600 mb-1">Category Name</label>
          <input type="text" id="cat-add-name" class="fin w-full text-sm" placeholder="e.g. Projector" maxlength="100">
        </div>
        <div class="col-span-5">
          <label class="block text-xs font-semibold text-gray-600 mb-1">Description (optional)</label>
          <input type="text" id="cat-add-desc" class="fin w-full text-sm" placeholder="Short description" maxlength="255">
        </div>
        <div class="col-span-1 flex items-center justify-center">
          <label class="flex items-center gap-1 text-xs font-semibold text-gray-600 cursor-pointer pt-5" title="Tracks bulb hours on assets in this category">
            <input type="checkbox" id="cat-add-bulb" class="rounded text-olfu-green focus:ring-olfu-green">
            Bulb
          </label>
        </div>
        <div class="col-span-2">
          <button type="button" onclick="addCategory()"
            class="w-full inline-flex items-center justify-center gap-1.5 bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-3 py-2 rounded-lg transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            Add
          </button>
        </div>
      </div>
      <div id="cat-msg" class="hidden mt-3 p-2 rounded-lg text-xs font-medium"></div>
    </div>

    <!-- Categories table -->
    <div class="flex-1 overflow-auto px-6 py-4">
      <table class="w-full text-sm">
        <thead class="text-xs uppercase tracking-wider text-gray-500 border-b border-gray-100">
          <tr>
            <th class="text-left py-2 font-bold">Name</th>
            <th class="text-left py-2 font-bold">Description</th>
            <th class="text-center py-2 font-bold">Bulb Hours</th>
            <th class="text-center py-2 font-bold">Assets</th>
            <th class="text-right py-2 font-bold">Actions</th>
          </tr>
        </thead>
        <tbody id="cat-tbody">
          <tr><td colspan="5" class="py-6 text-center text-gray-400 italic">Loading…</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-100">
      <button type="button" onclick="closeCategoriesModal()"
              class="text-sm font-medium text-gray-500 hover:text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">
        Close
      </button>
    </div>
  </div>
</div>

<script>
function openCategoriesModal() {
  document.getElementById('categories-modal').classList.remove('hidden');
  loadCategories();
}
function closeCategoriesModal() {
  document.getElementById('categories-modal').classList.add('hidden');
  hideCatMsg();
}

function showCatMsg(text, isError) {
  const el = document.getElementById('cat-msg');
  el.textContent = text;
  el.className = 'mt-3 p-2 rounded-lg text-xs font-medium ' +
    (isError ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200');
}
function hideCatMsg() {
  document.getElementById('cat-msg').className = 'hidden mt-3 p-2 rounded-lg text-xs font-medium';
}

function loadCategories() {
  fetch('categories_list.php')
    .then(r => r.json())
    .then(rows => renderCategories(rows))
    .catch(() => {
      document.getElementById('cat-tbody').innerHTML =
        '<tr><td colspan="5" class="py-6 text-center text-red-500">Failed to load categories.</td></tr>';
    });
}

function renderCategories(rows) {
  const tb = document.getElementById('cat-tbody');
  if (!rows.length) {
    tb.innerHTML = '<tr><td colspan="5" class="py-6 text-center text-gray-400 italic">No categories yet. Add one above.</td></tr>';
    return;
  }
  tb.innerHTML = rows.map(r => {
    const id = r.category_id;
    const cnt = parseInt(r.asset_count, 10) || 0;
    const canDelete = cnt === 0;
    const delTitle = canDelete ? 'Delete category' : `Cannot delete — ${cnt} asset${cnt === 1 ? '' : 's'} assigned`;
    const bulb = parseInt(r.has_bulb_hours, 10) ? '✓' : '—';
    return `
      <tr class="border-b border-gray-50 hover:bg-gray-50/60" data-id="${id}">
        <td class="py-2 pr-2"><span class="js-cell" data-field="category_name">${escapeHtml(r.category_name)}</span></td>
        <td class="py-2 pr-2 text-gray-500"><span class="js-cell" data-field="description">${escapeHtml(r.description ?? '')}</span></td>
        <td class="py-2 text-center">
          <span class="js-cell font-semibold ${parseInt(r.has_bulb_hours, 10) ? 'text-olfu-green' : 'text-gray-300'}" data-field="has_bulb_hours" data-raw="${parseInt(r.has_bulb_hours, 10)}">${bulb}</span>
        </td>
        <td class="py-2 text-center">
          <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold ${cnt > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}">${cnt}</span>
        </td>
        <td class="py-2 text-right whitespace-nowrap">
          <button type="button" onclick="editCategory(${id})"
                  class="text-xs font-semibold text-amber-700 hover:bg-amber-50 px-2 py-1 rounded">Edit</button>
          <button type="button" onclick="deleteCategory(${id})" ${canDelete ? '' : 'disabled'}
                  title="${escapeHtml(delTitle)}"
                  class="text-xs font-semibold text-red-600 hover:bg-red-50 px-2 py-1 rounded disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-transparent">Delete</button>
        </td>
      </tr>`;
  }).join('');
}

function addCategory() {
  hideCatMsg();
  const fd = new FormData();
  fd.append('csrf_token', _locCsrf);
  fd.append('category_name', document.getElementById('cat-add-name').value.trim());
  fd.append('description',   document.getElementById('cat-add-desc').value.trim());
  if (document.getElementById('cat-add-bulb').checked) fd.append('has_bulb_hours', '1');

  fetch('categories_save.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showCatMsg(res.message, false);
        document.getElementById('cat-add-name').value = '';
        document.getElementById('cat-add-desc').value = '';
        document.getElementById('cat-add-bulb').checked = false;
        loadCategories();
      } else {
        showCatMsg(res.message || 'Failed to add category.', true);
      }
    })
    .catch(() => showCatMsg('Network error. Please try again.', true));
}

function editCategory(id) {
  const row = document.querySelector(`#cat-tbody tr[data-id="${id}"]`);
  if (!row) return;
  row.querySelectorAll('.js-cell').forEach(c => {
    const field = c.dataset.field;
    if (field === 'has_bulb_hours') {
      const checked = c.dataset.raw === '1' ? 'checked' : '';
      c.outerHTML = `<input type="checkbox" class="js-edit rounded text-olfu-green focus:ring-olfu-green" data-field="has_bulb_hours" ${checked}>`;
    } else {
      const val = c.textContent;
      const max = field === 'description' ? 255 : 100;
      c.outerHTML = `<input type="text" class="fin w-full text-sm js-edit" data-field="${field}" value="${escapeHtml(val)}" maxlength="${max}">`;
    }
  });
  const actionsCell = row.querySelector('td:last-child');
  actionsCell.innerHTML = `
    <button type="button" onclick="saveCatEdit(${id})"
            class="text-xs font-semibold text-olfu-green hover:bg-green-50 px-2 py-1 rounded">Save</button>
    <button type="button" onclick="loadCategories()"
            class="text-xs font-semibold text-gray-500 hover:bg-gray-50 px-2 py-1 rounded">Cancel</button>`;
}

function saveCatEdit(id) {
  hideCatMsg();
  const row = document.querySelector(`#cat-tbody tr[data-id="${id}"]`);
  if (!row) return;
  const fd = new FormData();
  fd.append('csrf_token', _locCsrf);
  fd.append('category_id', id);
  row.querySelectorAll('.js-edit').forEach(inp => {
    if (inp.type === 'checkbox') {
      if (inp.checked) fd.append(inp.dataset.field, '1');
    } else {
      fd.append(inp.dataset.field, inp.value.trim());
    }
  });

  fetch('categories_save.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showCatMsg(res.message, false);
        loadCategories();
      } else {
        showCatMsg(res.message || 'Failed to update category.', true);
      }
    })
    .catch(() => showCatMsg('Network error. Please try again.', true));
}

function deleteCategory(id) {
  if (!confirm('Delete this category? This cannot be undone.')) return;
  hideCatMsg();
  const fd = new FormData();
  fd.append('csrf_token', _locCsrf);
  fd.append('category_id', id);

  fetch('categories_delete.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showCatMsg(res.message, false);
        loadCategories();
      } else {
        showCatMsg(res.message || 'Failed to delete category.', true);
      }
    })
    .catch(() => showCatMsg('Network error. Please try again.', true));
}
</script>
