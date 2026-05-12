<?php
// modules/notifications/index.view.php — Notification history (HTML only)
?>

<!-- Page header -->
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-xl font-bold text-gray-900">Notifications</h1>
    <p class="text-sm text-gray-500 mt-0.5">
      <?= $total ?> total
      <?php if ($unread > 0): ?>
        &mdash; <span class="text-olfu-green font-medium"><?= $unread ?> unread</span>
      <?php endif; ?>
    </p>
  </div>
  <?php if ($total > 0): ?>
    <button id="page-mark-all"
            class="text-sm text-olfu-green hover:underline font-medium">
      Mark all as read
    </button>
  <?php endif; ?>
</div>

<!-- Notification list -->
<?php if (!$notifs): ?>
  <div class="bg-white rounded-xl border border-gray-100 px-6 py-16 text-center">
    <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24"
         stroke="currentColor" stroke-width="1.5">
      <path stroke-linecap="round" stroke-linejoin="round"
            d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
    </svg>
    <p class="text-sm font-medium text-gray-400">No notifications yet</p>
  </div>
<?php else: ?>
  <div class="bg-white rounded-xl border border-gray-100 divide-y divide-gray-50 overflow-hidden">
    <?php foreach ($notifs as $n): ?>
      <?php
        $was_unread = !$n['is_read']; // captured before mark_all_read ran
        $href = $n['link'] ? htmlspecialchars($n['link']) : '#';
      ?>
      <a href="<?= $href ?>"
         class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50 transition-colors group">

        <!-- Icon -->
        <div class="w-9 h-9 rounded-full bg-green-50 flex items-center justify-center flex-shrink-0 mt-0.5">
          <svg class="w-4 h-4 text-olfu-green" fill="none" viewBox="0 0 24 24"
               stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
          </svg>
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-gray-800 leading-snug">
            <?= htmlspecialchars($n['title']) ?>
          </p>
          <?php if ($n['body']): ?>
            <p class="text-sm text-gray-500 mt-0.5 leading-relaxed">
              <?= htmlspecialchars($n['body']) ?>
            </p>
          <?php endif; ?>
          <p class="text-xs text-gray-400 mt-1">
            <?= notif_time_ago($n['created_at']) ?>
            &nbsp;&middot;&nbsp;
            <?= (new DateTime($n['created_at']))->format('M j, Y \a\t g:i A') ?>
          </p>
        </div>

        <!-- Unread indicator (shown for items that were unread on load) -->
        <?php if ($was_unread): ?>
          <span class="w-2 h-2 rounded-full bg-olfu-green flex-shrink-0 mt-2"
                title="Was unread"></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
    <div class="flex items-center justify-between mt-4">
      <p class="text-sm text-gray-500">
        Page <?= $cur_page ?> of <?= $total_pages ?>
      </p>
      <div class="flex gap-2">
        <?php if ($cur_page > 1): ?>
          <a href="?p=<?= $cur_page - 1 ?>"
             class="px-3 py-1.5 text-sm rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600">
            Previous
          </a>
        <?php endif; ?>
        <?php if ($cur_page < $total_pages): ?>
          <a href="?p=<?= $cur_page + 1 ?>"
             class="px-3 py-1.5 text-sm rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-600">
            Next
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<script>
(function () {
  const BASE_URL = '<?= BASE_URL ?>';
  const btn = document.getElementById('page-mark-all');
  if (!btn) return;

  btn.addEventListener('click', function () {
    fetch(BASE_URL + 'modules/notifications/mark_read.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'all=1',
    }).then(() => {
      // Remove all unread dots on this page
      document.querySelectorAll('[title="Was unread"]').forEach(el => el.remove());
      btn.remove();
    });
  });
})();
</script>
