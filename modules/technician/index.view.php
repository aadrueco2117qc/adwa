<?php require __DIR__ . '/_styles.php'; ?>

<?php if ($out_stock_count > 0): ?>
<!-- ── Out of stock banner ──────────────────────────────────────── -->
<div style="display:flex;align-items:center;gap:10px;padding:12px 18px;margin-bottom:12px;background:#fef2f2;border-left:4px solid #ef4444;border-radius:8px;">
  <svg style="width:16px;height:16px;color:#ef4444;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span style="font-size:13px;color:#b91c1c;">
    <strong><?= $out_stock_count ?> part<?= $out_stock_count !== 1 ? 's' : '' ?></strong> <?= $out_stock_count !== 1 ? 'are' : 'is' ?> completely out of stock.
    <?php if ($low_stock_count > $out_stock_count): ?>
      <?= $low_stock_count - $out_stock_count ?> more <?= ($low_stock_count - $out_stock_count) !== 1 ? 'are' : 'is' ?> running low.
    <?php endif; ?>
    <span style="font-weight:400;"> — Open a job below, then go to the <strong>Parts</strong> tab to see stock levels before using a part.</span>
  </span>
</div>
<?php elseif ($low_stock_count > 0): ?>
<!-- ── Low stock banner ─────────────────────────────────────────── -->
<div style="display:flex;align-items:center;gap:10px;padding:12px 18px;margin-bottom:12px;background:#fffbeb;border-left:4px solid #f59e0b;border-radius:8px;">
  <svg style="width:16px;height:16px;color:#d97706;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span style="font-size:13px;color:#92400e;">
    <strong><?= $low_stock_count ?> part<?= $low_stock_count !== 1 ? 's' : '' ?></strong> <?= $low_stock_count !== 1 ? 'are' : 'is' ?> running low on stock.
    <span style="font-weight:400;"> — Open a job below, then go to the <strong>Parts</strong> tab to see stock levels before using a part.</span>
  </span>
</div>
<?php endif; ?>

<!-- ── Page header ─────────────────────────────────────────────── -->
<div class="page-header-card">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
      <span style="color:var(--olfu-green);">
        <svg class="w-5 h-5 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l5.653-4.655"/>
        </svg>
      </span>
      My Jobs
    </h2>
    <p class="text-sm mt-0.5" style="color:var(--tech-gray-400);">View and manage your assigned work orders, even when offline.</p>
  </div>
</div>

<!-- ── Status chips ────────────────────────────────────────────── -->
<?php
  $count_all       = count($all_work_orders ?? []);
  $count_new       = count(array_filter($all_work_orders ?? [], fn($w) => ($w['status'] ?? '') === 'new'));
  $count_assigned  = count(array_filter($all_work_orders ?? [], fn($w) => ($w['status'] ?? '') === 'assigned'));
  $count_scheduled = count(array_filter($all_work_orders ?? [], fn($w) => ($w['status'] ?? '') === 'scheduled'));
  $count_progress  = count(array_filter($all_work_orders ?? [], fn($w) => ($w['status'] ?? '') === 'in_progress'));
  $count_hold      = count(array_filter($all_work_orders ?? [], fn($w) => ($w['status'] ?? '') === 'on_hold'));
  $count_resolved  = count(array_filter($all_work_orders ?? [], fn($w) => ($w['status'] ?? '') === 'resolved'));
  $count_closed    = count(array_filter($all_work_orders ?? [], fn($w) => ($w['status'] ?? '') === 'closed'));
  $count_overdue = 0;
  if (!empty($all_work_orders)) {
    foreach ($all_work_orders as $w) {
      if (($w['scheduled_end'] ?? null) &&
          strtotime($w['scheduled_end']) < time() &&
          !in_array($w['status'] ?? '', ['resolved', 'closed'])) {
        $count_overdue++;
      }
    }
  }
?>
<div class="flex flex-wrap gap-2 mb-4" id="status-chips">
  <?php
  $chip_defs = [
    ''            => ['All',         $count_all],
    'new'         => ['New',         $count_new],
    'assigned'    => ['Assigned',    $count_assigned],
    'scheduled'   => ['Scheduled',  $count_scheduled],
    'in_progress' => ['In Progress', $count_progress],
    'on_hold'     => ['On Hold',     $count_hold],
    'resolved'    => ['Resolved',    $count_resolved],
    'closed'      => ['Closed',      $count_closed],
    'overdue'     => ['Overdue',     $count_overdue],
  ];
  foreach ($chip_defs as $val => $info):
    [$label, $count] = $info;
    $is_on = ($val === '');
  ?>
  <button type="button"
    id="chip-<?= $val === '' ? 'all' : $val ?>"
    onclick="setChip('<?= $val ?>')"
    class="chip <?= $is_on ? 'chip-on' : '' ?>">
    <?= $label ?> <span style="opacity:.65;font-weight:400;">(<?= $count ?>)</span>
  </button>
  <?php endforeach; ?>
