<?php
// modules/users/_table.php
// Shared table partial — used by index.view.php (initial) and search_ajax.php (AJAX).
// Expects: $users, $total, $current_page, $per_page, $filters
$total_pages  = max(1, (int)ceil($total / $per_page));
$showing_from = $total > 0 ? ($current_page - 1) * $per_page + 1 : 0;
$showing_to   = min($current_page * $per_page, $total);

$sc = $filters['sort_col'] ?? 'created_at';
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

$logged_in_id = (int)($_SESSION['user_id'] ?? 0);
$actor_role   = current_user_role($pdo);
?>
<div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
  <div class="overflow-x-auto">
    <table class="w-full border-collapse" id="users-table">
      <thead>
        <tr class="bg-gray-50 border-b border-gray-200">
          <th class="bulk-col hidden px-3 py-3 w-10 text-center">
            <input type="checkbox" id="select-all-chk" onchange="selectAllUsers(this.checked)"
                   class="w-4 h-4 rounded accent-green-700 cursor-pointer">
          </th>
          <?= $th('full_name',  'User') ?>
          <?= $th('role_name',  'Role') ?>
          <?= $th('department', 'Department') ?>
          <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-500 whitespace-nowrap">ID / Position</th>
          <?= $th('is_active',  'Status') ?>
          <?= $th('last_login', 'Last Login') ?>
          <?= $th('created_at', 'Created') ?>
          <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-500 whitespace-nowrap">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr>
            <td colspan="9" class="px-4 py-12 text-center text-gray-400 text-sm italic">
              No users found matching your filters.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($users as $u): ?>
            <tr class="u-row border-b border-gray-50 transition-colors">
              <td class="bulk-col hidden px-3 py-3 text-center whitespace-nowrap" onclick="event.stopPropagation()">
                <input type="checkbox" class="bulk-chk w-4 h-4 rounded accent-green-700 cursor-pointer"
                       value="<?= $u['user_id'] ?>" onchange="updateBulkCount()">
              </td>
              <!-- User name + email -->
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex items-center gap-2.5">
                  <div class="u-avatar" style="background:<?= u_avatar_color($u['role_name'] ?? '') ?>">
                    <?= htmlspecialchars(user_initials($u['full_name'])) ?>
                  </div>
                  <div>
                    <div class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($u['full_name']) ?></div>
                    <div class="text-xs text-gray-400"><?= htmlspecialchars($u['email']) ?></div>
                  </div>
                </div>
              </td>
              <!-- Role -->
              <td class="px-4 py-3 text-center whitespace-nowrap">
                <?= role_badge($u['role_name'] ?? '') ?>
              </td>
              <!-- Department -->
              <td class="px-4 py-3 text-center text-sm text-gray-600 whitespace-nowrap">
                <?= htmlspecialchars($u['department_name'] ?? '—') ?>
              </td>
              <!-- ID / Position -->
              <td class="px-4 py-3 text-center whitespace-nowrap">
                <?php if ($u['id_number']): ?>
                  <span class="font-mono text-xs text-olfu-green font-semibold"><?= htmlspecialchars($u['id_number']) ?></span>
                <?php endif; ?>
                <?php if ($u['position']): ?>
                  <div class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($u['position']) ?></div>
                <?php endif; ?>
                <?php if (!$u['id_number'] && !$u['position']): ?>
                  <span class="text-gray-300 italic text-xs">—</span>
                <?php endif; ?>
              </td>
              <!-- Status -->
              <td class="px-4 py-3 text-center whitespace-nowrap">
                <?= user_status_badge((int)$u['is_active']) ?>
              </td>
              <!-- Last login -->
              <td class="px-4 py-3 text-center whitespace-nowrap">
                <?= user_time_ago($u['last_login']) ?>
              </td>
              <!-- Created -->
              <td class="px-4 py-3 text-center text-xs text-gray-400 whitespace-nowrap">
                <?= date('M j, Y', strtotime($u['created_at'])) ?>
              </td>
              <!-- Actions -->
              <?php
              $target_role    = $u['role_name'] ?? '';
              $is_self        = ($u['user_id'] === $logged_in_id);
              $is_super_admin = ($target_role === 'super_admin');
              $can_manage     = !$is_self && can_manage_user($actor_role, $target_role);
              ?>
              <td class="px-4 py-3 text-center whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                  <!-- Edit: super_admin edits anyone; admin edits non-admin/non-super_admin; self edits self -->
                  <?php if ($is_self || $actor_role === 'super_admin' || ($actor_role === 'admin' && $target_role !== 'super_admin' && $target_role !== 'admin')): ?>
                  <a href="edit.php?id=<?= $u['user_id'] ?>"
                     title="Edit user"
                     class="row-action">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
                    </svg>
                  </a>
                  <?php endif; ?>

                  <?php if ($can_manage): ?>
                    <?php if ($u['is_active']): ?>
                      <button type="button" title="Deactivate"
                              onclick="toggleActive(<?= $u['user_id'] ?>, 0, this)"
                              class="row-action danger">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 115.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                      </button>
                    <?php else: ?>
                      <button type="button" title="Activate"
                              onclick="toggleActive(<?= $u['user_id'] ?>, 1, this)"
                              class="row-action">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                      </button>
                    <?php endif; ?>
                  <?php elseif ($is_super_admin): ?>
                    <span title="Protected — Super Admin account cannot be deactivated"
                          class="inline-flex items-center justify-center w-7 h-7 text-red-400 opacity-70 cursor-default">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                      </svg>
                    </span>
                  <?php endif; ?>
                </div>
              </td>
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
        No users found
      <?php else: ?>
        Showing <?= $showing_from ?>–<?= $showing_to ?> of <?= $total ?> user<?= $total !== 1 ? 's' : '' ?>
      <?php endif; ?>
    </span>
    <div class="pg-btns">
      <button class="pg-btn" onclick="goPage(1)" <?= $current_page <= 1 ? 'disabled' : '' ?>>«</button>
      <button class="pg-btn" onclick="goPage(<?= $current_page - 1 ?>)" <?= $current_page <= 1 ? 'disabled' : '' ?>>‹</button>
      <?php
      $start = max(1, $current_page - 2);
      $end   = min($total_pages, $current_page + 2);
      for ($i = $start; $i <= $end; $i++): ?>
        <button class="pg-btn <?= $i === $current_page ? 'pg-on' : '' ?>" onclick="goPage(<?= $i ?>)"><?= $i ?></button>
      <?php endfor; ?>
      <button class="pg-btn" onclick="goPage(<?= $current_page + 1 ?>)" <?= $current_page >= $total_pages ? 'disabled' : '' ?>>›</button>
      <button class="pg-btn" onclick="goPage(<?= $total_pages ?>)" <?= $current_page >= $total_pages ? 'disabled' : '' ?>>»</button>
    </div>
  </div>
</div>

<?php
function u_avatar_color(string $role): string {
    $map = [
        'super_admin'      => '#b91c1c',
        'admin'            => '#7c3aed',
        'it_manager'       => '#1d4ed8',
        'it_staff'         => '#0369a1',
        'technician'       => '#c2410c',
        'faculty'          => '#0f766e',
        'department_staff' => '#a16207',
        'student'          => '#4b5563',
    ];
    return $map[$role] ?? '#1a5c2a';
}
?>
