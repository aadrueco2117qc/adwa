<?php
// modules/profile/index.view.php
// Receives: $profile, $flash_ok, $flash_err, $asset_activity, $created_assets, $tickets, $work_orders

$role_labels = [
    'super_admin'      => 'Super Admin',
    'admin'            => 'Admin',
    'it_manager'       => 'IT Manager',
    'it_staff'         => 'IT Staff',
    'technician'       => 'Technician',
    'faculty'          => 'Faculty',
    'department_staff' => 'Dept. Staff',
    'student'          => 'Student',
];

$role_colors = [
    'super_admin'      => 'bg-red-100 text-red-700',
    'admin'            => 'bg-purple-100 text-purple-700',
    'it_manager'       => 'bg-blue-100 text-blue-700',
    'it_staff'         => 'bg-sky-100 text-sky-700',
    'technician'       => 'bg-orange-100 text-orange-700',
    'faculty'          => 'bg-teal-100 text-teal-700',
    'department_staff' => 'bg-yellow-100 text-yellow-700',
    'student'          => 'bg-gray-100 text-gray-600',
];

$role_name  = $profile['role_name'] ?? '';
$role_label = $role_labels[$role_name] ?? ucwords(str_replace('_', ' ', $role_name));
$role_cls   = $role_colors[$role_name] ?? 'bg-gray-100 text-gray-600';

// Avatar
$pic_path = $profile['profile_picture'] ?? '';
$has_pic  = $pic_path && is_file(__DIR__ . '/../../' . ltrim($pic_path, '/'));
$avatar_url = $has_pic ? BASE_URL . $pic_path : '';

// Initials fallback
$initials = '';
foreach (explode(' ', trim($profile['full_name'] ?? 'U')) as $w) {
    if ($w !== '') { $initials .= strtoupper($w[0]); if (strlen($initials) >= 2) break; }
}
if ($initials === '') $initials = 'U';
?>

<!-- Flash Messages -->
<?php if ($flash_ok !== ''): ?>
<div class="mb-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2">
  <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
  <?= htmlspecialchars($flash_ok) ?>
</div>
<?php endif; ?>
<?php if ($flash_err !== ''): ?>
<div class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-2">
  <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
  <?= htmlspecialchars($flash_err) ?>
</div>
<?php endif; ?>