</div>

<!-- ── Work Type Filter ────────────────────────────────────────── -->
<div class="filter-panel mb-4">
  <div class="filter-panel__label">
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
      <path d="M4 6h16M4 12h10M4 18h7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
    </svg>
    Filter by Work Type
  </div>
  <div class="tech-filter-group">
    <div class="tech-filter-icon">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
        <path d="M4 6h16M4 12h10M4 18h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </div>
    <select id="work-type-filter" class="tech-filter-select" onchange="filterByWorkType()">
      <option value="">All Types</option>
      <option value="diagnosis">Diagnosis</option>
      <option value="repair">Repair</option>
      <option value="maintenance">Maintenance</option>
      <option value="follow_up">Follow-up</option>
    </select>
  </div>
  <button type="button" id="my-jobs-toggle" class="my-jobs-toggle" onclick="toggleMyJobs()">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
    </svg>
    Working On
  </button>
  <!-- Sort by date -->
  <button type="button" id="sort-date-btn" class="my-jobs-toggle" onclick="toggleSortDate()" title="Sort jobs by date">
    <svg id="sort-date-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="transition:transform .25s;">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25-.75L17.25 9m0 0L21 12.75M17.25 9v12"/>
    </svg>
    <span id="sort-date-label">Newest</span>
  </button>
  <button type="button" id="clear-cache-btn" class="my-jobs-toggle" onclick="clearTechCache()" title="Clears locally stored images and draft data to free up space">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
    </svg>
    Clear Cache
  </button>
</div>

