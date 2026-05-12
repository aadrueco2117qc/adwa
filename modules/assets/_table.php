<?php
// modules/assets/_table.php
// Shared table partial used by list.php (initial render) and search_ajax.php (AJAX updates).
// Expects: $assets, $total, $current_page, $per_page to be set before inclusion.
$total_pages  = max(1, (int) ceil($total / $per_page));
$showing_from = $total > 0 ? ($current_page - 1) * $per_page + 1 : 0;
$showing_to   = min($current_page * $per_page, $total);
?>
<div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
  <div class="overflow-x-auto">
    <table class="w-full border-collapse">
      <?php
      $sc = $filters['sort_col'] ?? 'updated_at';
      $sd = $filters['sort_dir'] ?? 'DESC';
      $th = function(string $col, string $label) use ($sc, $sd): string {
          $active   = $sc === $col;
          $next_dir = ($active && $sd === 'ASC') ? 'DESC' : 'ASC';
          $arrow    = $active ? ($sd === 'ASC' ? ' ↑' : ' ↓') : ' ↕';
          $color    = $active ? 'text-olfu-green' : 'text-gray-400';
          return "<th class=\"px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-500 whitespace-nowrap\">
                    <button type=\"button\" onclick=\"sortBy('{$col}','{$next_dir}')\"
                            class=\"flex items-center justify-center gap-1 w-full hover:text-gray-800 transition-colors\">
                      {$label}<span class=\"{$color} text-sm\">{$arrow}</span>
                    </button>
                  </th>";
      };
      ?>
      <thead>
        <tr class="bg-gray-50 border-b border-gray-200">
          <th class="bulk-col hidden px-3 py-3 w-10 text-center">
            <input type="checkbox" id="select-all-chk" onchange="selectAllAssets(this.checked)"
                   class="w-4 h-4 rounded accent-green-700 cursor-pointer">
          </th>
          <?= $th('asset_tag',     'Asset Tag') ?>
          <?= $th('manufacturer',  'Manufacturer / Model') ?>
          <?= $th('category_name', 'Category') ?>
          <?= $th('building',      'Building') ?>
          <?= $th('floor',         'Floor') ?>
          <?= $th('room',          'Room') ?>
          <?= $th('status',        'Status') ?>
          <?= $th('warranty_end',  'Warranty Expiry') ?>
          <?= $th('updated_at',    'Last Updated') ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($assets)): ?>
          <tr>
            <td colspan="10" class="px-4 py-12 text-center text-gray-400 text-sm">
              No assets found matching your filters.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($assets as $a): ?>
            <tr class="bulk-row border-b border-gray-50 hover:bg-gray-50 transition-colors cursor-pointer"
                onclick="handleRowClick(event, <?= $a['asset_id'] ?>)">
              <td class="bulk-col hidden px-3 py-3 text-center whitespace-nowrap" onclick="event.stopPropagation()">
                <input type="checkbox" class="bulk-chk w-4 h-4 rounded accent-green-700 cursor-pointer"
                       value="<?= $a['asset_id'] ?>" onchange="updateBulkCount()">
              </td>
              <td class="px-4 py-3 text-center whitespace-nowrap"><span class="asset-tag"><?= htmlspecialchars($a['asset_tag']) ?></span></td>
              <td class="px-4 py-3 text-center font-medium text-gray-800 whitespace-nowrap max-w-[200px] overflow-hidden text-ellipsis" title="<?= htmlspecialchars($a['manufacturer'] . ' ' . $a['model']) ?>"><?= htmlspecialchars($a['manufacturer'] . ' ' . $a['model']) ?></td>
              <td class="px-4 py-3 text-center whitespace-nowrap"><?= cat_badge($a['category_name'] ?? '—') ?></td>
              <td class="px-4 py-3 text-center text-gray-700 text-sm whitespace-nowrap"><?= htmlspecialchars($a['building'] ?? '—') ?></td>
              <td class="px-4 py-3 text-center text-gray-700 text-sm whitespace-nowrap"><?= htmlspecialchars($a['floor'] ?? '—') ?></td>
              <td class="px-4 py-3 text-center text-gray-700 text-sm whitespace-nowrap"><?= htmlspecialchars($a['room'] ?? '—') ?></td>
              <td class="px-4 py-3 text-center whitespace-nowrap"><?= status_badge($a['status']) ?></td>
              <td class="px-4 py-3 text-center text-sm whitespace-nowrap"><?= warranty_cell($a['warranty_end']) ?></td>
              <td class="px-4 py-3 text-center text-xs text-gray-400 whitespace-nowrap"><?= time_ago($a['updated_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="pg-wrap">
    <span>
      <?php if ($total === 0): ?>
        No assets found
      <?php else: ?>
        Showing <?= $showing_from ?>–<?= $showing_to ?> of <?= $total ?> asset<?= $total !== 1 ? 's' : '' ?>
      <?php endif; ?>
    </span>
    <div class="pg-btns">
      <?php if ($current_page > 1): ?>
        <button class="pg-btn" onclick="goToPage(<?= $current_page - 1 ?>)">‹</button>
      <?php endif; ?>

      <?php
      $range = 2;
      $sp    = max(1, $current_page - $range);
      $ep    = min($total_pages, $current_page + $range);
      if ($sp > 1) {
          echo '<button class="pg-btn" onclick="goToPage(1)">1</button>';
          if ($sp > 2) echo '<span class="pg-btn" style="cursor:default;border:none">…</span>';
      }
      for ($i = $sp; $i <= $ep; $i++) {
          $cls = $i === $current_page ? ' pg-on' : '';
          echo "<button class=\"pg-btn{$cls}\" onclick=\"goToPage({$i})\">{$i}</button>";
      }
      if ($ep < $total_pages) {
          if ($ep < $total_pages - 1) echo '<span class="pg-btn" style="cursor:default;border:none">…</span>';
          echo "<button class=\"pg-btn\" onclick=\"goToPage({$total_pages})\">{$total_pages}</button>";
      }
      ?>

      <?php if ($current_page < $total_pages): ?>
        <button class="pg-btn" onclick="goToPage(<?= $current_page + 1 ?>)">›</button>
      <?php endif; ?>
    </div>
  </div>
</div>