<!-- ── Profile Header Card ─────────────────────────────────── -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
  <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">

    <!-- Avatar with upload overlay -->
    <div class="relative flex-shrink-0">
      <div class="w-24 h-24 rounded-full overflow-hidden bg-olfu-green flex items-center justify-center ring-4 ring-green-100">
        <?php if ($has_pic): ?>
          <img src="<?= htmlspecialchars(BASE_URL . $pic_path) ?>" alt="Profile" class="w-full h-full object-cover" />
        <?php else: ?>
          <span class="text-white text-3xl font-bold"><?= htmlspecialchars($initials) ?></span>
        <?php endif; ?>
      </div>
      <!-- Upload button overlay -->
      <button type="button" onclick="document.getElementById('avatar-file').click()"
              title="Change profile picture"
              class="absolute bottom-0 right-0 w-8 h-8 bg-olfu-green hover:bg-olfu-green-md text-white rounded-full flex items-center justify-center shadow-md transition-colors duration-150">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
      </button>
      <!-- Hidden upload form -->
      <form id="avatar-form" action="upload_avatar.php" method="POST" enctype="multipart/form-data">
        <input type="file" id="avatar-file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif"
               class="hidden" onchange="document.getElementById('avatar-form').submit()" />
      </form>
    </div>

    <!-- User Info -->
    <div class="flex-1 text-center sm:text-left">
      <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($profile['full_name']) ?></h2>
      <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mt-1.5">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $role_cls ?>">
          <?= htmlspecialchars($role_label) ?>
        </span>
        <?php if ($profile['department_name']): ?>
          <span class="text-sm text-gray-500"><?= htmlspecialchars($profile['department_name']) ?></span>
        <?php endif; ?>
      </div>
      <p class="text-sm text-gray-400 mt-1"><?= htmlspecialchars($profile['email']) ?></p>
    </div>

    <!-- Quick stats -->
    <div class="flex gap-6 text-center flex-shrink-0">
      <div>
        <p class="text-xs text-gray-400 mb-0.5">Member since</p>
        <p class="text-sm font-semibold text-gray-700"><?= date('M j, Y', strtotime($profile['created_at'])) ?></p>
      </div>
      <div>
        <p class="text-xs text-gray-400 mb-0.5">Last login</p>
        <p class="text-sm font-semibold text-gray-700">
          <?= $profile['last_login'] ? date('M j, Y', strtotime($profile['last_login'])) : 'N/A' ?>
        </p>
      </div>
      <?php if ($profile['id_number']): ?>
      <div>
        <p class="text-xs text-gray-400 mb-0.5">ID Number</p>
        <p class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($profile['id_number']) ?></p>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- ── Edit Profile + Change Password (two columns) ─────────── -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

  <!-- Edit Profile -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
      <svg class="w-4 h-4 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
      </svg>
      Edit Profile
    </h3>

    <form action="save.php" method="POST">
      <input type="hidden" name="action" value="profile" />

      <div class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">Full Name <span class="text-red-500">*</span></label>
          <input type="text" name="full_name" required
                 value="<?= htmlspecialchars($profile['full_name']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-olfu-green focus:border-transparent transition" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">Email Address</label>
          <input type="text" value="<?= htmlspecialchars($profile['email']) ?>" disabled
                 class="w-full border border-gray-100 rounded-lg px-3 py-2.5 text-sm text-gray-400 bg-gray-50 cursor-not-allowed" />
          <p class="text-xs text-gray-400 mt-1">Contact an administrator to change your email.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">Contact Number</label>
            <input type="text" name="contact_number"
                   value="<?= htmlspecialchars($profile['contact_number'] ?? '') ?>"
                   placeholder="e.g. 09XX-XXX-XXXX"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-olfu-green focus:border-transparent transition" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">Position / Title</label>
            <input type="text" name="position"
                   value="<?= htmlspecialchars($profile['position'] ?? '') ?>"
                   placeholder="e.g. Professor, IT Officer"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-olfu-green focus:border-transparent transition" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">Role</label>
            <div class="flex items-center gap-2 border border-gray-100 rounded-lg px-3 py-2.5 bg-gray-50">
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $role_cls ?>">
                <?= htmlspecialchars($role_label) ?>
              </span>
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">Department</label>
            <input type="text" value="<?= htmlspecialchars($profile['department_name'] ?? 'N/A') ?>" disabled
                   class="w-full border border-gray-100 rounded-lg px-3 py-2.5 text-sm text-gray-400 bg-gray-50 cursor-not-allowed" />
          </div>
        </div>
      </div>

      <div class="mt-5">
        <button type="submit"
                class="bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors duration-150">
          Save Changes
        </button>
      </div>
    </form>
  </div>

  <!-- Change Password -->
  <div id="security" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
      <svg class="w-4 h-4 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
      </svg>
      Change Password
    </h3>

    <form action="save.php" method="POST" id="pw-form">
      <input type="hidden" name="action" value="password" />

      <div class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">Current Password <span class="text-red-500">*</span></label>
          <div class="relative">
            <input type="password" name="current_password" id="pw-current" required
                   placeholder="Enter current password"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 pr-10 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-olfu-green focus:border-transparent transition" />
            <button type="button" onclick="togglePw('pw-current','eye1a','eye1b')" tabindex="-1"
                    class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600">
              <svg id="eye1a" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <svg id="eye1b" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.087-3.288M6.228 6.228A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.384 5.293M3 3l18 18"/></svg>
            </button>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">New Password <span class="text-red-500">*</span></label>
          <div class="relative">
            <input type="password" name="new_password" id="pw-new" required minlength="8"
                   placeholder="At least 8 characters"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 pr-10 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-olfu-green focus:border-transparent transition" />
            <button type="button" onclick="togglePw('pw-new','eye2a','eye2b')" tabindex="-1"
                    class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600">
              <svg id="eye2a" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <svg id="eye2b" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.087-3.288M6.228 6.228A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.384 5.293M3 3l18 18"/></svg>
            </button>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">Confirm New Password <span class="text-red-500">*</span></label>
          <div class="relative">
            <input type="password" name="confirm_password" id="pw-confirm" required
                   placeholder="Re-enter new password"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 pr-10 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-olfu-green focus:border-transparent transition" />
            <button type="button" onclick="togglePw('pw-confirm','eye3a','eye3b')" tabindex="-1"
                    class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600">
              <svg id="eye3a" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <svg id="eye3b" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.087-3.288M6.228 6.228A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.384 5.293M3 3l18 18"/></svg>
            </button>
          </div>
          <p id="pw-match-msg" class="text-xs mt-1 hidden"></p>
        </div>
      </div>

      <div class="mt-5">
        <button type="submit"
                class="bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors duration-150">
          Update Password
        </button>
      </div>
    </form>
  </div>

