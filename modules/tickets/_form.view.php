<?php
/**
 * Injected by add.php / edit.php before require.
 *
 * @var array  $t              Ticket row (or default values for a new ticket)
 * @var bool   $is_edit
 * @var bool   $is_staff
 * @var array  $categories
 * @var array  $locations
 * @var array  $assets
 * @var array  $assignables
 * @var array  $kb_articles
 * @var array  $dynamic_fields
 * @var array  $attachments
 */
?>
<div class="mb-4 flex items-center justify-between">
  <div>
    <a href="<?= $is_edit ? "view.php?id={$t['ticket_id']}" : "index.php" ?>" class="text-sm text-olfu-green hover:underline flex items-center gap-1 mb-2">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      Back
    </a>
    <h2 class="text-2xl font-bold text-gray-900 tracking-tight"><?= $is_edit ? 'Edit Ticket ' . htmlspecialchars($t['ticket_number'] ?? '') : 'Submit New Ticket' ?></h2>
  </div>
</div>

<form action="save.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6" id="ticket-form">
  <?php if ($is_edit): ?>
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="ticket_id" value="<?= $t['ticket_id'] ?>">
  <?php else: ?>
    <input type="hidden" name="action" value="create">
  <?php endif; ?>
  
  <input type="hidden" name="requester_id" value="<?= htmlspecialchars($t['requester_id'] ?? $_SESSION['user_id']) ?>">
  <input type="hidden" name="asset_id" id="hidden-asset-id" value="<?= htmlspecialchars($t['asset_id'] ?? '') ?>">

  <!-- Main Column -->
  <div class="lg:col-span-2 space-y-6">
    
    <!-- 1. Requester Information -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 md:p-6">
      <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">1. Requester Information</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-500 mb-1">Full Name</label>
          <input type="text" value="<?= htmlspecialchars($t['full_name'] ?? '') ?>" readonly class="fin w-full bg-gray-50 border-gray-200 text-gray-600 cursor-not-allowed">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-500 mb-1">Contact Email</label>
          <input type="email" value="<?= htmlspecialchars($t['email'] ?? '') ?>" readonly class="fin w-full bg-gray-50 border-gray-200 text-gray-600 cursor-not-allowed">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-500 mb-1">Department</label>
          <input type="text" value="<?= htmlspecialchars($t['department'] ?? '') ?>" readonly class="fin w-full bg-gray-50 border-gray-200 text-gray-600 cursor-not-allowed">
        </div>
      </div>
    </div>

    <!-- 2. Asset Identification -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 md:p-6">
      <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">2. Asset Identification</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
           <label class="block text-sm font-medium text-gray-700 mb-1">Asset ID / Serial Number</label>
           <div class="flex gap-2">
             <input type="text" name="asset_tag" id="asset-tag-input" list="asset-list"
                    value="<?= htmlspecialchars($t['asset_tag_linked'] ?? $t['asset_tag'] ?? '') ?>"
                    placeholder="Type or scan Asset ID" class="fin flex-1">
             <datalist id="asset-list">
               <?php foreach ($assets as $a): ?>
                 <option value="<?= htmlspecialchars($a['asset_tag']) ?>" data-id="<?= $a['asset_id'] ?>" data-model="<?= htmlspecialchars($a['model']) ?>">
                 <?php if ($a['serial_number']): ?>
                    <option value="<?= htmlspecialchars($a['serial_number']) ?>" data-id="<?= $a['asset_id'] ?>" data-model="<?= htmlspecialchars($a['model']) ?>">
                 <?php endif; ?>
               <?php endforeach; ?>
             </datalist>
             <button type="button" onclick="openScanner()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg border border-gray-300 flex items-center gap-2 transition-colors">
               <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 17h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
               <span class="text-xs font-bold">Scan QR</span>
             </button>
           </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
          <select name="category_id" id="category-select" class="fsel w-full" required onchange="checkCategoryOthers(this)">
            <option value="">-- Select Category --</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['category_id'] ?>" data-name="<?= htmlspecialchars($c['category_name']) ?>" data-bulb="<?= $c['has_bulb_hours'] ?>" <?= ($t['category_id'] ?? 0) == $c['category_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['category_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-1">
            Location / Room <span class="text-red-500">*</span>
            <svg onclick="showHelp('Location', 'The exact room where the issue is. This helps us calculate travel time and locate the asset.')" class="w-3.5 h-3.5 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </label>
          <select name="location_id" id="location-select" class="fsel w-full" required>
            <option value="">-- Select Room --</option>
            <?php foreach ($locations as $l): ?>
              <option value="<?= $l['location_id'] ?>" <?= ($t['location_id'] ?? 0) == $l['location_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($l['building'] . ' - ' . $l['room']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div id="others-specify-container" class="md:col-span-2 hidden">
          <label class="block text-sm font-medium text-gray-700 mb-1">Others, please specify <span class="text-red-500">*</span></label>
          <input type="text" name="category_others" id="category-others" class="fin w-full" placeholder="Specify category/issue">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
          <input type="text" name="model" id="input-model" 
                 value="<?= htmlspecialchars($t['asset_model'] ?? $t['model'] ?? '') ?>" 
                 readonly placeholder="Auto-filled from Asset ID" 
                 class="fin w-full bg-gray-50 border-gray-200 text-gray-600 cursor-not-allowed">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Warranty Status</label>
          <input type="text" name="warranty_status" id="input-warranty" 
                 value="<?= htmlspecialchars($t['warranty_status'] ?? '') ?>" 
                 readonly placeholder="Auto-filled from Asset ID" 
                 class="fin w-full bg-gray-50 border-gray-200 text-gray-600 cursor-not-allowed">
        </div>

      </div>
    </div>

    <!-- 3. Request Details -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 md:p-6">
      <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">3. Request Details</h3>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Issue Title <span class="text-red-500">*</span></label>
          <input type="text" name="title" value="<?= htmlspecialchars($t['title'] ?? '') ?>" required 
                 placeholder="Short, descriptive title" class="fin w-full">
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-1">
              Request Type <span class="text-red-500">*</span>
              <svg onclick="showHelp('Request Type', 'Standard Repair: Fix broken gear. \nEvent Support: Live technical assistance for classes/events. \nMaintenance: Non-urgent cleanup/updates.')" class="w-3.5 h-3.5 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </label>
            <select name="request_type" class="fsel w-full" required>
               <option value="repair" <?= ($t['request_type'] ?? '') == 'repair' ? 'selected' : '' ?>>Standard Repair</option>
               <option value="event" <?= ($t['request_type'] ?? '') == 'event' ? 'selected' : '' ?>>Event Support</option>
               <option value="maintenance" <?= ($t['request_type'] ?? '') == 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
               <option value="other" <?= ($t['request_type'] ?? '') == 'other' ? 'selected' : '' ?>>Other</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-1">
              Impact <span class="text-red-500">*</span>
              <svg onclick="showHelp('Impact', 'How many people are affected? \nLow: One person. \nMedium: A whole class. \nHigh: Multiple rooms/Department.')" class="w-3.5 h-3.5 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </label>
            <select name="impact" class="fsel w-full" required>
               <option value="low"      <?= ($t['impact'] ?? '') === 'low'      ? 'selected' : '' ?>>Low</option>
               <option value="medium"   <?= ($t['impact'] ?? '') === 'medium'   ? 'selected' : '' ?>>Medium</option>
               <option value="high"     <?= ($t['impact'] ?? '') === 'high'     ? 'selected' : '' ?>>High</option>
               <option value="critical" <?= ($t['impact'] ?? '') === 'critical' ? 'selected' : '' ?>>Critical</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-1">
              Urgency <span class="text-red-500">*</span>
              <svg onclick="showHelp('Urgency', 'How fast do you need it? \nCritical: Class is happening NOW. \nHigh: Class in < 2 hours. \nMedium: Next day.')" class="w-3.5 h-3.5 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </label>
            <select name="urgency" class="fsel w-full" required>
               <option value="low"      <?= ($t['urgency'] ?? '') === 'low'      ? 'selected' : '' ?>>Low</option>
               <option value="medium"   <?= ($t['urgency'] ?? '') === 'medium'   ? 'selected' : '' ?>>Medium</option>
               <option value="high"     <?= ($t['urgency'] ?? '') === 'high'     ? 'selected' : '' ?>>High</option>
               <option value="critical" <?= ($t['urgency'] ?? '') === 'critical' ? 'selected' : '' ?>>Critical</option>
            </select>
          </div>
        </div>

        <div class="pt-2">
          <div class="flex items-start gap-3 p-3 rounded-lg border border-red-100 bg-red-50">
            <div class="pt-0.5">
              <input type="checkbox" id="is_event_support" name="is_event_support" value="1" <?= ($t['is_event_support'] ?? 0) ? 'checked' : '' ?> class="w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-red-500">
            </div>
            <label for="is_event_support" class="cursor-pointer">
              <span class="block text-sm font-bold text-red-800">Event Support Required</span>
              <span class="block text-xs text-red-600 mt-0.5 font-medium">mark as urgent pre-class or high priority live event support</span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- 4. Detailed Description -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 md:p-6">
      <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">4. Detailed Description</h3>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Issue Description <span class="text-red-500">*</span></label>
        <textarea name="description" rows="6" required 
                  placeholder="Provide detailed information about the issue you are experiencing..." 
                  class="fin w-full"><?= htmlspecialchars($t['description'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- 5. Helpful Resources (Suggested Articles) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 md:p-6">
      <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100">
        <div class="flex items-center gap-2">
          <div class="w-2 h-6 bg-olfu-green rounded-full"></div>
          <h3 class="text-base font-bold text-gray-900">5. HELPFUL RESOURCES</h3>
        </div>
      </div>
      
      <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
        <div class="flex items-center gap-2 mb-3">
          <svg class="w-5 h-5 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
          <span class="text-sm font-bold text-gray-800">Suggested Troubleshooting Steps</span>
        </div>
        
        <p class="text-sm text-gray-600 mb-4">Review these articles; they might resolve your issue immediately:</p>
        
        <div class="space-y-3" id="kb-suggestions-container">
          <?php if (!empty($kb_articles)): foreach ($kb_articles as $article): ?>
            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm hover:border-olfu-green transition-all cursor-pointer group kb-article-card relative"
                 onclick='openKbModal(<?= htmlspecialchars(json_encode($article["title"]), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($article["content"]), ENT_QUOTES) ?>)'>
              <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] font-bold text-olfu-green uppercase tracking-wider bg-green-50 px-2 py-0.5 rounded">Article</span>
                <span class="text-[10px] text-olfu-green font-bold opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1">
                  Read Full Article <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </span>
              </div>
              <h4 class="text-sm font-bold text-gray-900 group-hover:text-olfu-green transition-colors"><?= htmlspecialchars($article['title']) ?></h4>
              <p class="text-xs text-gray-500 mt-1 line-clamp-3 leading-relaxed"><?= htmlspecialchars(strip_tags($article['content'])) ?></p>
            </div>
          <?php endforeach; else: ?>
            <p class="text-xs text-gray-400 italic">Select a category to see specific recommendations.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- 6. Supporting Media -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 md:p-6">
      <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">6. Supporting Media</h3>
      
      <div class="space-y-4">
        <label class="block text-sm font-medium text-gray-700">Upload Attachments (Photo/Video)</label>
        <div id="drop-zone" class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:bg-gray-50 transition-all cursor-pointer group">
          <svg class="mx-auto h-10 w-10 text-gray-400 group-hover:text-olfu-green transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
          <div class="mt-2 text-sm text-gray-600">
            <span class="font-medium text-olfu-green">Upload files</span> or drag and drop
            <input type="file" id="file-input" name="attachments[]" multiple class="sr-only" accept="image/*,video/*,.pdf">
          </div>
          <p class="text-xs text-gray-400 mt-1">PNG, JPG, MP4, PDF up to 10MB</p>
        </div>

        <div id="attachments-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
          <?php if ($is_edit && !empty($attachments)): foreach ($attachments as $att): ?>
            <div class="relative group aspect-square rounded-lg border border-gray-200 overflow-hidden bg-gray-50 attachment-item" data-att-id="<?= $att['attachment_id'] ?>">
              <?php if (in_array($att['file_type'], ['jpg','jpeg','png','webp'])): ?>
                <img src="<?= BASE_URL . htmlspecialchars($att['file_path']) ?>" class="w-full h-full object-cover">
              <?php else: ?>
                <div class="w-full h-full flex flex-col items-center justify-center p-2 text-center text-gray-400">
                  <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-width="2"/></svg>
                </div>
              <?php endif; ?>
              <button type="button" onclick="removeExistingAttachment(<?= $att['attachment_id'] ?>, this)" class="absolute top-1 right-1 w-6 h-6 bg-red-600/90 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
              <div class="absolute inset-0 bg-red-500/10 hidden deleted-overlay items-center justify-center">
                <span class="bg-red-600 text-[10px] text-white font-bold px-1.5 py-0.5 rounded shadow">DELETED</span>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
        <div id="deleted-attachments-ids"></div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Time Window (Date)</label>
          <input type="datetime-local" name="preferred_window" value="<?= ($t['preferred_window'] ?? '') ? date('Y-m-d\TH:i', strtotime($t['preferred_window'])) : '' ?>" class="fin w-full">
        </div>
      </div>
    </div>
    
  </div>

  <!-- Sidebar -->
  <div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sticky top-6">
      <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-100">Submission</h3>

      <?php if ($is_edit && $is_staff): ?>
      <div class="space-y-4 mb-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
        <h4 class="text-xs font-bold text-blue-800 uppercase tracking-wider">Staff Options</h4>
        <div>
          <label class="block text-xs font-bold text-blue-700 mb-1">Status</label>
          <select name="status" id="status-select" class="fsel w-full border-blue-200" onchange="toggleOnHoldReason(this)">
            <?php foreach (['new'=>'New','assigned'=>'Assigned','in_progress'=>'In Progress','on_hold'=>'On Hold','resolved'=>'Resolved','closed'=>'Closed','cancelled'=>'Cancelled'] as $k => $v): ?>
              <option value="<?= $k ?>" <?= ($t['status'] ?? 'new') === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div id="on-hold-reason-wrap" class="<?= ($t['status'] ?? '') === 'on_hold' ? '' : 'hidden' ?>">
          <label class="block text-xs font-bold text-blue-700 mb-1">On-hold Reason</label>
          <select name="on_hold_reason" class="fsel w-full border-blue-200">
            <option value="">— None —</option>
            <?php foreach (['waiting_parts'=>'Waiting on parts','waiting_vendor'=>'Waiting on vendor','waiting_access'=>'Waiting on access','other'=>'Other'] as $k => $v): ?>
              <option value="<?= $k ?>" <?= ($t['on_hold_reason'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-blue-700 mb-1">Assign To</label>
          <select name="assigned_to" class="fsel w-full border-blue-200">
            <option value="">— Unassigned —</option>
            <?php foreach ($assignables as $a): ?>
              <option value="<?= $a['user_id'] ?>" <?= (int)($t['assigned_to'] ?? 0) === (int)$a['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <script>
      function toggleOnHoldReason(sel) {
        const wrap = document.getElementById('on-hold-reason-wrap');
        if (!wrap) return;
        if (sel.value === 'on_hold') wrap.classList.remove('hidden');
        else wrap.classList.add('hidden');
      }
      </script>
      <?php endif; ?>

      <button type="submit" class="w-full bg-olfu-green hover:bg-olfu-green-md text-white font-bold py-3 px-4 rounded-xl shadow-md transition-all text-base flex items-center justify-center gap-2">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= $is_edit ? 'Save Changes' : 'Submit Ticket' ?>
      </button>
    </div>
  </div>
</form>

<!-- KB Article View Modal -->
<div id="kb-modal" class="fixed inset-0 z-[110] hidden flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
      <h3 class="font-bold text-gray-900" id="kb-modal-title">Article Details</h3>
      <button type="button" onclick="closeKbModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="p-8 max-h-[70vh] overflow-y-auto" id="kb-modal-content">
      <!-- Content injected by JS -->
    </div>
    <div class="px-6 py-4 bg-gray-50 flex justify-end">
      <button type="button" onclick="closeKbModal()" class="px-6 py-2 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">Close</button>
    </div>
  </div>
</div>

<!-- Scanner Modal -->
<div id="scanner-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
      <h3 class="font-bold text-gray-900">Scan Asset QR Code</h3>
      <button type="button" onclick="closeScanner()" class="text-gray-400 hover:text-gray-600 transition-colors">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="p-6">
      <div id="qr-reader" class="rounded-xl overflow-hidden bg-gray-100 aspect-square"></div>
      <div id="qr-reader-results" class="mt-4 text-center text-sm text-gray-500 italic">Scanning...</div>
      
      <div class="mt-6 pt-6 border-t border-gray-100">
        <label class="block text-sm font-bold text-gray-700 mb-2">Or Upload QR Image</label>
        <div class="flex items-center gap-3">
          <input type="file" id="qr-file-input" accept="image/*" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-olfu-green hover:file:bg-green-100">
        </div>
      </div>
    </div>
    <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
      <button type="button" onclick="closeScanner()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
    </div>
  </div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
const categorySelect = document.getElementById('category-select');
const assetTagInput   = document.getElementById('asset-tag-input');
const hiddenAssetId  = document.getElementById('hidden-asset-id');
const modelInput     = document.getElementById('input-model');
const warrantyInput  = document.getElementById('input-warranty');
const assetList      = document.getElementById('asset-list');
const qrFileInput    = document.getElementById('qr-file-input');

function checkCategoryOthers(sel) {
  const container = document.getElementById('others-specify-container');
  const opt = sel.options[sel.selectedIndex];
  if (opt && opt.getAttribute('data-name') === 'Others') {
    container.classList.remove('hidden');
    document.getElementById('category-others').setAttribute('required', 'required');
  } else {
    container.classList.add('hidden');
    document.getElementById('category-others').removeAttribute('required');
  }
}

function updateFormBehavior() {
  if (!categorySelect) return;
  const selOpt = categorySelect.options[categorySelect.selectedIndex];
  const kbContainer = document.getElementById('kb-suggestions-container');
  
  // 1. Dynamic Fields Logic
  const container = document.getElementById('dynamic-fields-container');
  if (!selOpt || !selOpt.value) { 
    container.classList.add('hidden'); 
  } else {
    const text = selOpt.text.toLowerCase();
    const hasBulb = selOpt.getAttribute('data-bulb') === '1';
    let showAny = false;
    
    const bDiv = document.getElementById('df-bulb-hours');
    if (hasBulb || text.includes('projector')) { bDiv.classList.remove('hidden'); showAny = true; }
    else { bDiv.classList.add('hidden'); }
    
    const iDiv = document.getElementById('df-input-source');
    if (text.includes('switcher') || text.includes('display') || text.includes('projector')) { iDiv.classList.remove('hidden'); showAny = true; }
    else { iDiv.classList.add('hidden'); }
    
    if (showAny) container.classList.remove('hidden');
    else container.classList.add('hidden');
  }

  // 2. KB Suggestions Logic
  if (kbContainer) {
    const catId = categorySelect.value;
    fetch('kb_ajax.php?category_id=' + catId)
      .then(response => response.text())
      .then(html => {
        kbContainer.innerHTML = html;
      })
      .catch(err => console.error('Error fetching KB suggestions:', err));
  }
}

function openKbModal(title, content) {
  document.getElementById('kb-modal-title').innerText = title;
  document.getElementById('kb-modal-content').innerHTML = `
    <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed">
      ${content.replace(/\n/g, '<br>')}
    </div>
  `;
  document.getElementById('kb-modal').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeKbModal() {
  document.getElementById('kb-modal').classList.add('hidden');
  document.body.style.overflow = '';
}

assetTagInput.addEventListener('change', function() {
  const val = this.value.trim();
  const catSel = document.getElementById('category-select');
  const locSel = document.querySelector('select[name="location_id"]');

  if (!val) {
    modelInput.value = '';
    warrantyInput.value = '';
    hiddenAssetId.value = '';
    
    // Make selects editable again
    if (catSel) {
        catSel.classList.remove('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
        catSel.style.pointerEvents = '';
    }
    if (locSel) {
        locSel.classList.remove('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
        locSel.style.pointerEvents = '';
    }
    return;
  }
  
  // Try to find in datalist first for immediate feedback
  const opts = assetList.options;
  let foundLocal = false;
  for (let i = 0; i < opts.length; i++) {
    if (opts[i].value === val) {
      hiddenAssetId.value = opts[i].dataset.id;
      modelInput.value = opts[i].dataset.model || '';
      foundLocal = true;
      break;
    }
  }

  // Always fetch from server to get full details (like warranty)
  fetch(`asset_lookup.php?asset_tag=${encodeURIComponent(val)}`)
    .then(res => res.json())
    .then(data => {
      if (data.success || data.asset_id) {
        const d = data.success ? data : data; // Handle both wrapper or direct
        hiddenAssetId.value = d.asset_id;
        modelInput.value = d.model;
        warrantyInput.value = d.warranty_status;
        
        // Auto-select category and location if available
        if (d.category_id && catSel) {
            catSel.value = d.category_id;
            catSel.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
            catSel.style.pointerEvents = 'none';
            catSel.dispatchEvent(new Event('change'));
        }
        if (d.location_id && locSel) {
            locSel.value = d.location_id;
            locSel.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
            locSel.style.pointerEvents = 'none';
        }
        
        const titleInput = document.querySelector('input[name="title"]');
        if (titleInput) {
            titleInput.focus();
            titleInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      } else if (!foundLocal) {
        modelInput.value = '';
        warrantyInput.value = '';
        hiddenAssetId.value = '';
        if (catSel) {
            catSel.classList.remove('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
            catSel.style.pointerEvents = '';
        }
        if (locSel) {
            locSel.classList.remove('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
            locSel.style.pointerEvents = '';
        }
      }
    })
    .catch(err => console.error('Error fetching asset details:', err));
});

assetTagInput.addEventListener('input', function() {
  const val = this.value.trim();
  const opts = assetList.options;
  const catSel = document.getElementById('category-select');
  const locSel = document.querySelector('select[name="location_id"]');
  
  let found = false;
  for (let i = 0; i < opts.length; i++) {
    if (opts[i].value === val) {
      hiddenAssetId.value = opts[i].dataset.id;
      modelInput.value = opts[i].dataset.model || '';
      found = true;
      break;
    }
  }
  if (!found) {
    hiddenAssetId.value = '';
    if (val === '') {
        modelInput.value = '';
        warrantyInput.value = '';
        if (catSel) {
            catSel.classList.remove('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
            catSel.style.pointerEvents = '';
        }
        if (locSel) {
            locSel.classList.remove('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
            locSel.style.pointerEvents = '';
        }
    }
  }
});

if (categorySelect) {
  categorySelect.addEventListener('change', updateFormBehavior);
  categorySelect.addEventListener('change', function() { checkCategoryOthers(this); });
  // Initialize on load
  checkCategoryOthers(categorySelect);
  
  // Initial lock if asset is already pre-filled (e.g. in Edit mode or from URL)
  if (hiddenAssetId.value) {
      categorySelect.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
      categorySelect.style.pointerEvents = 'none';
      const locSel = document.getElementById('location-select');
      if (locSel) {
          locSel.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
          locSel.style.pointerEvents = 'none';
      }
  }
}
updateFormBehavior();

function onScanSuccess(decodedText) {
    let tag = decodedText;
    // Handle URL format: http://.../view.php?id=123 or ...?asset_tag=TAG
    if (decodedText.includes('?')) {
        const urlParams = new URLSearchParams(decodedText.split('?')[1]);
        tag = urlParams.get('asset_tag') || urlParams.get('id') || decodedText;
    }
    
    assetTagInput.value = tag;
    assetTagInput.dispatchEvent(new Event('change'));
    closeScanner();
}

if (qrFileInput) {
    qrFileInput.addEventListener('change', e => {
        if (e.target.files.length === 0) return;
        const file = e.target.files[0];
        const html5QrCodeFile = new Html5Qrcode("qr-reader");
        html5QrCodeFile.scanFile(file, true)
            .then(decodedText => {
                onScanSuccess(decodedText);
            })
            .catch(err => {
                console.error("Error scanning file", err);
                alert("Could not find a valid QR code in this image.");
            });
    });
}

// --- QR SCANNER ---
let html5QrCode;

function fillAssetFromLookup(data) {
  assetTagInput.value  = data.asset_tag;
  hiddenAssetId.value  = data.asset_id;

  if (data.category_id && categorySelect) {
    categorySelect.value = data.category_id;
    categorySelect.dispatchEvent(new Event('change'));
    categorySelect.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
    categorySelect.style.pointerEvents = 'none';
  }

  const locSelect = document.getElementById('location-select');
  if (data.location_id && locSelect) {
    locSelect.value = data.location_id;
    locSelect.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
    locSelect.style.pointerEvents = 'none';
  }

  if (modelInput)   modelInput.value   = data.model            || '';
  if (warrantyInput) warrantyInput.value = data.warranty_status || '';

  const titleInput = document.querySelector('input[name="title"]');
  if (titleInput) {
      titleInput.focus();
      titleInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

function openScanner() {
  document.getElementById('scanner-modal').classList.remove('hidden');
  document.getElementById('qr-reader-results').textContent = 'Scanning...';

  if (!html5QrCode) {
    html5QrCode = new Html5Qrcode("qr-reader");
  }

  const config = { fps: 10, qrbox: { width: 250, height: 250 } };

  function handleScan(decodedText) {
    closeScanner();

    // Extract asset_id from a URL like .../assets/view.php?id=12
    let assetId = null;
    try {
      const url = new URL(decodedText);
      assetId = url.searchParams.get('id');
    } catch (e) {
      // Not a URL — fall through to raw tag match
    }

    if (assetId) {
      fetch('asset_lookup.php?asset_id=' + encodeURIComponent(assetId))
        .then(r => r.json())
        .then(data => {
          if (data.error) { alert('Asset not found in the system.'); return; }
          fillAssetFromLookup(data);
        })
        .catch(() => alert('Could not look up asset. Please try again.'));
    } else {
      // Raw asset tag scanned — fall back to datalist match
      assetTagInput.value = decodedText;
      assetTagInput.dispatchEvent(new Event('input'));
    }
  }

  html5QrCode.start({ facingMode: "environment" }, config, handleScan, () => {})
    .catch((err) => {
      console.error("Scanner start error:", err);
      document.getElementById('qr-reader-results').innerText = "Camera error: " + err;
      // Fallback: try front camera if rear fails
      html5QrCode.start({ facingMode: "user" }, config, handleScan, () => {})
        .catch(() => {
          alert("Could not access camera. Please ensure permissions are granted.");
          closeScanner();
        });
    });
}

function closeScanner() {
  if (html5QrCode && html5QrCode.isScanning) {
    html5QrCode.stop().then(() => {
        document.getElementById('scanner-modal').classList.add('hidden');
    }).catch(err => {
        console.error("Error stopping scanner:", err);
        document.getElementById('scanner-modal').classList.add('hidden');
    });
  } else {
    document.getElementById('scanner-modal').classList.add('hidden');
  }
}

// --- ATTACHMENT MANAGEMENT ---
let selectedFiles = [];
const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-input');
const attachmentsGrid = document.getElementById('attachments-grid');
const deletedIdsContainer = document.getElementById('deleted-attachments-ids');

if (dropZone && fileInput) {
  dropZone.addEventListener('click', () => fileInput.click());
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => dropZone.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); }, false));
  ['dragenter', 'dragover'].forEach(evt => dropZone.addEventListener(evt, () => dropZone.classList.add('bg-green-50', 'border-olfu-green'), false));
  ['dragleave', 'drop'].forEach(evt => dropZone.addEventListener(evt, () => dropZone.classList.remove('bg-green-50', 'border-olfu-green'), false));
  dropZone.addEventListener('drop', e => handleFiles(e.dataTransfer.files));
  fileInput.addEventListener('change', () => handleFiles(fileInput.files));
}

function handleFiles(files) { selectedFiles = selectedFiles.concat(Array.from(files)); syncFileInput(); renderPreviews(); }
function removeSelectedFile(index) { selectedFiles.splice(index, 1); syncFileInput(); renderPreviews(); }
function removeExistingAttachment(id, btn) {
  const card = btn.closest('.attachment-item');
  const overlay = card.querySelector('.deleted-overlay');
  if (card.classList.contains('marked-deleted')) {
    card.classList.remove('marked-deleted'); overlay.classList.add('hidden');
    const input = document.getElementById('del-att-' + id); if (input) input.remove();
  } else {
    card.classList.add('marked-deleted'); overlay.classList.remove('hidden'); overlay.classList.add('flex');
    const input = document.createElement('input'); input.type = 'hidden'; input.name = 'deleted_attachments[]'; input.value = id; input.id = 'del-att-' + id; deletedIdsContainer.appendChild(input);
  }
}
function syncFileInput() { const dt = new DataTransfer(); selectedFiles.forEach(f => dt.items.add(f)); fileInput.files = dt.files; }
function renderPreviews() {
  document.querySelectorAll('.new-attachment-preview').forEach(el => el.remove());
  selectedFiles.forEach((file, index) => {
    const card = document.createElement('div');
    card.className = 'relative group aspect-square rounded-lg border border-olfu-green bg-green-50/30 overflow-hidden new-attachment-preview';
    card.innerHTML = `<button type="button" onclick="removeSelectedFile(${index})" class="absolute top-1 right-1 w-6 h-6 bg-red-600/90 text-white rounded-full flex items-center justify-center shadow-md z-10"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M6 18L18 6M6 6l12 12"/></svg></button>`;
    if (file.type.startsWith('image/')) {
      const img = document.createElement('img'); img.className = 'w-full h-full object-cover';
      const reader = new FileReader(); reader.onload = e => img.src = e.target.result; reader.readAsDataURL(file);
      card.appendChild(img);
    } else {
      card.innerHTML += `<div class="w-full h-full flex items-center justify-center p-2 text-gray-400"><svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-width="2"/></svg></div>`;
    }
    attachmentsGrid.appendChild(card);
  });
}

function showHelp(title, content) {
  document.getElementById('kb-modal-title').innerText = title;
  document.getElementById('kb-modal-content').innerHTML = `
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded mb-4">
      <p class="text-sm text-blue-700 leading-relaxed whitespace-pre-line">${content}</p>
    </div>
    <p class="text-xs text-gray-500 italic">This information helps our SLA engine calculate the best response time for your request.</p>
  `;
  document.getElementById('kb-modal').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}
</script>
