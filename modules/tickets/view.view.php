<?php
/**
 * Injected by view.php before require.
 *
 * @var array  $ticket          Ticket row from get_ticket_by_id()
 * @var bool   $is_staff
 * @var array  $attachments
 * @var array  $comments
 * @var array  $dynamic_fields
 * @var array  $related_wos
 * @var array|null $original_ticket
 * @var array  $duplicates
 * @var array|false $ticket_sla
 */
?>
<!-- Header -->
<div class="mb-4">
  <a href="index.php" class="text-sm text-olfu-green hover:underline flex items-center gap-1 mb-2">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    Back to Tickets
  </a>
  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <div class="flex items-center gap-3 mb-1">
        <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($ticket['ticket_number']) ?></h2>
        <?= ticket_status_badge($ticket['status']) ?>
        <?php if($ticket['is_event_support']): ?>
          <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
            ⚡ Urgent Event
          </span>
        <?php endif; ?>
      </div>
      <h3 class="text-lg text-gray-700"><?= htmlspecialchars($ticket['title']) ?></h3>
    </div>
    <div class="flex flex-wrap gap-2">
      <?php if ($ticket['status'] !== 'closed' && $ticket['status'] !== 'cancelled'): ?>
        <a href="edit.php?id=<?= $ticket['ticket_id'] ?>" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
          Edit Request
        </a>
        <?php if ($is_staff && empty($related_wos)): ?>
        <a href="<?= BASE_URL ?>modules/workorders/add.php?ticket_id=<?= $ticket['ticket_id'] ?>" class="px-4 py-2 bg-olfu-green hover:bg-olfu-green-md text-white rounded-lg text-sm font-semibold transition">
          Create Work Order
        </a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'duplicate_voided'): ?>
    <div class="mt-4 p-4 bg-amber-50 border-l-4 border-amber-500 rounded-lg flex items-center gap-3">
      <svg class="w-6 h-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <div>
        <h4 class="text-sm font-bold text-amber-800">Duplicate Submission Voided</h4>
        <p class="text-xs text-amber-700">A new request was submitted but detected as a duplicate. It has been voided and linked to this ticket for tracking.</p>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($original_ticket): ?>
    <div class="mt-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg flex items-center gap-3">
      <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <div>
        <h4 class="text-sm font-bold text-red-800">Voided: Duplicate Ticket</h4>
        <p class="text-xs text-red-700">This ticket has been marked as a duplicate of 
          <a href="view.php?id=<?= $original_ticket['ticket_id'] ?>" class="font-bold underline">#<?= htmlspecialchars($original_ticket['ticket_number']) ?></a>.
          Please refer to the original ticket for updates.
        </p>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($duplicates)): ?>
    <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded-lg flex flex-col gap-1">
      <div class="flex items-center gap-3">
        <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
        <h4 class="text-sm font-bold text-blue-800">Duplicate Requests (<?= count($duplicates) ?>)</h4>
      </div>
      <div class="pl-9 text-xs text-blue-700">
        The following submissions were identified as duplicates of this ticket:
        <div class="flex flex-wrap gap-2 mt-1">
          <?php foreach ($duplicates as $dup): ?>
            <a href="view.php?id=<?= $dup['ticket_id'] ?>" class="bg-blue-100 px-2 py-0.5 rounded border border-blue-200 hover:bg-blue-200 transition">
              #<?= htmlspecialchars($dup['ticket_number']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <!-- Main Column -->
  <div class="lg:col-span-2 space-y-6">
    <!-- Description -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 font-bold text-gray-900 bg-gray-50 flex justify-between items-center">
        Description
      </div>
      <div class="p-5 text-gray-700 whitespace-pre-wrap font-sans"><?= htmlspecialchars($ticket['description'] ?: 'No description provided.') ?></div>
    </div>
    
    <!-- Attachments -->
    <?php if (!empty($attachments)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-4 border-b border-gray-100 font-bold text-gray-900 bg-gray-50">
        Attachments (<?= count($attachments) ?>)
      </div>
      <div class="p-5">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <?php foreach ($attachments as $att): ?>
          <a href="<?= BASE_URL . htmlspecialchars($att['file_path']) ?>" target="_blank" class="block border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition">
            <div class="flex items-center gap-2 mb-1">
              <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
              <span class="text-sm font-semibold text-olfu-green truncate" title="<?= htmlspecialchars($att['file_name']) ?>">
                <?= htmlspecialchars($att['file_name']) ?>
              </span>
            </div>
            <div class="text-xs text-gray-500"><?= $att['file_size_kb'] ?> KB</div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Comments / Notes -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-4 border-b border-gray-100 font-bold text-gray-900 bg-gray-50">
        Updates & Comments
      </div>
      <div class="p-5 space-y-4">
        <?php if (empty($comments)): ?>
          <div class="text-center text-sm text-gray-400 py-4">No comments yet.</div>
        <?php else: ?>
          <?php foreach ($comments as $c): ?>
            <div class="flex gap-3 <?= $c['is_internal'] ? 'p-3 bg-amber-50 rounded-lg border border-amber-100' : '' ?>">
              <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-200 overflow-hidden">
                <svg class="w-full h-full text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
              </div>
              <div class="flex-1">
                <div class="flex items-baseline gap-2 mb-1">
                  <span class="font-bold text-sm text-gray-900"><?= htmlspecialchars($c['user_name']) ?></span>
                  <span class="text-xs text-gray-500"><?= date('M j, Y h:i A', strtotime($c['created_at'])) ?></span>
                  <?php if ($c['is_internal']): ?>
                    <span class="text-[10px] uppercase tracking-wider font-bold text-amber-700 bg-amber-200 px-1.5 py-0.5 rounded">Internal Note</span>
                  <?php endif; ?>
                </div>
                <div class="text-sm text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars($c['comment_text']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ($ticket['status'] !== 'closed' && $ticket['status'] !== 'cancelled'): ?>
        <form action="save.php" method="POST" class="mt-4 pt-4 border-t border-gray-100">
          <input type="hidden" name="action" value="add_comment">
          <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
          <textarea name="comment_text" rows="3" required class="fin mb-2" placeholder="Write a comment or update..."></textarea>
          <div class="flex justify-between items-center">
            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
              <?php if ($is_staff): ?>
                <input type="checkbox" name="is_internal" value="1" class="text-olfu-green focus:ring-olfu-green rounded border-gray-300">
                Staff Note (hidden from requester)
              <?php endif; ?>
            </label>
            <button type="submit" class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white font-semibold rounded-lg text-sm transition">
              Add Comment
            </button>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <?php
    // ── Service Rating Card ──────────────────────────────────────
    // Visible only to the ticket requester when the linked WO is closed.
    $is_requester = ((int)$ticket['requester_id'] === (int)($_SESSION['user_id'] ?? 0));
    if ($is_requester && $linked_wo_id):
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" id="ratingCard">
      <div class="px-5 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:var(--olfu-green,#2d6a1f);">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
        </svg>
        <span class="font-bold text-gray-900 text-sm">Service Rating</span>
      </div>

      <?php if ($wo_rating && $wo_rating['satisfaction'] !== null): ?>
        <!-- Already rated — read-only display -->
        <div class="p-5">
          <p class="text-xs text-gray-500 mb-3">You rated this service:</p>
          <div class="flex items-center gap-1.5 mb-3">
            <?php for ($s = 1; $s <= 5; $s++): ?>
              <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"
                   style="color:<?= $s <= (int)$wo_rating['satisfaction'] ? '#f59e0b' : '#e5e7eb'; ?>;">
                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
              </svg>
            <?php endfor; ?>
            <span class="text-sm font-semibold text-gray-700 ml-1"><?= (int)$wo_rating['satisfaction'] ?> / 5</span>
          </div>
          <?php if (!empty($wo_rating['feedback'])): ?>
            <div style="padding:10px 14px;border-radius:8px;background:#f9fafb;border:1px solid #e5e7eb;">
              <p class="text-xs text-gray-500 mb-1 font-semibold uppercase tracking-wide">Your feedback</p>
              <p class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($wo_rating['feedback'])) ?></p>
            </div>
          <?php endif; ?>
          <p class="text-xs text-gray-400 mt-3 italic">Thank you for your feedback.</p>
        </div>

      <?php else: ?>
        <!-- Interactive rating form -->
        <div class="p-5">
          <p class="text-sm text-gray-600 mb-4">Your repair request has been completed. How satisfied are you with the service?</p>

          <!-- Stars -->
          <div class="flex items-center gap-1 mb-4" id="ratingStars">
            <?php for ($s = 1; $s <= 5; $s++): ?>
              <button type="button"
                      class="rating-star-btn transition-transform hover:scale-110"
                      data-rating="<?= $s ?>"
                      title="<?= $s ?> star<?= $s > 1 ? 's' : '' ?>"
                      style="background:none;border:none;cursor:pointer;padding:2px;">
                <svg class="w-9 h-9" fill="currentColor" viewBox="0 0 24 24" style="color:#e5e7eb;transition:color .15s;">
                  <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
              </button>
            <?php endfor; ?>
            <span id="ratingLabel" class="text-sm text-gray-400 ml-2">Select a rating</span>
          </div>
          <input type="hidden" id="ratingValue" value="">

          <!-- Feedback -->
          <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-500 mb-1.5" for="ratingFeedback">
              Additional feedback <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <textarea id="ratingFeedback" rows="3"
                      class="fin text-sm w-full resize-none"
                      placeholder="Tell us about your experience…"></textarea>
          </div>

          <!-- Error message -->
          <p id="ratingError" class="text-xs text-red-600 mb-3 hidden"></p>

          <!-- Submit -->
          <button type="button" id="btnSubmitRating"
                  class="inline-flex items-center gap-2 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors"
                  style="background:var(--olfu-green,#2d6a1f);"
                  onmouseover="this.style.opacity='.85'"
                  onmouseout="this.style.opacity='1'">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Submit Rating
          </button>
          <p class="text-xs text-gray-400 mt-2">Your rating helps us improve our service.</p>
        </div>
      <?php endif; ?>
    </div>

    <script>
    (function () {
      const stars     = document.querySelectorAll('.rating-star-btn');
      const ratingIn  = document.getElementById('ratingValue');
      const ratingLbl = document.getElementById('ratingLabel');
      const submitBtn = document.getElementById('btnSubmitRating');
      const errorEl   = document.getElementById('ratingError');
      const labels    = ['', 'Very Dissatisfied', 'Dissatisfied', 'Neutral', 'Satisfied', 'Very Satisfied'];

      if (!stars.length) return; // already rated — read-only mode

      function paintStars(n) {
        stars.forEach((btn, i) => {
          btn.querySelector('svg').style.color = (i < n) ? '#f59e0b' : '#e5e7eb';
        });
      }

      stars.forEach(btn => {
        btn.addEventListener('click', function () {
          const r = parseInt(this.dataset.rating);
          ratingIn.value = r;
          if (ratingLbl) ratingLbl.textContent = labels[r] || '';
          paintStars(r);
        });
        btn.addEventListener('mouseenter', function () {
          paintStars(parseInt(this.dataset.rating));
        });
      });

      document.getElementById('ratingStars')?.addEventListener('mouseleave', () => {
        paintStars(parseInt(ratingIn.value) || 0);
      });

      if (submitBtn) {
        submitBtn.addEventListener('click', function () {
          const rating   = parseInt(ratingIn.value) || 0;
          const feedback = (document.getElementById('ratingFeedback')?.value || '').trim();

          if (rating < 1 || rating > 5) {
            if (errorEl) { errorEl.textContent = 'Please select a star rating before submitting.'; errorEl.classList.remove('hidden'); }
            return;
          }
          if (errorEl) errorEl.classList.add('hidden');

          submitBtn.disabled = true;
          submitBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Submitting…';

          const fd = new FormData();
          fd.append('wo_id',    '<?= (int)$linked_wo_id ?>');
          fd.append('rating',   rating);
          fd.append('feedback', feedback);

          fetch('<?= BASE_URL ?>modules/tickets/rate_wo.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
              if (data.success) {
                // Replace the card body with a thank-you confirmation
                const card = document.getElementById('ratingCard');
                if (card) {
                  card.innerHTML = `
                    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:#15803d;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                      <span class="font-bold text-gray-900 text-sm">Service Rating</span>
                    </div>
                    <div class="p-5">
                      <p class="text-xs text-gray-500 mb-3">You rated this service:</p>
                      <div class="flex items-center gap-1.5 mb-3">
                        ${[1,2,3,4,5].map(s => `<svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24" style="color:${s<=rating?'#f59e0b':'#e5e7eb'};"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>`).join('')}
                        <span class="text-sm font-semibold text-gray-700 ml-1">${rating} / 5</span>
                      </div>
                      ${feedback ? `<div style="padding:10px 14px;border-radius:8px;background:#f9fafb;border:1px solid #e5e7eb;"><p class="text-xs text-gray-500 mb-1 font-semibold uppercase tracking-wide">Your feedback</p><p class="text-sm text-gray-700">${feedback.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</p></div>` : ''}
                      <p class="text-xs text-gray-400 mt-3 italic">Thank you for your feedback.</p>
                    </div>`;
                }
              } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Submit Rating';
                if (errorEl) { errorEl.textContent = data.message || 'Failed to submit rating. Please try again.'; errorEl.classList.remove('hidden'); }
              }
            })
            .catch(() => {
              submitBtn.disabled = false;
              submitBtn.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Submit Rating';
              if (errorEl) { errorEl.textContent = 'Network error. Please try again.'; errorEl.classList.remove('hidden'); }
            });
        });
      }
    })();
    </script>
    <?php endif; ?>

  </div>

  <!-- Sidebar Column -->
  <div class="space-y-6">
  


    <?php if ($ticket_sla): ?>
    <!-- SLA Countdown Widget -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" id="sla-widget">
      <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
        <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
          <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          SLA Tracker
        </h3>
        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider bg-gray-100 px-2 py-0.5 rounded"><?= htmlspecialchars($ticket_sla['policy_name']) ?></span>
      </div>
      <div class="p-4 space-y-3">

        <?php if ($ticket_sla['paused_at']): ?>
          <div class="flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold px-3 py-2 rounded-lg">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            SLA Clock Paused
            <?php if ($ticket_sla['pause_reason']): ?>
              — <?= htmlspecialchars(ucwords(str_replace('_', ' ', $ticket_sla['pause_reason']))) ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php
        // Helper: compute remaining time or breach status for a stage
        $sla_stages = [
            ['label' => 'Response',  'due' => $ticket_sla['response_due'],  'actual' => $ticket_sla['responded_at'],  'breached' => $ticket_sla['is_response_breached']],
            ['label' => 'Diagnosis', 'due' => $ticket_sla['diagnosis_due'], 'actual' => $ticket_sla['diagnosed_at'],  'breached' => $ticket_sla['is_diagnosis_breached']],
            ['label' => 'Resolution','due' => $ticket_sla['resolution_due'],'actual' => $ticket_sla['resolved_at'],   'breached' => $ticket_sla['is_resolution_breached']],
        ];
        ?>

        <?php foreach ($sla_stages as $stage): ?>
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider"><?= $stage['label'] ?></p>
            <?php if ($stage['actual']): ?>
              <p class="text-[10px] text-gray-400 mt-0.5">Completed <?= date('M j, g:i A', strtotime($stage['actual'])) ?></p>
            <?php elseif ($stage['due']): ?>
              <p class="text-[10px] text-gray-400 mt-0.5">Due <?= date('M j, g:i A', strtotime($stage['due'])) ?></p>
            <?php endif; ?>
          </div>
          <div class="text-right">
            <?php if ($stage['actual']): ?>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800 border border-green-200">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                Met
              </span>
            <?php elseif ($stage['breached']): ?>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800 border border-red-200 animate-pulse">
                ⚠ Breached
              </span>
            <?php elseif ($stage['due']): ?>
              <span class="sla-countdown text-xs font-mono font-bold text-gray-700" data-due="<?= htmlspecialchars($stage['due']) ?>" data-paused="<?= $ticket_sla['paused_at'] ? '1' : '0' ?>">
                --:--:--
              </span>
            <?php else: ?>
              <span class="text-[10px] text-gray-300 italic">N/A</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if ($ticket_sla['total_paused_minutes'] > 0): ?>
        <div class="pt-2 mt-2 border-t border-gray-100 flex items-center justify-between text-[10px] text-gray-400">
          <span>Total paused time</span>
          <span class="font-mono font-bold"><?php
            $pm = (int)$ticket_sla['total_paused_minutes'];
            echo ($pm >= 60 ? floor($pm/60).'h ' : '') . ($pm % 60) . 'm';
          ?></span>
        </div>
        <?php endif; ?>

      </div>
    </div>
    <script>
    // Live SLA countdown timer
    (function() {
      function updateCountdowns() {
        document.querySelectorAll('.sla-countdown').forEach(el => {
          if (el.dataset.paused === '1') {
            el.textContent = 'PAUSED';
            el.classList.add('text-amber-600');
            return;
          }
          const due = new Date(el.dataset.due.replace(' ', 'T'));
          const now = new Date();
          let diff = due - now;
          const isNegative = diff < 0;
          if (isNegative) diff = Math.abs(diff);

          const h = Math.floor(diff / 3600000);
          const m = Math.floor((diff % 3600000) / 60000);
          const s = Math.floor((diff % 60000) / 1000);
          const pad = n => String(n).padStart(2, '0');

          if (isNegative) {
            el.textContent = `00:00:00`;
            el.classList.remove('text-gray-700', 'text-amber-600');
            el.classList.add('text-red-600', 'animate-pulse');
          } else if (h === 0 && m < 30) {
            el.textContent = `${pad(h)}:${pad(m)}:${pad(s)}`;
            el.classList.remove('text-gray-700', 'text-red-600');
            el.classList.add('text-amber-600');
          } else {
            el.textContent = `${pad(h)}:${pad(m)}:${pad(s)}`;
            el.classList.remove('text-red-600', 'text-amber-600', 'animate-pulse');
            el.classList.add('text-gray-700');
          }
        });
      }
      updateCountdowns();
      setInterval(updateCountdowns, 1000);
    })();
    </script>
    <?php endif; ?>

    <!-- Details Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-900 bg-gray-50 text-sm">
        Details
      </div>
      <div class="p-5">
        <dl class="space-y-3 text-sm">
          <div>
            <dt class="text-gray-500 mb-0.5">Priority</dt>
            <dd><?= ticket_priority_badge($ticket['priority']) ?></dd>
          </div>
          <div>
            <dt class="text-gray-500 mb-0.5">Urgency / Impact</dt>
            <dd class="font-medium text-gray-900 capitalize"><?= $ticket['urgency'] ?> / <?= $ticket['impact'] ?></dd>
          </div>
          <div>
            <dt class="text-gray-500 mb-0.5">Category</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($ticket['category_name'] ?: 'None specified') ?></dd>
          </div>
          <div>
            <dt class="text-gray-500 mb-0.5">Location</dt>
            <dd class="font-medium text-gray-900">
              <?php if($ticket['location_id']): ?>
                <?= htmlspecialchars($ticket['building'] . ' - ' . $ticket['floor'] . ' - ' . $ticket['room']) ?>
              <?php else: ?>
                Pending
              <?php endif; ?>
            </dd>
          </div>
          <div>
            <dt class="text-gray-500 mb-0.5">Submitted</dt>
            <dd class="font-medium text-gray-900"><?= date('M j, Y h:i A', strtotime($ticket['created_at'])) ?> (via <?= ucfirst($ticket['channel']) ?>)</dd>
          </div>
        </dl>
      </div>
    </div>
    
    <!-- Requester Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-900 bg-gray-50 text-sm">
        Requester
      </div>
      <div class="p-5 space-y-2 text-sm">
        <div class="font-medium text-gray-900"><?= htmlspecialchars($ticket['full_name'] ?? '') ?></div>
        <div class="text-gray-600 flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <a href="mailto:<?= htmlspecialchars($ticket['email'] ?? '') ?>" class="hover:underline"><?= htmlspecialchars($ticket['email'] ?? '') ?></a>
        </div>
        <?php if (!empty($ticket['contact_number'])): ?>
        <div class="text-gray-600 flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          <?= htmlspecialchars($ticket['contact_number']) ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($ticket['department'])): ?>
          <div class="text-gray-600"><?= htmlspecialchars($ticket['department']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Asset / Equipment Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-900 bg-gray-50 text-sm">
        Equipment Identification
      </div>
      <div class="p-5 space-y-3 text-sm">
        <?php if (!empty($ticket['asset_tag'])): ?>
          <div>
            <dt class="text-gray-500 mb-0.5">Reported Asset ID</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($ticket['asset_tag']) ?></dd>
          </div>
        <?php endif; ?>

        <?php if ($ticket['asset_id']): ?>
          <div>
            <span class="text-xs font-mono font-bold text-olfu-green bg-green-50 px-2 py-0.5 rounded border border-green-200"><?= htmlspecialchars($ticket['asset_tag_linked'] ?? $ticket['asset_tag']) ?></span>
          </div>
          <div>
            <dt class="text-gray-500 mb-0.5">Linked Asset</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($ticket['manufacturer'] . ' ' . $ticket['asset_model']) ?></dd>
          </div>
        <?php endif; ?>

        <?php if (!empty($ticket['model'])): ?>
          <div>
            <dt class="text-gray-500 mb-0.5">Reported Model</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($ticket['model']) ?></dd>
          </div>
        <?php endif; ?>

        <?php if (!empty($ticket['warranty_status'])): ?>
          <div>
            <dt class="text-gray-500 mb-0.5">Warranty Status</dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($ticket['warranty_status']) ?></dd>
          </div>
        <?php endif; ?>

        <?php if (!empty($ticket['asset_id'])): ?>
          <div>
            <a href="<?= BASE_URL ?>modules/assets/view.php?id=<?= $ticket['asset_id'] ?>" class="text-olfu-green hover:underline font-semibold block mt-1">View Full Asset →</a>
          </div>
        <?php endif; ?>

        <?php if (empty($ticket['asset_id']) && empty($ticket['model']) && empty($ticket['warranty_status'])): ?>
          <div class="text-gray-400 italic">No specific asset or model identified.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Dynamic Fields -->
    <?php if (!empty($dynamic_fields)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-900 bg-gray-50 text-sm">
        Additional Data
      </div>
      <div class="p-5">
        <dl class="space-y-3 text-sm">
          <?php foreach ($dynamic_fields as $k => $v): ?>
          <div>
            <dt class="text-gray-500 mb-0.5"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $k))) ?></dt>
            <dd class="font-medium text-gray-900"><?= htmlspecialchars($v) ?></dd>
          </div>
          <?php endforeach; ?>
        </dl>
      </div>
    </div>
    <?php endif; ?>
    
    <!-- Related Work Orders -->
    <?php if (!empty($related_wos)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
      <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-900 bg-gray-50 text-sm">
        Related Work Orders
      </div>
      <div class="p-4 space-y-3">
        <?php foreach ($related_wos as $wo): ?>
          <a href="<?= BASE_URL ?>modules/workorders/view.php?id=<?= $wo['wo_id'] ?>" class="block p-3 rounded-lg border border-gray-100 hover:border-olfu-green hover:shadow-sm transition group">
            <div class="flex justify-between items-center mb-1">
              <span class="font-mono font-bold text-xs text-olfu-green group-hover:underline"><?= htmlspecialchars($wo['wo_number']) ?></span>
              <span class="text-[10px] uppercase font-bold text-gray-500"><?= htmlspecialchars($wo['status']) ?></span>
            </div>
            <div class="text-xs text-gray-600">
              <?= $wo['scheduled_start'] ? 'Scheduled: ' . date('M j', strtotime($wo['scheduled_start'])) : 'Unscheduled' ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>
