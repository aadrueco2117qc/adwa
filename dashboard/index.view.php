<?php
// modules/dashboard/index.view.php

function dash_time_ago(?string $dt): string {
    if (!$dt) return '—';
    $diff = time() - strtotime($dt);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($dt));
}

function dash_status_cls(string $status): string {
    return match($status) {
        'active'  => 'bg-green-100 text-green-700',
        'spare'   => 'bg-yellow-100 text-yellow-700',
        'retired' => 'bg-gray-100 text-gray-500',
        default   => 'bg-gray-100 text-gray-500',
    };
}
?>

<!-- Page header -->
<div class="mb-5">
  <h2 class="text-xl font-bold text-gray-900 tracking-tight">Dashboard</h2>
  <p class="text-sm text-gray-400 mt-0.5">
    Welcome back, <strong class="text-gray-600"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></strong>.
    Here's what's happening in MTRTS.
  </p>
</div>

<!-- ── Stat Cards ─────────────────────────────────────────────── -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">

  <!-- Total Assets -->
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Assets</p>
    <p class="text-3xl font-bold text-gray-900 mt-1"><?= (int)$asset_stats['total'] ?></p>
    <div class="flex gap-2 mt-2 flex-wrap">
      <span class="text-xs text-green-600 font-medium"><?= (int)$asset_stats['active'] ?> active</span>
      <span class="text-xs text-yellow-500 font-medium"><?= (int)$asset_stats['spare'] ?> spare</span>
      <span class="text-xs text-gray-400 font-medium"><?= (int)$asset_stats['retired'] ?> retired</span>
    </div>
  </div>

  <!-- Warranty Expiring -->
  <div class="bg-white rounded-xl border <?= (int)$asset_stats['expiring'] > 0 ? 'border-red-200' : 'border-gray-100' ?> shadow-sm px-5 py-4">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Warranties Expiring</p>
    <p class="text-3xl font-bold <?= (int)$asset_stats['expiring'] > 0 ? 'text-red-600' : 'text-gray-900' ?> mt-1"><?= (int)$asset_stats['expiring'] ?></p>
    <p class="text-xs text-gray-400 mt-2">within 30 days</p>
  </div>

  <!-- Tickets -->
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Tickets</p>
    <p class="text-3xl font-bold text-gray-900 mt-1"><?= (int)$ticket_stats['total'] ?></p>
    <p class="text-xs text-gray-400 mt-2"><?= (int)$ticket_stats['open'] ?> open</p>
  </div>

  <!-- Active Users -->
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Users</p>
    <p class="text-3xl font-bold text-gray-900 mt-1"><?= (int)$user_stats['active'] ?></p>
    <p class="text-xs text-gray-400 mt-2">of <?= (int)$user_stats['total'] ?> total</p>
  </div>

</div>

<!-- ── Two-column lower section ───────────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

  <!-- Recent Activity -->
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
    <h3 class="text-sm font-bold text-gray-800 mb-4">Recent Asset Activity</h3>

    <?php if (empty($recent_activity)): ?>
      <p class="text-sm text-gray-400 text-center py-8">No activity recorded yet.</p>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($recent_activity as $ev): ?>
        <div class="flex items-start gap-3">
          <div class="w-7 h-7 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center flex-shrink-0 mt-0.5">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-gray-800 truncate">
              <?= htmlspecialchars($ev['changed_by_name'] ?? 'Unknown') ?>
              updated <span class="text-gray-500"><?= htmlspecialchars($ev['field_name']) ?></span>
              on <span class="font-semibold"><?= htmlspecialchars($ev['asset_tag'] ?? '—') ?></span>
            </p>
            <p class="text-xs text-gray-400 truncate">
              <?= htmlspecialchars($ev['old_value'] ?? '—') ?> → <?= htmlspecialchars($ev['new_value'] ?? '—') ?>
            </p>
          </div>
          <span class="text-[11px] text-gray-300 flex-shrink-0"><?= dash_time_ago($ev['changed_at']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Recently Added Assets -->
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-bold text-gray-800">Recently Added Assets</h3>
      <a href="<?= BASE_URL ?>modules/assets/index.php"
         class="text-xs text-olfu-green hover:underline font-medium">View all →</a>
    </div>

    <?php if (empty($recent_assets)): ?>
      <p class="text-sm text-gray-400 text-center py-8">No assets added yet.</p>
    <?php else: ?>
      <div class="space-y-2.5">
        <?php foreach ($recent_assets as $a): ?>
        <a href="<?= BASE_URL ?>modules/assets/view.php?id=<?= $a['asset_id'] ?>"
           class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors duration-100 group">
          <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-gray-800 truncate group-hover:text-olfu-green transition-colors">
              <?= htmlspecialchars($a['asset_tag']) ?>
              <?php if ($a['model']): ?>
                <span class="font-normal text-gray-500">— <?= htmlspecialchars($a['model']) ?></span>
              <?php endif; ?>
            </p>
            <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($a['category_name'] ?? '—') ?></p>
          </div>
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold <?= dash_status_cls($a['status']) ?>">
            <?= htmlspecialchars(ucfirst($a['status'])) ?>
          </span>
          <span class="text-[11px] text-gray-300 flex-shrink-0"><?= dash_time_ago($a['created_at']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>