</div>

<!-- ── Activity History ───────────────────────────────────────── -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
  <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
    <svg class="w-4 h-4 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    Activity History
  </h3>

  <!-- Tabs -->
  <div class="flex gap-1 border-b border-gray-100 mb-5" id="act-tabs">
    <button onclick="switchTab('assets')"   id="tab-assets"   class="act-tab act-tab-active">Assets</button>
    <button onclick="switchTab('tickets')"  id="tab-tickets"  class="act-tab">Tickets</button>
    <button onclick="switchTab('workorders')" id="tab-workorders" class="act-tab">Work Orders</button>
  </div>

  <!-- Tab: Assets -->
  <div id="panel-assets">
    <?php
    $all_asset_events = [];
    foreach ($created_assets as $a) {
        $all_asset_events[] = [
            'type' => 'created',
            'time' => $a['created_at'],
            'tag'  => $a['asset_tag'],
            'id'   => $a['asset_id'],
            'desc' => 'Created asset: ' . htmlspecialchars($a['model'] ?: $a['asset_tag']),
            'sub'  => htmlspecialchars($a['category_name'] ?? '') . ' · Status: ' . $a['status'],
            'color'=> 'bg-green-100 text-green-700',
            'icon' => 'M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z',
        ];
    }
    foreach ($asset_activity as $l) {
        $all_asset_events[] = [
            'type' => 'changed',
            'time' => $l['changed_at'],
            'tag'  => $l['asset_tag'],
            'id'   => $l['asset_id'],
            'desc' => 'Updated ' . htmlspecialchars($l['field_name']) . ' on ' . htmlspecialchars($l['asset_tag'] ?? 'asset'),
            'sub'  => htmlspecialchars($l['old_value'] ?? '–') . ' → ' . htmlspecialchars($l['new_value'] ?? '–') . ($l['change_reason'] ? ' · ' . htmlspecialchars($l['change_reason']) : ''),
            'color'=> 'bg-blue-100 text-blue-700',
            'icon' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z',
        ];
    }
    usort($all_asset_events, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));
    ?>

    <?php if (empty($all_asset_events)): ?>
      <div class="text-center py-12 text-gray-400">
        <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
        </svg>
        <p class="text-sm">No asset activity yet.</p>
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($all_asset_events as $ev): ?>
        <div class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors duration-100">
          <div class="w-8 h-8 rounded-full <?= $ev['color'] ?> flex items-center justify-center flex-shrink-0 mt-0.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="<?= $ev['icon'] ?>"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-800"><?= $ev['desc'] ?></p>
            <p class="text-xs text-gray-500 mt-0.5 truncate"><?= $ev['sub'] ?></p>
          </div>
          <span class="text-xs text-gray-400 flex-shrink-0"><?= profile_time_ago($ev['time']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Tab: Tickets -->
  <div id="panel-tickets" class="hidden">
    <?php if (empty($tickets)): ?>
      <div class="text-center py-12 text-gray-400">
        <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.235 2.235 0 0 0-.1.661Z"/>
        </svg>
        <p class="text-sm">No submitted tickets found.</p>
        <p class="text-xs mt-1">Tickets you submit will appear here.</p>
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($tickets as $t): ?>
        <div class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50">
          <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center flex-shrink-0 mt-0.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($t['title'] ?? 'Ticket #' . $t['ticket_id'] ?? '') ?></p>
            <p class="text-xs text-gray-500 mt-0.5">Status: <?= htmlspecialchars($t['status'] ?? 'Unknown') ?></p>
          </div>
          <span class="text-xs text-gray-400 flex-shrink-0"><?= profile_time_ago($t['created_at'] ?? null) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Tab: Work Orders -->
  <div id="panel-workorders" class="hidden">
    <?php if (empty($work_orders)): ?>
      <div class="text-center py-12 text-gray-400">
        <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
        </svg>
        <p class="text-sm">No work orders assigned yet.</p>
        <p class="text-xs mt-1">Work orders assigned to you will appear here.</p>
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($work_orders as $wo): ?>
        <div class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50">
          <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-700 flex items-center justify-center flex-shrink-0 mt-0.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($wo['title'] ?? 'Work Order #' . ($wo['work_order_id'] ?? '')) ?></p>
            <p class="text-xs text-gray-500 mt-0.5">Status: <?= htmlspecialchars($wo['status'] ?? 'Unknown') ?></p>
          </div>
          <span class="text-xs text-gray-400 flex-shrink-0"><?= profile_time_ago($wo['created_at'] ?? null) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function switchTab(name) {
  ['assets','tickets','workorders'].forEach(t => {
    document.getElementById('panel-' + t).classList.add('hidden');
    const tab = document.getElementById('tab-' + t);
    tab.classList.remove('act-tab-active');
  });
  document.getElementById('panel-' + name).classList.remove('hidden');
  document.getElementById('tab-' + name).classList.add('act-tab-active');
}

function togglePw(inputId, eyeOn, eyeOff) {
  const el = document.getElementById(inputId);
  const on = document.getElementById(eyeOn);
  const off = document.getElementById(eyeOff);
  const hidden = el.type === 'password';
  el.type = hidden ? 'text' : 'password';
  on.classList.toggle('hidden', hidden);
  off.classList.toggle('hidden', !hidden);
}

// Live password match indicator
const pwNew     = document.getElementById('pw-new');
const pwConfirm = document.getElementById('pw-confirm');
const matchMsg  = document.getElementById('pw-match-msg');
function checkMatch() {
  if (!pwConfirm.value) { matchMsg.classList.add('hidden'); return; }
  const match = pwNew.value === pwConfirm.value;
  matchMsg.classList.remove('hidden','text-green-600','text-red-600');
  matchMsg.classList.add(match ? 'text-green-600' : 'text-red-600');
  matchMsg.textContent = match ? 'Passwords match.' : 'Passwords do not match.';
}
pwNew.addEventListener('input', checkMatch);
pwConfirm.addEventListener('input', checkMatch);
</script>

<style>
.act-tab {
  padding: 0.375rem 0.875rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #6b7280;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  transition: color .15s, border-color .15s;
  cursor: pointer;
  background: none;
  border-top: none;
  border-left: none;
  border-right: none;
}
.act-tab:hover { color: #1a5c2a; }
.act-tab-active { color: #1a5c2a; border-bottom-color: #1a5c2a; font-weight: 600; }
</style>
