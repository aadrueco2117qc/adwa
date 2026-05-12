<!-- Back + header -->
<div class="flex items-center gap-2 mb-4">
  <a href="index.php"
     class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    Back to List
  </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4 flex items-center justify-between">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight">Work Order Schedule</h2>
    <p class="text-sm text-gray-400 mt-0.5">Week of <?= $start_date->format('M j') ?> to <?= $end_date->format('M j, Y') ?></p>
  </div>
  <div class="flex gap-2">
    <a href="?week=<?= $week_offset - 1 ?>" class="px-3 py-1.5 border border-gray-200 rounded text-sm hover:bg-gray-50">◀ Prev</a>
    <a href="?week=0" class="px-3 py-1.5 border border-gray-200 rounded text-sm hover:bg-gray-50 <?= $week_offset === 0 ? 'bg-gray-100' : '' ?>">Today</a>
    <a href="?week=<?= $week_offset + 1 ?>" class="px-3 py-1.5 border border-gray-200 rounded text-sm hover:bg-gray-50">Next ▶</a>
  </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col" style="min-width: 800px;">
  <!-- Header row for days -->
  <div class="flex border-b border-gray-100">
    <div class="w-16 flex-shrink-0 bg-gray-50 border-r border-gray-100"></div>
    <?php foreach($days as $d): ?>
    <div class="flex-1 text-center py-3 border-r border-gray-100 last:border-0 <?= $d['is_today'] ? 'bg-green-50' : '' ?>">
      <div class="text-xs uppercase font-bold text-gray-500 tracking-wider"><?= $d['day'] ?></div>
      <div class="text-lg font-semibold <?= $d['is_today'] ? 'text-olfu-green' : 'text-gray-900' ?>"><?= $d['num'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Body -->
  <div class="flex relative" style="height: <?= count($hours) * 4 ?>rem;">
    <!-- Time col -->
    <div class="w-16 flex-shrink-0 flex flex-col border-r border-gray-100 bg-gray-50 absolute left-0 top-0 bottom-0 z-10">
      <?php foreach($hours as $h): ?>
        <div class="h-16 border-b border-gray-100 text-xs text-gray-400 text-right pr-2 pt-1">
          <?= $h > 12 ? $h-12 : $h ?><?= $h >= 12 ? 'pm' : 'am' ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Grid cols -->
    <div class="flex-1 flex ml-16 relative">
      <?php foreach($days as $d): ?>
      <div class="flex-1 relative border-r border-gray-100 last:border-0 <?= $d['is_today'] ? 'bg-green-50/20' : '' ?>">
        <!-- background grid lines -->
        <?php foreach($hours as $h): ?>
          <div class="h-16 border-b border-gray-100/50"></div>
        <?php endforeach; ?>

        <!-- Work Orders -->
        <?php 
        foreach($wos as $wo) {
           $ws = new DateTime($wo['scheduled_start']);
           $we = new DateTime($wo['scheduled_end']);
           if ($ws->format('Y-m-d') === $d['date']) {
               $start_h = (int)$ws->format('H') + ((int)$ws->format('i')/60);
               $end_h   = (int)$we->format('H') + ((int)$we->format('i')/60);
               
               // Clamp
               $start_h = max($start_h, $hours[0]);
               $end_h   = min($end_h, end($hours) + 1);
               
               if ($end_h <= $hours[0] || $start_h >= end($hours) + 1) continue;
               
               $top = ($start_h - $hours[0]) * 4; 
               $height = ($end_h - $start_h) * 4; 
               
               $color = match($wo['status']) {
                  'new' => 'bg-blue-50 text-blue-800 border-blue-200 hover:bg-blue-100',
                  'scheduled' => 'bg-purple-50 text-purple-800 border-purple-200 hover:bg-purple-100',
                  'assigned' => 'bg-indigo-50 text-indigo-800 border-indigo-200 hover:bg-indigo-100',
                  'in_progress' => 'bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100',
                  'on_hold' => 'bg-red-50 text-red-800 border-red-200 hover:bg-red-100',
                  'resolved','closed' => 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100',
                  default => 'bg-gray-50 text-gray-800 border-gray-200 hover:bg-gray-100'
               };
               
               echo "<a href='view.php?id={$wo['wo_id']}' class='absolute left-1 right-1 border rounded p-1.5 overflow-hidden transition-all shadow-sm z-20 flex flex-col gap-0.5 $color' style='top: {$top}rem; height: {$height}rem;'>";
               echo "<div class='text-[10px] font-bold truncate leading-tight'>{$wo['wo_number']}</div>";
               echo "<div class='text-[10px] truncate opacity-90 leading-tight'>" . htmlspecialchars($wo['technician_name'] ?: 'Unassigned') . "</div>";
               echo "</a>";
           }
        }
        ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
