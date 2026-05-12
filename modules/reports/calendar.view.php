<!-- modules/reports/calendar.view.php -->
<div class="flex justify-between items-center mb-4">
    <div>
        <h1 class="text-xl font-extrabold text-gray-900 leading-none">Operating & Service Calendar</h1>
        <p class="text-[11px] font-normal text-gray-500 mt-1">Working hours, holidays, and system-wide service availability.</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>modules/reports/index.php" class="bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-gray-50 transition shadow-sm flex items-center gap-2">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Dashboard
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 h-[calc(100vh-140px)]">
    <!-- Left Sidebar: Working Hours & Stats -->
    <div class="space-y-4 flex flex-col h-full">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex-1 overflow-hidden">
            <h3 class="text-[10px] font-bold text-gray-900 uppercase tracking-wider mb-3 flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-[#1a5c2a]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Weekly Schedule
            </h3>
            <div class="space-y-2">
                <?php 
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                foreach ($biz_hours as $bh): 
                    $is_today = date('w') == $bh['day_of_week'];
                ?>
                    <div class="flex items-center justify-between p-1.5 rounded-lg <?= $is_today ? 'bg-green-50 border border-green-100' : '' ?>">
                        <span class="text-[10px] font-semibold <?= $is_today ? 'text-[#1a5c2a]' : 'text-gray-600' ?>"><?= $days[$bh['day_of_week']] ?></span>
                        <div class="text-right">
                            <?php if ($bh['is_working']): ?>
                                <span class="text-[9px] font-bold text-gray-800 bg-gray-100 px-1 py-0.5 rounded"><?= date('g:i A', strtotime($bh['start_time'])) ?> - <?= date('g:i A', strtotime($bh['end_time'])) ?></span>
                            <?php else: ?>
                                <span class="text-[9px] font-bold text-red-500 uppercase">Closed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-gradient-to-br from-[#1a5c2a] to-[#2d8a41] rounded-xl shadow-md p-4 text-white">
            <h3 class="text-[10px] font-bold uppercase tracking-wider mb-1">SLA Protection</h3>
            <p class="text-[10px] opacity-90 leading-tight">System pauses all SLA countdowns during non-working hours shown here.</p>
        </div>
    </div>

    <!-- Main Calendar Area -->
    <div class="lg:col-span-3 flex flex-col h-full space-y-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col flex-1">
            <!-- Calendar Header -->
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                <?php
                $prev_m = $month - 1; $prev_y = $year; if ($prev_m == 0) { $prev_m = 12; $prev_y--; }
                $next_m = $month + 1; $next_y = $year; if ($next_m == 13) { $next_m = 1; $next_y++; }
                $month_name = date('F', mktime(0, 0, 0, $month, 10));
                ?>
                <div class="flex items-center gap-3">
                    <form action="" method="GET" class="flex items-center gap-1" id="cal-nav-form">
                        <select name="m" onchange="document.getElementById('cal-nav-form').submit()" class="bg-transparent border-none text-base font-bold text-gray-800 cursor-pointer focus:ring-0 p-0">
                            <?php for($i=1; $i<=12; $i++): ?>
                                <option value="<?= $i ?>" <?= $month == $i ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$i,10)) ?></option>
                            <?php endfor; ?>
                        </select>
                        <select name="y" onchange="document.getElementById('cal-nav-form').submit()" class="bg-transparent border-none text-base font-bold text-gray-800 cursor-pointer focus:ring-0 p-0">
                            <?php for($i=$year-5; $i<=$year+5; $i++): ?>
                                <option value="<?= $i ?>" <?= $year == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </form>
                </div>
                <div class="flex gap-1">
                    <a href="?m=<?= $prev_m ?>&y=<?= $prev_y ?>" class="p-1.5 hover:bg-gray-200 rounded-lg transition text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </a>
                    <a href="?m=<?= date('m') ?>&y=<?= date('Y') ?>" class="px-2 py-1 text-[10px] font-bold text-[#1a5c2a] hover:bg-green-50 rounded-lg transition border border-green-100">Today</a>
                    <a href="?m=<?= $next_m ?>&y=<?= $next_y ?>" class="p-1.5 hover:bg-gray-200 rounded-lg transition text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="p-3 flex-1 flex flex-col min-h-0">
                <div class="grid grid-cols-7 mb-1">
                    <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d): ?>
                        <div class="text-center text-[9px] font-bold text-gray-400 uppercase tracking-widest py-1"><?= $d ?></div>
                    <?php endforeach; ?>
                </div>

                <div class="grid grid-cols-7 gap-px bg-gray-100 border border-gray-100 rounded-lg overflow-hidden flex-1">
                    <?php
                    $first_day = date('w', mktime(0, 0, 0, $month, 1, $year));
                    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                    
                    // Padding for start of month
                    for ($i = 0; $i < $first_day; $i++) {
                        echo '<div class="bg-gray-50"></div>';
                    }

                    for ($day = 1; $day <= $days_in_month; $day++) {
                        $current_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $is_today = $current_date == date('Y-m-d');
                        
                        // Check if holiday
                        $day_holidays = [];
                        foreach ($holidays as $h) {
                            $h_date = $h['is_recurring'] ? date('m-d', strtotime($h['holiday_date'])) : $h['holiday_date'];
                            $c_date_check = $h['is_recurring'] ? sprintf('%02d-%02d', $month, $day) : $current_date;
                            if ($h_date == $c_date_check) {
                                $day_holidays[] = $h;
                            }
                        }

                        // Check if working day
                        $dow = date('w', mktime(0, 0, 0, $month, $day, $year));
                        $is_non_working = false;
                        foreach ($biz_hours as $bh) {
                            if ($bh['day_of_week'] == $dow && !$bh['is_working']) {
                                $is_non_working = true;
                                break;
                            }
                        }
                        ?>
                        <div class="bg-white p-1.5 relative group hover:bg-gray-50 transition-colors flex flex-col">
                            <span class="text-[11px] font-bold <?= $is_today ? 'bg-[#1a5c2a] text-white w-5 h-5 flex items-center justify-center rounded-full shadow-sm' : ($is_non_working ? 'text-gray-300' : 'text-gray-700') ?>">
                                <?= $day ?>
                            </span>
                            
                            <div class="mt-1 flex-1 overflow-hidden">
                                <?php if (!empty($day_holidays)): ?>
                                    <?php foreach ($day_holidays as $dh): ?>
                                        <div class="bg-red-50 border-l-2 border-red-500 px-1 py-0.5 rounded text-[8px] font-bold text-red-700 shadow-sm leading-tight truncate" title="<?= htmlspecialchars($dh['holiday_name']) ?>">
                                            <?= htmlspecialchars($dh['holiday_name']) ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php elseif ($is_non_working): ?>
                                    <div class="text-[8px] font-bold text-gray-300 uppercase tracking-tighter text-center mt-2">OFF</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    }

                    // Padding for end of month
                    $total_cells = $first_day + $days_in_month;
                    $remaining = 42 - $total_cells; // Use fixed 6 weeks for stability
                    for ($i = 0; $i < $remaining; $i++) {
                        echo '<div class="bg-gray-50"></div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex items-center gap-6 px-4 py-2 bg-white rounded-xl border border-gray-100 shadow-sm text-[9px] font-bold uppercase tracking-wider text-gray-500">
            <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded-full bg-[#1a5c2a]"></div> Today
            </div>
            <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded bg-red-100 border-l-2 border-red-500"></div> System Holiday
            </div>
            <div class="flex items-center gap-2 text-gray-300">
                 OFF / Non-Working
            </div>
        </div>
    </div>
</div>
