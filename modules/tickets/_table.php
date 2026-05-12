<?php
/**
 * modules/tickets/_table.php — Included by index.php and search_ajax.php
 *
 * @var array $tickets       Result of get_tickets()
 * @var array $filters
 * @var int   $total         Result of count_tickets()
 * @var int   $current_page
 * @var int   $per_page
 */

// Sorting/filtering state with defaults
$sc = $filters['sort_col'] ?? 'updated_at';
$sd = $filters['sort_dir'] ?? 'DESC';

// Helper to render sortable column headers
$render_th = function(string $col_key, string $label) use ($sc, $sd) {
    $is_active = ($sc === $col_key);
    $dir       = $is_active ? $sd : 'DESC';
    $next_dir  = ($is_active && $dir === 'DESC') ? 'ASC' : 'DESC';
    
    $arrow = '';
    if ($is_active) {
        $arrow = $dir === 'ASC' 
            ? '<svg class="w-3 h-3 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>' 
            : '<svg class="w-3 h-3 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';
    } else {
        $arrow = '<svg class="w-3 h-3 text-gray-300 group-hover:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>';
    }

    return sprintf(
        '<th onclick="sortBy(\'%s\', \'%s\')" class="group hover:bg-gray-100 cursor-pointer select-none transition-colors"><div class="flex items-center gap-1.5">%s %s</div></th>',
        $col_key, $next_dir, htmlspecialchars($label), $arrow
    );
};
?>

<?php if (empty($tickets)): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 text-center py-16">
  <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
  <h3 class="text-base font-semibold text-gray-900">No tickets found</h3>
  <p class="text-sm text-gray-400 mt-1">Try adjusting your search or filters.</p>
</div>
<?php else: ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
  <table class="wo-table">
    <thead>
      <tr>
        <?= $render_th('ticket_number', 'Ticket ID') ?>
        <?= $render_th('status', 'Status') ?>
        <th>Priority</th>
        <th>Requester & Asset</th>
        <th>Issue Description</th>
        <?= $render_th('updated_at', 'Last Updated') ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tickets as $t): ?>
      <?php 
         $tr_js = 'onclick="window.location.href=\'view.php?id=' . $t['ticket_id'] . '\'"';
      ?>
      <tr <?= $tr_js ?>>
        <td class="font-mono font-medium text-olfu-green leading-tight">
          <?= htmlspecialchars($t['ticket_number']) ?>
        </td>
        <td>
          <?= ticket_status_badge($t['status']) ?>
        </td>
        <td>
          <?= ticket_priority_badge($t['priority']) ?>
        </td>
        <td>
          <div class="font-medium text-gray-900">
             <?= htmlspecialchars($t['full_name'] ?: 'System') ?>
          </div>
          <?php if ($t['asset_tag']): ?>
          <div class="text-xs text-gray-500 mt-0.5">Asset: <?= htmlspecialchars($t['asset_tag']) ?></div>
          <?php endif; ?>
        </td>
        <td class="max-w-xs truncate">
          <div class="font-medium text-gray-900 truncate">
             <?php if ($t['is_event_support']): ?>
                <span class="text-red-600 font-bold mr-1" title="Urgent Event Support">⚡</span>
             <?php endif; ?>
             <?= htmlspecialchars($t['title']) ?>
          </div>
          <?php if ($t['category_name']): ?>
             <div class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($t['category_name']) ?></div>
          <?php endif; ?>
        </td>
        <td>
          <div class="text-gray-900 font-medium"><?= ticket_time_ago($t['updated_at']) ?></div>
          <div class="text-xs text-gray-400 mt-0.5">by <?= htmlspecialchars($t['assigned_to_name'] ?: 'Unassigned') ?></div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
// Pagination logic
$total_pages = ceil($total / $per_page);
if ($total_pages > 1):
  $start = ($current_page - 1) * $per_page + 1;
  $end   = min($current_page * $per_page, $total);
?>
<div class="mt-4 flex items-center justify-between px-2">
  <div class="text-sm text-gray-500 font-medium">
    Showing <span class="text-gray-900"><?= $start ?></span> to <span class="text-gray-900"><?= $end ?></span> of <span class="text-gray-900"><?= $total ?></span> tickets
  </div>
  <div class="flex items-center gap-1">
    <button <?= $current_page > 1 ? 'onclick="goToPage('.($current_page-1).')"' : 'disabled' ?> 
      class="px-3 py-1.5 rounded text-sm font-medium border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-gray-700">
      Prev
    </button>
    <button <?= $current_page < $total_pages ? 'onclick="goToPage('.($current_page+1).')"' : 'disabled' ?> 
      class="px-3 py-1.5 rounded text-sm font-medium border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed text-gray-700">
      Next
    </button>
  </div>
</div>
<?php endif; ?>

<?php endif; ?>