<!-- ── Jobs grid ──────────────────────────────────────────────── -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="jobsGrid">

  <?php if (empty($all_work_orders)): ?>
    <div class="col-span-full bg-white rounded-xl border border-gray-100 shadow-sm py-16 text-center">
      <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4"
           style="background:var(--olfu-green-100);">
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
             style="color:var(--olfu-green);">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
        </svg>
      </div>
      <h3 class="text-sm font-semibold mb-1" style="color:var(--tech-gray-700);">No work orders found</h3>
      <p class="text-xs max-w-xs mx-auto" style="color:var(--tech-gray-400);">Work orders assigned to your queue will appear here. They can be unclaimed (role-based) or already assigned to you.</p>
    </div>
  <?php endif; ?>

  <?php foreach (($all_work_orders ?? []) as $wo):
    $status = (string)($wo['status'] ?? '');
    $is_overdue = false;
    if (($wo['scheduled_end'] ?? null) &&
        strtotime($wo['scheduled_end']) < time() &&
        !in_array($status, ['resolved', 'closed'])) {
      $is_overdue = true;
    }
    $badge_cls = match($status) {
      'new'         => 'badge-new',
      'assigned'    => 'badge-assigned',
      'scheduled'   => 'badge-scheduled',
      'in_progress' => 'badge-in_progress',
      'on_hold'     => 'badge-on_hold',
      'resolved'    => 'badge-resolved',
      'closed'      => 'badge-closed',
      default       => 'badge-new',
    };
    $badge_label = match($status) {
      'new'         => 'New',
      'assigned'    => 'Assigned',
      'scheduled'   => 'Scheduled',
      'in_progress' => 'In Progress',
      'on_hold'     => 'On Hold',
      'resolved'    => 'Resolved',
      'closed'      => 'Closed',
      default       => ucfirst(str_replace('_', ' ', $status ?: 'new')),
    };
    $is_unclaimed = empty($wo['assigned_to']);
    $data_status = $is_overdue ? 'overdue' : $status;
    $data_wo_type = (string)($wo['wo_type'] ?? '');

    // Card left border color for quick visual status
    $border_accent = match($status) {
      'in_progress' => '#d97706',
      'on_hold'     => '#ec4899',
      'assigned'    => '#3b82f6',
      'resolved'    => 'var(--olfu-green-600)',
      default       => 'transparent',
    };
  ?>
  <div class="job-card"
       data-status="<?php echo htmlspecialchars($data_status); ?>"
       data-wo-type="<?php echo htmlspecialchars($data_wo_type); ?>"
       data-assigned-to="<?php echo (int)($wo['assigned_to'] ?? 0); ?>"
       data-created-at="<?php
         // Use the most relevant timestamp for sorting:
         // resolved/closed → actual_end, claimed/in-progress → claimed_at, else created_at
         if (in_array($status, ['resolved', 'closed']) && !empty($wo['actual_end'])) {
           echo strtotime($wo['actual_end']);
         } elseif (!empty($wo['claimed_at'])) {
           echo strtotime($wo['claimed_at']);
         } else {
           echo $wo['created_at'] ? strtotime($wo['created_at']) : 0;
         }
       ?>">

    <div class="job-card-header">
      <div class="flex items-center justify-between mb-2">
        <span class="job-card-tag"><?php echo htmlspecialchars($wo['wo_number']); ?></span>
        <span class="wo-badge <?php echo $badge_cls; ?>">
          <span class="bdot"></span><?php echo $badge_label; ?>
        </span>
      </div>
      <h3 class="text-sm font-bold leading-snug line-clamp-2 mb-1" style="color:var(--tech-gray-900);">
        <?php
          $wo_type_raw = $wo['wo_type'] ?: 'No type';
          $wo_type_labels = [
            'follow_up'    => 'Follow-up',
            'diagnosis'    => 'Diagnosis',
            'repair'       => 'Repair',
            'maintenance'  => 'Maintenance',
            'installation' => 'Installation',
          ];
          echo htmlspecialchars($wo_type_labels[$wo_type_raw] ?? ucfirst(str_replace('_', ' ', $wo_type_raw)));
        ?>
      </h3>
    </div>

    <div class="job-card-body py-3 flex-1">
      <p class="text-xs line-clamp-2 mb-3" style="color:var(--tech-gray-500);">
        <?php echo htmlspecialchars($wo['notes'] ?: 'No description provided.'); ?>
      </p>

      <div class="space-y-1.5">
        <?php if (!empty($wo['assigned_to'])): ?>
          <?php if (!empty($wo['assigned_to_name'])): ?>
            <div class="flex items-center gap-1.5 text-xs" style="color:var(--tech-gray-500);">
              <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:var(--tech-gray-400);">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              <span><?php echo htmlspecialchars($wo['assigned_to_name']); ?></span>
            </div>
          <?php else: ?>
            <div class="flex items-center gap-1.5 text-xs" style="color:#f59e0b;">
              <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
              <span>Invalid User (ID: <?php echo (int)$wo['assigned_to']; ?>)</span>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($is_overdue): ?>
          <div class="flex items-center gap-1.5">
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:99px;background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;">
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Overdue
            </span>
          </div>
        <?php elseif ($is_unclaimed): ?>
          <div class="mt-1">
            <span class="claim-badge">
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5"/>
              </svg>
              Unclaimed in queue
            </span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="job-card-footer pt-3 mt-auto" style="border-top:1px solid var(--tech-gray-100);">
      <?php if ($is_unclaimed): ?>
        <button type="button"
                onclick="claimJob(<?php echo (int)$wo['wo_id']; ?>, this)"
                class="btn-primary w-full">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5"/>
          </svg>
          Claim Job
        </button>
      <?php else: ?>
        <a href="view.php?id=<?php echo (int)$wo['wo_id']; ?>"
           class="<?php echo in_array($status, ['resolved', 'closed']) ? 'btn-secondary' : 'btn-primary'; ?> w-full"
           style="text-decoration:none;">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
          </svg>
          <?php
            if (in_array($status, ['resolved', 'closed'])) echo 'View Job';
            elseif ($status === 'assigned') echo 'Start Work';
            else echo 'Open Job';
          ?>
        </a>
      <?php endif; ?>
    </div>

  </div>
  <?php endforeach; ?>


</div>

<script>
const CURRENT_USER_ID = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
let myJobsOnly = false;
let sortDateOrder = 'newest'; // 'newest' | 'oldest'

function setChip(status) {
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('chip-on'));
  const id = status === '' ? 'chip-all' : 'chip-' + status;
  const el = document.getElementById(id);
  if (el) el.classList.add('chip-on');
  applyFilters();
}

function filterByWorkType() { applyFilters(); }

function toggleMyJobs() {
  myJobsOnly = !myJobsOnly;
  const btn = document.getElementById('my-jobs-toggle');
  btn.classList.toggle('my-jobs-toggle--on', myJobsOnly);
  applyFilters();
}

