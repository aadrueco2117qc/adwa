<!-- Back + breadcrumb row -->
<div class="flex items-center gap-2 mb-4">
  <a href="index.php"
     class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    Back to Work Orders
  </a>
  <span class="text-gray-300">/</span>
  <span class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($wo['wo_number']) ?></span>
</div>

<?php if ($is_overdue): ?>
<div class="wo-banner banner-warn mb-4">
  <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
  </svg>
  <span>This work order is <strong>overdue</strong> — scheduled end was <?= (new DateTime($wo['scheduled_end']))->format('M j, Y g:ia') ?>.</span>
</div>
<?php endif; ?>

<?php if ($wo['status'] === 'on_hold' && $wo['on_hold_reason']): ?>
<div class="wo-banner banner-info mb-4">
  <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
  </svg>
  <span>Work order is <strong>on hold</strong> — <?= wo_hold_reason($wo['on_hold_reason']) ?></span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_260px] gap-4 items-start">

  <!-- LEFT COLUMN -->
  <div class="flex flex-col gap-4">

    <!-- WO info card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
            <span class="block w-0.5 h-4 bg-olfu-green rounded"></span>
            Work Order Information
          </h2>
          <p class="text-xs text-gray-400 mt-0.5">
            Viewing <?= htmlspecialchars($wo['wo_number']) ?> — <?= wo_type_badge($wo['wo_type']) ?>
          </p>
        </div>
        <div class="flex gap-2 flex-wrap">
          <a href="edit.php?id=<?= $id ?>"
             class="inline-flex items-center gap-1.5 bg-amber-50 border border-amber-300 text-amber-700 hover:bg-amber-100 text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit
          </a>
          <?php if ($wo['status'] === 'resolved'): ?>
          <button type="button"
                  id="btnCloseAndNotify"
                  onclick="closeAndNotify(<?= (int)$wo['wo_id'] ?>)"
                  class="inline-flex items-center gap-1.5 text-white text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors"
                  style="background:var(--olfu-green,#2d6a1f);"
                  onmouseover="this.style.opacity='.85'"
                  onmouseout="this.style.opacity='1'">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Close &amp; Pass to Requester
          </button>
          <?php endif; ?>
        </div>
      </div>

      <div class="p-6">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
          <div>
            <div class="vf-lbl">WO Number</div>
            <div class="vf-val vf-mono"><?= htmlspecialchars($wo['wo_number']) ?></div>
          </div>
          <div>
            <div class="vf-lbl">Type</div>
            <div class="vf-val"><?= wo_type_badge($wo['wo_type']) ?></div>
          </div>
          <div>
            <div class="vf-lbl">Status</div>
            <div class="vf-val"><?= wo_status_badge($wo['status']) ?></div>
          </div>
          <div>
            <div class="vf-lbl">Linked Ticket</div>
            <?php if ($wo['ticket_number']): ?>
              <div class="vf-val vf-mono"><?= htmlspecialchars($wo['ticket_number']) ?></div>
            <?php else: ?>
              <div class="text-red-400 text-xs font-bold italic">Unlinked — Ticket Required</div>
            <?php endif; ?>
          </div>
          <div>
            <div class="vf-lbl">Priority</div>
            <div class="vf-val"><?= wo_priority_badge($wo['priority'] ?? null) ?></div>
          </div>
          <div>
            <div class="vf-lbl">RMA</div>
            <div class="vf-val"><?= $wo['is_rma'] ? '<span class="wo-badge badge-type-diagnosis">Yes — RMA</span>' : '<span class="text-gray-400">No</span>' ?></div>
          </div>

          <!-- Asset info -->
          <?php if ($wo['asset_tag']): ?>
          <div class="col-span-2 md:col-span-3">
            <div class="sdiv">Linked Asset</div>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <div class="vf-lbl">Asset Tag</div>
                <div class="vf-val vf-mono"><?= htmlspecialchars($wo['asset_tag']) ?></div>
              </div>
              <div>
                <div class="vf-lbl">Model</div>
                <div class="vf-val"><?= htmlspecialchars(($wo['manufacturer'] ?? '') . ' ' . ($wo['model'] ?? '')) ?></div>
              </div>
              <div>
                <div class="vf-lbl">Category</div>
                <div class="vf-val"><?= htmlspecialchars($wo['category_name'] ?? '—') ?></div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- Location -->
          <?php if ($wo['building']): ?>
          <div class="col-span-2 md:col-span-3">
            <div class="sdiv">Location</div>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <div class="vf-lbl">Building</div>
                <div class="vf-val"><?= htmlspecialchars($wo['building']) ?></div>
              </div>
              <div>
                <div class="vf-lbl">Floor</div>
                <div class="vf-val"><?= htmlspecialchars($wo['floor']) ?></div>
              </div>
              <div>
                <div class="vf-lbl">Room</div>
                <div class="vf-val"><?= htmlspecialchars($wo['room']) ?></div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- Assignment -->
          <div class="col-span-2 md:col-span-3">
            <div class="sdiv">Assignment</div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <div class="vf-lbl">Assigned To</div>
                <?php if ($wo['technician_name']): ?>
                  <div class="vf-val"><?= htmlspecialchars($wo['technician_name']) ?></div>
                <?php else: ?>
                  <div class="vf-empty">Unassigned</div>
                <?php endif; ?>
              </div>
              <div>
                <div class="vf-lbl">Assigned By</div>
                <div class="vf-val"><?= $wo['assigned_by_name'] ? htmlspecialchars($wo['assigned_by_name']) : '<span class="vf-empty">—</span>' ?></div>
              </div>
            </div>
          </div>

          <!-- Schedule -->
          <div class="col-span-2 md:col-span-3">
            <div class="sdiv">Schedule</div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div>
                <div class="vf-lbl">Scheduled Start</div>
                <div class="vf-val"><?= $wo['scheduled_start'] ? (new DateTime($wo['scheduled_start']))->format('M j, Y g:ia') : '<span class="vf-empty">Not set</span>' ?></div>
              </div>
              <div>
                <div class="vf-lbl">Scheduled End</div>
                <div class="vf-val <?= $is_overdue ? 'text-red-600' : '' ?>"><?= $wo['scheduled_end'] ? (new DateTime($wo['scheduled_end']))->format('M j, Y g:ia') : '<span class="vf-empty">Not set</span>' ?></div>
              </div>
              <div>
                <div class="vf-lbl">Actual Start</div>
                <div class="vf-val"><?= $wo['actual_start'] ? (new DateTime($wo['actual_start']))->format('M j, Y g:ia') : '<span class="vf-empty">—</span>' ?></div>
              </div>
              <div>
                <div class="vf-lbl">Actual End</div>
                <div class="vf-val"><?= $wo['actual_end'] ? (new DateTime($wo['actual_end']))->format('M j, Y g:ia') : '<span class="vf-empty">—</span>' ?></div>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <?php if ($wo['notes']): ?>
          <div class="col-span-2 md:col-span-3">
            <div class="vf-lbl">Notes</div>
            <div class="vf-val text-gray-600 whitespace-pre-line"><?= htmlspecialchars($wo['notes']) ?></div>
          </div>
          <?php endif; ?>

          <?php if ($wo['resolution_notes']): ?>
          <div class="col-span-2 md:col-span-3">
            <div class="vf-lbl">Resolution Notes</div>
            <div class="vf-val text-gray-600 whitespace-pre-line"><?= htmlspecialchars($wo['resolution_notes']) ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Tabs card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="tab-nav">
        <?php
        $tabs = [
          'checklist'   => 'Checklist',
          'parts'       => 'Parts Used (' . count($parts) . ')',
          'timelog'     => 'Time Log',
          'media'       => 'Media (' . count($media) . ')',
          'notes'       => 'Notes (' . count($notes) . ')',
          'signoff'     => 'Sign-off',
          'assignments' => 'Assignment History (' . count($assignments) . ')',
        ];
        foreach ($tabs as $key => $label):
        ?>
        <button class="tab-btn <?= $active_tab === $key ? 'tab-on' : '' ?>"
                onclick="switchTab('<?= $key ?>')">
          <?= htmlspecialchars($label) ?>
        </button>
        <?php endforeach; ?>
      </div>

      <!-- Checklist tab -->
      <div id="tab-checklist" class="p-5 <?= $active_tab !== 'checklist' ? 'hidden' : '' ?>">
        <?php if ($checklist): ?>
          <?php
          $done_count  = count(array_filter($checklist, fn($i) => $i['is_done']));
          $total_items = count($checklist);
          $pct         = $total_items > 0 ? round($done_count / $total_items * 100) : 0;
          ?>
          <div class="flex items-center justify-between mb-4">
            <div>
              <span class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($cl_name) ?></span>
              <span class="text-xs text-gray-400 ml-2"><?= $done_count ?>/<?= $total_items ?> items (<?= $pct ?>%)</span>
            </div>
          </div>
          <div class="cl-progress mb-4">
            <div class="cl-progress-fill" style="width:<?= $pct ?>%"></div>
          </div>
          <div>
            <?php foreach ($checklist as $item): ?>
            <div class="cl-item">
              <div class="cl-check <?= $item['is_done'] ? 'cl-done' : '' ?>">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
              <div class="flex-1">
                <div class="cl-text">
                  <?= htmlspecialchars($item['item_text']) ?>
                  <?php if ($item['is_mandatory']): ?><span class="cl-mandatory">*</span><?php endif; ?>
                  <?php if ($item['requires_photo']): ?>
                    <svg class="inline w-3.5 h-3.5 text-gray-400 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
                    </svg>
                  <?php endif; ?>
                </div>
                <?php if ($item['is_done']): ?>
                  <div class="cl-meta">
                    Completed by <?= htmlspecialchars($item['completed_by_name'] ?? 'Unknown') ?>
                    · <?= $item['completed_at'] ? (new DateTime($item['completed_at']))->format('M j, g:ia') : '' ?>
                  </div>
                  <?php if ($item['completion_notes']): ?>
                    <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($item['completion_notes']) ?></div>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-sm text-gray-400 italic">No checklist available for this work order.</p>
        <?php endif; ?>
      </div>

      <!-- Parts used tab -->
      <div id="tab-parts" class="p-5 <?= $active_tab !== 'parts' ? 'hidden' : '' ?>">
        <?php if ($parts): ?>
          <table class="w-full border-collapse text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Part</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Part #</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Qty</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Serial</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Warranty</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Used By</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($parts as $p): ?>
              <tr class="border-b border-gray-50">
                <td class="py-2 px-3 font-medium text-gray-700"><?= htmlspecialchars($p['part_name']) ?></td>
                <td class="py-2 px-3 wo-tag"><?= htmlspecialchars($p['part_number']) ?></td>
                <td class="py-2 px-3 text-gray-600"><?= $p['quantity_used'] ?></td>
                <td class="py-2 px-3 text-gray-500 text-xs font-mono"><?= $p['serial_number'] ? htmlspecialchars($p['serial_number']) : '—' ?></td>
                <td class="py-2 px-3"><?= $p['is_warranty'] ? '<span class="wo-badge badge-type-diagnosis">RMA</span>' : '<span class="text-gray-300">—</span>' ?></td>
                <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars($p['used_by_name'] ?? '—') ?></td>
                <td class="py-2 px-3 text-gray-400 text-xs"><?= (new DateTime($p['used_at']))->format('M j, Y') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-sm text-gray-400 italic">No parts consumed on this work order.</p>
        <?php endif; ?>
      </div>

      <!-- Time log tab -->
      <div id="tab-timelog" class="p-5 <?= $active_tab !== 'timelog' ? 'hidden' : '' ?>">
        <?php if ($time_logs): ?>
          <div class="flex items-center gap-3 mb-4 p-3 bg-gray-50 rounded-lg">
            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
              <span class="text-sm font-semibold text-gray-700">Total Logged Time:</span>
              <span class="text-sm font-bold text-olfu-green ml-1"><?= format_duration($total_time) ?></span>
            </div>
          </div>
          <div class="tl-wrap">
            <?php foreach ($time_logs as $tl): ?>
            <div class="tl-entry">
              <div class="tl-dot tl-<?= $tl['action'] ?>"></div>
              <div>
                <span class="tl-action text-<?= match($tl['action']) { 'start'=>'green-600', 'pause'=>'amber-600', 'resume'=>'blue-600', 'stop'=>'red-600', default=>'gray-600' } ?>">
                  <?= ucfirst($tl['action']) ?>
                </span>
                <?php if ($tl['labor_type']): ?>
                  <span class="wo-badge badge-type ml-2"><?= ucfirst(str_replace('_',' ',$tl['labor_type'])) ?></span>
                <?php endif; ?>
                <span class="tl-time ml-2"><?= (new DateTime($tl['logged_at']))->format('M j, g:ia') ?> · <?= htmlspecialchars($tl['technician_name'] ?? 'Unknown') ?></span>
                <?php if ($tl['notes']): ?>
                  <div class="tl-notes"><?= htmlspecialchars($tl['notes']) ?></div>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-sm text-gray-400 italic">No time entries recorded yet. Time tracking is managed by technicians in Module 4.</p>
        <?php endif; ?>
      </div>

      <!-- Media tab -->
      <div id="tab-media" class="p-5 <?= $active_tab !== 'media' ? 'hidden' : '' ?>">
        <?php if ($media): ?>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <?php foreach ($media as $m): ?>
            <div class="media-card">
              <?php
                // file_path is already a full URL (stored by sync.php as BASE_URL + path).
                // file_type is a MIME type like 'image/jpeg', not an extension.
                $mediaUrl = $m['file_path'];
                // If it looks like a relative path (no scheme), prepend BASE_URL once.
                if (!preg_match('#^https?://#i', $mediaUrl) && !str_starts_with($mediaUrl, '/')) {
                    $mediaUrl = BASE_URL . $mediaUrl;
                }
                $isImage = str_starts_with($m['file_type'], 'image/') ||
                           in_array(strtolower(pathinfo($m['file_path'], PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif','webp']);
              ?>
              <?php if ($isImage): ?>
                <img src="<?= htmlspecialchars($mediaUrl) ?>" alt="<?= htmlspecialchars($m['caption'] ?? '') ?>" class="media-thumb" />
              <?php else: ?>
                <div class="media-thumb flex items-center justify-center text-gray-300">
                  <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                  </svg>
                </div>
              <?php endif; ?>
              <div class="media-info">
                <span class="media-type media-<?= $m['media_type'] ?>"><?= ucfirst($m['media_type']) ?></span>
                <?php if ($m['caption']): ?>
                  <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($m['caption']) ?></p>
                <?php endif; ?>
                <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($m['uploaded_by_name'] ?? '—') ?> · <?= (new DateTime($m['uploaded_at']))->format('M j') ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-sm text-gray-400 italic">No media captured yet. Photos and evidence are uploaded by technicians during work order execution.</p>
        <?php endif; ?>
      </div>

      <!-- Notes tab -->
      <div id="tab-notes" class="p-5 <?= $active_tab !== 'notes' ? 'hidden' : '' ?>">
        <?php if ($notes): ?>
          <div class="space-y-3">
            <?php
            $note_type_labels = [
              'diagnosis' => 'Diagnosis',
              'repair'    => 'Repair',
              'general'   => 'General',
              'system'    => 'System',
              'voice'     => 'Voice',
            ];
            foreach ($notes as $n):
              $type_label = $note_type_labels[$n['note_type']] ?? ucfirst($n['note_type']);
            ?>
            <div class="border border-gray-100 rounded-lg p-3 bg-gray-50">
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                  <span class="wo-badge badge-type"><?= htmlspecialchars($type_label) ?></span>
                  <?php if ($n['is_voice']): ?>
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-14 0m7 7v3m-4 0h8m-4-7a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                    </svg>
                  <?php endif; ?>
                  <span class="text-xs font-semibold text-gray-700"><?= htmlspecialchars($n['added_by_name'] ?? 'Unknown') ?></span>
                </div>
                <span class="text-xs text-gray-400"><?= (new DateTime($n['added_at']))->format('M j, Y g:ia') ?></span>
              </div>
              <div class="text-sm text-gray-600 whitespace-pre-line"><?= htmlspecialchars($n['note_text']) ?></div>
              <?php if ($n['is_voice'] && $n['voice_path']): ?>
                <audio controls class="mt-2 w-full">
                  <source src="<?= htmlspecialchars(BASE_URL . ltrim($n['voice_path'], '/')) ?>" type="audio/mpeg" />
                </audio>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-sm text-gray-400 italic">No notes recorded yet. Notes are added by technicians during work order execution.</p>
        <?php endif; ?>
      </div>

      <!-- Sign-off tab -->
      <div id="tab-signoff" class="p-5 <?= $active_tab !== 'signoff' ? 'hidden' : '' ?>">
        <?php if ($signoff): ?>
          <div class="signoff-card">
            <div class="grid grid-cols-2 gap-4 mb-4">
              <div>
                <div class="vf-lbl">Signed By</div>
                <div class="vf-val"><?= htmlspecialchars($signoff['signer_name']) ?></div>
              </div>
              <div>
                <div class="vf-lbl">Signed At</div>
                <div class="vf-val"><?= (new DateTime($signoff['signed_at']))->format('M j, Y g:ia') ?></div>
              </div>
            </div>
            <?php
            // signature_path may be an absolute web path (starts with /), a full URL, or a relative path.
            $sig_path = trim((string)($signoff['signature_path'] ?? ''));
            $sig_url = '';
            if ($sig_path !== '' && $sig_path !== 'data:inline') {
                if (preg_match('#^(https?:)?//#', $sig_path) || str_starts_with($sig_path, 'data:')) {
                    $sig_url = $sig_path;
                } elseif (str_starts_with($sig_path, '/')) {
                    $sig_url = $sig_path;
                } else {
                    $sig_url = BASE_URL . ltrim($sig_path, '/');
                }
            }
            ?>
            <?php if ($sig_url): ?>
            <div class="mb-3">
              <div class="vf-lbl">Signature</div>
              <div class="mt-2 inline-block bg-white border border-gray-200 rounded-lg p-3">
                <img src="<?= htmlspecialchars($sig_url) ?>" alt="Signature of <?= htmlspecialchars($signoff['signer_name']) ?>"
                     class="block max-h-40" style="max-width:320px" />
              </div>
            </div>
            <?php endif; ?>
            <?php if ($signoff['satisfaction'] !== null): ?>
            <div class="mb-3">
              <div class="vf-lbl">Satisfaction</div>
              <div class="flex gap-1 mt-1">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <span class="star <?= $s <= $signoff['satisfaction'] ? 'star-on' : '' ?>">★</span>
                <?php endfor; ?>
              </div>
            </div>
            <?php endif; ?>
            <?php if ($signoff['feedback']): ?>
            <div>
              <div class="vf-lbl">Feedback</div>
              <div class="text-sm text-gray-600 mt-1 whitespace-pre-line"><?= htmlspecialchars($signoff['feedback']) ?></div>
            </div>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <p class="text-sm text-gray-400 italic">Awaiting requester sign-off. This will be captured by the technician at completion.</p>
        <?php endif; ?>
      </div>

      <!-- Assignment history tab -->
      <div id="tab-assignments" class="p-5 <?= $active_tab !== 'assignments' ? 'hidden' : '' ?>">
        <?php if ($assignments): ?>
          <table class="w-full border-collapse text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">From</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">To</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Assigned By</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Reason</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($assignments as $a): ?>
              <tr class="border-b border-gray-50">
                <td class="py-2 px-3 text-gray-600"><?= $a['from_name'] ? htmlspecialchars($a['from_name']) : '<span class="text-gray-300 italic">—</span>' ?></td>
                <td class="py-2 px-3 font-medium text-gray-700"><?= htmlspecialchars($a['to_name'] ?? '—') ?></td>
                <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars($a['by_name'] ?? '—') ?></td>
                <td class="py-2 px-3 text-gray-500 text-xs"><?= $a['reason'] ? htmlspecialchars($a['reason']) : '—' ?></td>
                <td class="py-2 px-3 text-gray-400 text-xs"><?= (new DateTime($a['assigned_at']))->format('M j, Y g:ia') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-sm text-gray-400 italic">No assignment changes recorded yet.</p>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /left column -->

  <!-- RIGHT PANEL -->
  <div class="flex flex-col gap-3">

    <!-- Quick info -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="rp-hdr">WO Quick Info</div>
      <div class="px-4 py-2">
        <div class="rp-row">
          <span class="rp-lbl">WO Number</span>
          <span class="rp-val wo-tag"><?= htmlspecialchars($wo['wo_number']) ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Status</span>
          <span><?= wo_status_badge($wo['status']) ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Type</span>
          <span><?= wo_type_badge($wo['wo_type']) ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Assigned To</span>
          <span class="rp-val"><?= $wo['technician_name'] ? htmlspecialchars($wo['technician_name']) : '<span class="text-gray-300 italic text-xs">Unassigned</span>' ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Ticket</span>
          <span class="rp-val <?= $wo['ticket_number'] ? 'wo-tag' : 'text-gray-300 italic text-xs' ?>"><?= $wo['ticket_number'] ? htmlspecialchars($wo['ticket_number']) : 'None' ?></span>
        </div>
        <?php if ($wo['asset_tag']): ?>
        <div class="rp-row">
          <span class="rp-lbl">Asset</span>
          <span class="rp-val wo-tag"><?= htmlspecialchars($wo['asset_tag']) ?></span>
        </div>
        <?php endif; ?>
        <div class="rp-row">
          <span class="rp-lbl">RMA</span>
          <span class="rp-val"><?= $wo['is_rma'] ? 'Yes' : 'No' ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Checklist</span>
          <span class="rp-val">
            <?php if ($checklist):
              $done_c = count(array_filter($checklist, fn($i) => $i['is_done']));
            ?>
              <?= $done_c ?>/<?= count($checklist) ?>
            <?php else: ?>
              <span class="text-gray-300 text-xs">—</span>
            <?php endif; ?>
          </span>
        </div>
      </div>
    </div>

    <!-- Schedule panel -->
    <?php if ($wo['scheduled_start'] || $wo['scheduled_end']): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="rp-hdr">Schedule</div>
      <div class="px-4 py-2">
        <?php if ($wo['scheduled_start']): ?>
        <div class="rp-row">
          <span class="rp-lbl">Start</span>
          <span class="rp-val text-xs"><?= (new DateTime($wo['scheduled_start']))->format('M j, g:ia') ?></span>
        </div>
        <?php endif; ?>
        <?php if ($wo['scheduled_end']): ?>
        <div class="rp-row">
          <span class="rp-lbl">End</span>
          <span class="rp-val text-xs <?= $is_overdue ? 'text-red-600' : '' ?>"><?= (new DateTime($wo['scheduled_end']))->format('M j, g:ia') ?></span>
        </div>
        <?php endif; ?>
        <?php if ($total_time > 0): ?>
        <div class="rp-row">
          <span class="rp-lbl">Logged Time</span>
          <span class="rp-val text-xs"><?= format_duration($total_time) ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Record info -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="rp-hdr">Record Info</div>
      <div class="px-4 py-2">
        <div class="rp-row">
          <span class="rp-lbl">Created By</span>
          <span class="rp-val text-xs"><?= $wo['created_by_name'] ? htmlspecialchars($wo['created_by_name']) : '—' ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Created</span>
          <span class="rp-val text-xs"><?= (new DateTime($wo['created_at']))->format('M j, Y') ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Last Updated</span>
          <span class="rp-val text-xs"><?= wo_time_ago($wo['updated_at']) ?></span>
        </div>
      </div>
    </div>

    <!-- Quick reassign -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="rp-hdr">Quick Reassign</div>
      <div class="px-4 py-3">
        <select id="reassign-tech" class="fsel w-full mb-2">
          <option value="">— Select technician —</option>
          <?php foreach ($technicians as $t): ?>
            <option value="<?= $t['user_id'] ?>" <?= $wo['assigned_to'] == $t['user_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($t['full_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <input type="text" id="reassign-reason" class="fin text-sm mb-2" placeholder="Reason for reassignment…" />
        <button id="reassign-btn" onclick="doReassign()"
                class="w-full bg-olfu-green text-white text-sm font-semibold py-2 rounded-lg hover:bg-olfu-green-md transition-colors disabled:opacity-40 disabled:cursor-not-allowed" disabled>
          Reassign
        </button>
        <p id="reassign-msg" class="text-xs mt-2 hidden"></p>
      </div>
    </div>

    <!-- KB Articles -->
    <?php if ($kb_articles): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="rp-hdr flex items-center gap-2">
        <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </svg>
        Knowledge Base
      </div>
      <div class="px-4 py-2 text-sm divide-y divide-gray-100">
        <?php foreach ($kb_articles as $kb): ?>
        <details class="group py-2">
          <summary class="flex items-center justify-between cursor-pointer font-medium text-gray-700 hover:text-olfu-green">
            <span><?= htmlspecialchars($kb['title']) ?></span>
            <span class="transition group-open:rotate-180">
              <svg fill="none" height="16" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="16"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
          </summary>
          <div class="text-gray-600 mt-2 text-xs whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($kb['content']) ?></div>
        </details>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /right panel -->

</div><!-- /grid -->

<script>
function switchTab(key) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('tab-on'));
  document.querySelectorAll('[id^="tab-"]').forEach(p => p.classList.add('hidden'));
  event.currentTarget.classList.add('tab-on');
  const panel = document.getElementById('tab-' + key);
  if (panel) panel.classList.remove('hidden');
}

// Quick reassign
const reassignTech   = document.getElementById('reassign-tech');
const reassignReason = document.getElementById('reassign-reason');
const reassignBtn    = document.getElementById('reassign-btn');
const reassignMsg    = document.getElementById('reassign-msg');

reassignTech.addEventListener('change', () => {
  reassignBtn.disabled = !reassignTech.value;
});

function doReassign() {
  const tech   = reassignTech.value;
  const reason = reassignReason.value.trim();
  if (!tech) return;

  reassignBtn.disabled    = true;
  reassignBtn.textContent = 'Saving…';
  reassignMsg.classList.add('hidden');

  const fd = new FormData();
  fd.append('wo_id',       <?= $id ?>);
  fd.append('assigned_to', tech);
  fd.append('reason',      reason);

  fetch('assign.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      reassignBtn.textContent = 'Reassign';
      if (data.success) {
        reassignMsg.className = 'text-xs mt-2 text-green-600';
        reassignMsg.textContent = 'Technician reassigned successfully.';
        reassignMsg.classList.remove('hidden');
        setTimeout(() => location.reload(), 1200);
      } else {
        reassignMsg.className = 'text-xs mt-2 text-red-600';
        reassignMsg.textContent = data.message || 'Reassignment failed.';
        reassignMsg.classList.remove('hidden');
        reassignBtn.disabled = false;
      }
    })
    .catch(() => {
      reassignBtn.textContent = 'Reassign';
      reassignBtn.disabled = false;
      reassignMsg.className = 'text-xs mt-2 text-red-600';
      reassignMsg.textContent = 'Network error. Please try again.';
      reassignMsg.classList.remove('hidden');
    });
}

function closeAndNotify(woId) {
  const btn = document.getElementById('btnCloseAndNotify');
  if (!btn) return;

  if (!confirm('Close this work order and notify the requester to rate the service?\n\nThis action cannot be undone.')) return;

  btn.disabled = true;
  btn.innerHTML = '<svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Closing…';

  const fd = new FormData();
  fd.append('wo_id', woId);

  fetch('<?= BASE_URL ?>modules/workorders/close_and_notify.php', {
    method: 'POST',
    body: fd,
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      // Update the status badge in the header without a full reload
      const badge = document.querySelector('.wo-badge.badge-resolved');
      if (badge) {
        badge.className = 'wo-badge badge-closed';
        badge.innerHTML = '<span class="bdot"></span>Closed';
      }
      btn.remove(); // Remove the button — action is done
      // Show a success banner
      const banner = document.createElement('div');
      banner.style.cssText = 'display:flex;align-items:center;gap:12px;padding:14px 18px;margin-bottom:16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;';
      banner.innerHTML = '<svg style="width:18px;height:18px;color:#15803d;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>'
        + '<p style="font-size:13px;color:#14532d;margin:0;"><strong>Work order closed.</strong> The requester has been notified and can now rate the service.</p>';
      const main = document.querySelector('.flex.flex-col.gap-4') || document.body;
      main.prepend(banner);
    } else {
      btn.disabled = false;
      btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Close &amp; Pass to Requester';
      alert(data.message || 'Failed to close work order.');
    }
  })
  .catch(() => {
    btn.disabled = false;
    btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Close &amp; Pass to Requester';
    alert('Network error. Please try again.');
  });
}
</script>
