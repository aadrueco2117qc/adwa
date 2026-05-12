<?php
// modules/workorders/_table.php — Table partial, loaded by index.view.php and search_ajax.php.
// Variables expected: $work_orders, $total, $current_page, $per_page, $filters

$total_pages = max(1, (int) ceil($total / $per_page));
?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full border-collapse text-sm">
      <thead>
        <tr class="border-b border-gray-200">
          <?php
          $cols = [
              'wo_number'       => 'WO #',
              'ticket_number'   => 'Ticket',
              ''                => 'Type',
              '_asset'          => 'Asset',
              'assigned_to'     => 'Assigned To',
              '_status'         => 'Status',
              'scheduled_start' => 'Scheduled',
              'updated_at'      => 'Updated',
          ];
          foreach ($cols as $col => $label):
            $sortable = $col !== '' && $col[0] !== '_';
            $is_sorted = $sortable && ($filters['sort_col'] ?? '') === $col;
            $next_dir  = ($is_sorted && ($filters['sort_dir'] ?? 'DESC') === 'ASC') ? 'DESC' : 'ASC';
          ?>
          <th class="py-3 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400 <?= $sortable ? 'cursor-pointer hover:text-gray-700 select-none' : '' ?>"
              <?= $sortable ? "onclick=\"sortBy('$col','$next_dir')\"" : '' ?>>
            <?= $label ?>
            <?php if ($is_sorted): ?>
              <svg class="inline w-3 h-3 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="<?= ($filters['sort_dir'] ?? 'DESC') === 'ASC' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' ?>"/>
              </svg>
            <?php endif; ?>
          </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($work_orders)): ?>
          <tr><td colspan="8" class="py-12 text-center text-sm text-gray-400 italic">No work orders found matching your filters.</td></tr>
        <?php else: ?>
          <?php foreach ($work_orders as $wo): ?>
            <tr class="row-link border-b border-gray-50"
                onclick="window.location='view.php?id=<?= $wo['wo_id'] ?>'">
              <td class="py-3 px-3"><span class="wo-tag"><?= htmlspecialchars($wo['wo_number']) ?></span></td>
              <td class="py-3 px-3">
                <?php if ($wo['ticket_number']): ?>
                  <span class="wo-tag"><?= htmlspecialchars($wo['ticket_number']) ?></span>
                <?php else: ?>
                  <span class="text-red-300 italic text-xs">Unlinked (Fix Required)</span>
                <?php endif; ?>
              </td>
              <td class="py-3 px-3"><?= wo_type_badge($wo['wo_type']) ?></td>
              <td class="py-3 px-3">
                <?php if ($wo['asset_tag']): ?>
                  <span class="wo-tag"><?= htmlspecialchars($wo['asset_tag']) ?></span>
                <?php else: ?>
                  <span class="text-gray-300">—</span>
                <?php endif; ?>
              </td>
              <td class="py-3 px-3">
                <?php if ($wo['technician_name']): ?>
                  <span class="text-gray-700 font-medium"><?= htmlspecialchars($wo['technician_name']) ?></span>
                <?php else: ?>
                  <span class="text-gray-300 italic">Unassigned</span>
                <?php endif; ?>
              </td>
              <td class="py-3 px-3">
                <?= wo_status_badge($wo['status']) ?>
                <?php if ($wo['status'] === 'on_hold' && $wo['on_hold_reason']): ?>
                  <div class="mt-1"><?= wo_hold_reason($wo['on_hold_reason']) ?></div>
                <?php endif; ?>
              </td>
              <td class="py-3 px-3 text-gray-500">
                <?= $wo['scheduled_start'] ? (new DateTime($wo['scheduled_start']))->format('M j, g:ia') : '<span class="text-gray-300">—</span>' ?>
              </td>
              <td class="py-3 px-3 text-gray-400 text-xs"><?= wo_time_ago($wo['updated_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
  <div class="pg-wrap">
    <span>Showing <?= (($current_page - 1) * $per_page) + 1 ?>–<?= min($current_page * $per_page, $total) ?> of <?= $total ?></span>
    <div class="pg-btns">
      <?php if ($current_page > 1): ?>
        <button class="pg-btn" onclick="goToPage(<?= $current_page - 1 ?>)">‹</button>
      <?php endif; ?>

      <?php
      $range = 2;
      $start_p = max(1, $current_page - $range);
      $end_p   = min($total_pages, $current_page + $range);
      if ($start_p > 1): ?>
        <button class="pg-btn" onclick="goToPage(1)">1</button>
        <?php if ($start_p > 2): ?><span class="px-1 text-gray-300">…</span><?php endif; ?>
      <?php endif; ?>

      <?php for ($p = $start_p; $p <= $end_p; $p++): ?>
        <button class="pg-btn <?= $p === $current_page ? 'pg-on' : '' ?>" onclick="goToPage(<?= $p ?>)"><?= $p ?></button>
      <?php endfor; ?>

      <?php if ($end_p < $total_pages): ?>
        <?php if ($end_p < $total_pages - 1): ?><span class="px-1 text-gray-300">…</span><?php endif; ?>
        <button class="pg-btn" onclick="goToPage(<?= $total_pages ?>)"><?= $total_pages ?></button>
      <?php endif; ?>

      <?php if ($current_page < $total_pages): ?>
        <button class="pg-btn" onclick="goToPage(<?= $current_page + 1 ?>)">›</button>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