function toggleSortDate() {
  sortDateOrder = sortDateOrder === 'newest' ? 'oldest' : 'newest';
  const btn   = document.getElementById('sort-date-btn');
  const label = document.getElementById('sort-date-label');
  const icon  = document.getElementById('sort-date-icon');
  if (label) label.textContent = sortDateOrder === 'newest' ? 'Newest' : 'Oldest';
  if (icon)  icon.style.transform = sortDateOrder === 'oldest' ? 'scaleY(-1)' : 'scaleY(1)';
  if (btn)   btn.classList.toggle('my-jobs-toggle--on', sortDateOrder === 'oldest');
  applyFilters();
}

function applyFilters() {
  const activeChip = document.querySelector('.chip.chip-on');
  const statusFilter = activeChip ?
    (activeChip.id === 'chip-all' ? '' : activeChip.id.replace('chip-', '')) : '';
  const workTypeFilter = document.getElementById('work-type-filter').value;

  const grid = document.getElementById('jobsGrid');
  const cards = Array.from(grid.querySelectorAll('.job-card'));

  // Determine visibility
  cards.forEach(card => {
    const statusMatch   = statusFilter   === '' || card.dataset.status  === statusFilter;
    const workTypeMatch = workTypeFilter === '' || card.dataset.woType  === workTypeFilter;
    const myJobsMatch   = !myJobsOnly         || parseInt(card.dataset.assignedTo) === CURRENT_USER_ID;
    card.style.display  = (statusMatch && workTypeMatch && myJobsMatch) ? '' : 'none';
  });

  // Sort only the visible cards by the relevant timestamp, then re-append them
  // in sorted order. Hidden cards are left in place (they stay invisible).
  const visible = cards.filter(c => c.style.display !== 'none');
  visible.sort((a, b) => {
    const ta = parseInt(a.dataset.createdAt || '0', 10);
    const tb = parseInt(b.dataset.createdAt || '0', 10);
    return sortDateOrder === 'oldest' ? ta - tb : tb - ta;
  });
  // Use a DocumentFragment so we only touch the DOM once
  const frag = document.createDocumentFragment();
  visible.forEach(card => frag.appendChild(card));
  grid.appendChild(frag);
}

async function clearTechCache() {
  const btn = document.getElementById('clear-cache-btn');
  const origHTML = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Clearing…';

  // Inject spin keyframe once
  if (!document.getElementById('_cacheClearSpinStyle')) {
    const s = document.createElement('style');
    s.id = '_cacheClearSpinStyle';
    s.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
    document.head.appendChild(s);
  }

  let blobsRemoved = 0;
  let draftsRemoved = 0;
  let queueItemsRemoved = 0;

  try {
    // ── Step 1: Find all blobIds still referenced by PENDING queue items ──
    // These must NOT be deleted — they haven't been uploaded yet.
    const LS_QUEUE = 'mrtsp.queue.v1';
    const queue = JSON.parse(localStorage.getItem(LS_QUEUE) || '[]');
    const pendingBlobIds = new Set(
      queue
        .filter(item => item.meta && item.meta.blobId && !item.synced)
        .map(item => item.meta.blobId)
    );

    // ── Step 2: Clear orphaned / already-synced blobs from IndexedDB ──
    if (window.MRTS && window.MRTS.idbStorage) {
      try {
        const idb = await window.MRTS.idbStorage.initDB();
        await new Promise((resolve, reject) => {
          const tx = idb.transaction(['blobs'], 'readwrite');
          const store = tx.objectStore('blobs');
          const req = store.openCursor();
          req.onsuccess = (e) => {
            const cursor = e.target.result;
            if (!cursor) { resolve(); return; }
            const record = cursor.value;
            const blobId = record.id;
            const ageMs  = Date.now() - (record.createdAt || 0);
            const ageDays = ageMs / (1000 * 60 * 60 * 24);

            // Keep if: still pending upload
            if (pendingBlobIds.has(blobId)) {
              cursor.continue();
              return;
            }

            // Delete if: older than 3 days (already synced or orphaned)
            if (ageDays > 3) {
              cursor.delete();
              blobsRemoved++;
            }

            cursor.continue();
          };
          req.onerror = () => resolve(); // non-fatal
        });
      } catch (e) {
        console.warn('[clearCache] IDB cleanup failed:', e);
      }
    }

    // ── Step 3: Remove localStorage draft keys for resolved/closed WOs ──
    // A draft key looks like: mrtsp.draft.{woId}.v1
    // We check the WO status from the page's rendered cards.
    const resolvedWoIds = new Set();
    document.querySelectorAll('#jobsGrid .job-card').forEach(card => {
      const st = card.dataset.status;
      if (st === 'resolved' || st === 'closed') {
        // Extract wo_id from the card's view link
        const link = card.querySelector('a[href*="view.php"]');
        if (link) {
          const m = link.href.match(/[?&]id=(\d+)/);
          if (m) resolvedWoIds.add(m[1]);
        }
      }
    });

    for (const woId of resolvedWoIds) {
      const draftKey = `mrtsp.draft.${woId}.v1`;
      if (localStorage.getItem(draftKey) !== null) {
        localStorage.removeItem(draftKey);
        draftsRemoved++;
      }
      // Also remove the session sync flag
      localStorage.removeItem(`mrtsp.synced_session.${woId}`);
    }

    // ── Step 4: Remove synced items from the offline queue ──
    // Items marked synced:true are already processed — safe to drop.
    const cleanQueue = queue.filter(item => !item.synced);
    queueItemsRemoved = queue.length - cleanQueue.length;
    if (queueItemsRemoved > 0) {
      localStorage.setItem(LS_QUEUE, JSON.stringify(cleanQueue));
    }

    // ── Done ──
    const parts = [];
    if (blobsRemoved)      parts.push(`${blobsRemoved} cached file${blobsRemoved > 1 ? 's' : ''}`);
    if (draftsRemoved)     parts.push(`${draftsRemoved} old draft${draftsRemoved > 1 ? 's' : ''}`);
    if (queueItemsRemoved) parts.push(`${queueItemsRemoved} synced queue item${queueItemsRemoved > 1 ? 's' : ''}`);

    const summary = parts.length ? parts.join(', ') + ' removed' : 'Nothing to clear — cache is clean';

    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Done';
    btn.style.background = 'var(--olfu-green)';
    btn.style.borderColor = 'var(--olfu-green)';
    btn.style.color = '#fff';

    // Show a small toast below the button
    const toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);z-index:9999;' +
      'background:#1e293b;color:#e2e8f0;padding:10px 18px;border-radius:8px;font-size:13px;' +
      'box-shadow:0 4px 16px rgba(0,0,0,.25);white-space:nowrap;';
    toast.textContent = summary;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);

  } catch (e) {
    console.error('[clearCache] Error:', e);
    btn.innerHTML = origHTML;
  } finally {
    setTimeout(() => {
      btn.disabled = false;
      btn.innerHTML = origHTML;
      btn.style.background = '';
      btn.style.borderColor = '';
      btn.style.color = '';
    }, 2500);
  }
}

function claimJob(woId, button) {
  if (window.MRTS && window.MRTS.modal) {
    window.MRTS.modal.confirm('Claim this work order?', {
      title: 'Claim Job',
      okLabel: 'Claim',
      cancelLabel: 'Cancel',
    }).then(function(confirmed) {
      if (!confirmed) return;
      _doClaimJob(woId, button);
    });
  } else {
    if (!confirm('Claim this work order?')) return;
    _doClaimJob(woId, button);
  }
}

function _doClaimJob(woId, button) {
  button.disabled = true;
  button.textContent = 'Claiming…';

  fetch('<?php echo BASE_URL; ?>modules/technician/api/claim.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'wo_id=' + woId
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) { window.location.reload(); }
    else {
      const msg = data.message || 'Failed to claim job';
      if (window.MRTS && window.MRTS.modal) {
        window.MRTS.modal.alert(msg, { type: 'error', title: 'Claim Failed' });
      } else { alert(msg); }
      button.disabled = false;
      button.textContent = 'Claim Job';
    }
  })
  .catch(() => {
    const msg = 'Network error while claiming job';
    if (window.MRTS && window.MRTS.modal) {
      window.MRTS.modal.alert(msg, { type: 'error', title: 'Network Error' });
    } else { alert(msg); }
    button.disabled = false;
    button.textContent = 'Claim Job';
  });
}



(function () {
  if (document.querySelector('link[rel="manifest"]')) return;
  const link = document.createElement('link');
  link.rel = 'manifest';
  link.href = '<?php echo BASE_URL; ?>public/manifest.json';
  document.head.appendChild(link);
})();

window.MRTS = {
  APP_BASE: '<?php echo BASE_URL; ?>',
  USER_ID:  <?php echo json_encode($_SESSION['user_id'] ?? null); ?>,
  ROLE_ID:  <?php echo json_encode($_SESSION['role_id'] ?? null); ?>
};
</script>

<script src="<?php echo BASE_URL; ?>modules/technician/public/app.js"></script>
<script src="<?php echo BASE_URL; ?>modules/technician/public/modal.js"></script>
<script src="<?php echo BASE_URL; ?>modules/technician/public/offline.js"></script>
<script src="<?php echo BASE_URL; ?>modules/technician/public/idb-storage.js"></script>
<script src="<?php echo BASE_URL; ?>modules/technician/public/jobs.js"></script>