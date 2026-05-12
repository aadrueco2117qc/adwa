<!-- modules/reports/audit.view.php -->
<div class="max-w-[1600px] mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-[#1a5c2a]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                E-Discovery & Master Audit Logs
            </h1>
            <p class="text-gray-500 mt-1 font-medium">Compliance Monitoring • Immutable Activity Records • PII Protection</p>
        </div>
        <div class="flex gap-3">
            <a href="<?= BASE_URL ?>modules/reports/index.php" class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 shadow-sm transition-all hover:shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                Back to Analytics
            </a>
            <div class="relative group/tooltip">
                <div class="bg-blue-50 text-blue-700 border border-blue-200 px-4 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 cursor-help shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Compliance Info
                </div>
                <div class="absolute right-0 top-full mt-2 w-72 p-4 bg-gray-900 text-white text-xs rounded-xl shadow-2xl opacity-0 group-hover/tooltip:opacity-100 transition-opacity pointer-events-none z-50 leading-relaxed border border-gray-700">
                    <p class="font-bold text-blue-400 mb-2">Immutable Audit Protocol</p>
                    <p class="mb-3">This log is <strong>permanent</strong>. No user, including administrators, can modify or delete these entries once they are recorded.</p>
                    <p class="font-bold text-green-400 mb-2">PII Masking Active</p>
                    <p>Personally Identifiable Information (emails/phones) is automatically masked for non-administrative users to comply with Data Privacy regulations.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-6 items-end">
            <input type="hidden" name="page" value="1">
            
            <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Date From</label>
                <input type="date" name="date_from" value="<?= $f['date_from'] ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-2 focus:ring-[#1a5c2a] outline-none">
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Date To</label>
                <input type="date" name="date_to" value="<?= $f['date_to'] ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-2 focus:ring-[#1a5c2a] outline-none">
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Actor (User)</label>
                <select name="user_id" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-[#1a5c2a] outline-none appearance-none">
                    <option value="">All Users</option>
                    <?php foreach($users as $u): ?>
                        <option value="<?= $u['user_id'] ?>" <?= $f['user_id'] == $u['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Object Type</label>
                <select name="object_type" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-[#1a5c2a] outline-none appearance-none">
                    <option value="">All Objects</option>
                    <?php foreach($object_types as $ot): ?>
                        <option value="<?= $ot ?>" <?= $f['object_type'] == $ot ? 'selected' : '' ?>><?= ucfirst($ot) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lg:col-span-2 flex gap-2">
                <div class="flex-1 space-y-2">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Keyword Search</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($f['q']) ?>" placeholder="Search values..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-2 focus:ring-[#1a5c2a] outline-none">
                </div>
                <button type="submit" class="bg-gray-900 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-black transition-all shadow-lg hover:-translate-y-0.5">
                    Search
                </button>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Timestamp</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Actor</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Action</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Target</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Changes</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center text-gray-400 font-medium italic">No audit records found matching your criteria.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-gray-900"><?= date('M j, Y', strtotime($log['created_at'])) ?></p>
                                <p class="text-[10px] text-gray-400 font-bold"><?= date('g:i:s A', strtotime($log['created_at'])) ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-[10px] font-bold text-gray-500">
                                        <?= substr($log['user_name'] ?? 'SYS', 0, 1) ?>
                                    </div>
                                    <span class="text-sm font-bold text-gray-700"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                    $action_cls = match($log['action']) {
                                        'CREATE'   => 'bg-green-50 text-green-700 border-green-100',
                                        'UPDATE'   => 'bg-blue-50 text-blue-700 border-blue-100',
                                        'DELETE'   => 'bg-red-50 text-red-700 border-red-100',
                                        'USE_PART' => 'bg-purple-50 text-purple-700 border-purple-100',
                                        default    => 'bg-gray-50 text-gray-600 border-gray-100'
                                    };
                                ?>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black border <?= $action_cls ?>">
                                    <?= $log['action'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest"><?= $log['object_type'] ?></span>
                                    <span class="text-sm font-bold text-gray-700">#<?= $log['object_id'] ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 max-w-xs">
                                <div class="text-xs text-gray-500 line-clamp-2 leading-relaxed">
                                    <?php if ($log['new_values']): ?>
                                        <?= htmlspecialchars(substr($log['new_values'], 0, 100)) ?>...
                                    <?php else: ?>
                                        <span class="italic text-gray-300">No data payload</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button onclick="viewLogDetails(<?= htmlspecialchars(json_encode($log)) ?>)" class="text-[#1a5c2a] hover:text-black font-bold text-sm underline underline-offset-4 decoration-2 decoration-[#1a5c2a]/20">Inspect</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">Showing page <?= $page ?> of <?= $total_pages ?></p>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($f, ['page' => $page-1])) ?>" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold hover:bg-gray-50">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($f, ['page' => $page+1])) ?>" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold hover:bg-gray-50">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Details Modal -->
<div id="log-modal" class="hidden fixed inset-0 bg-gray-900/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-[2rem] w-full max-w-4xl shadow-2xl overflow-hidden border border-gray-100 animate-in fade-in zoom-in duration-200">
        <div class="px-10 py-8 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <div>
                <h3 class="text-2xl font-black text-gray-900 tracking-tight">Security Payload Inspection</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Audit Record: <span id="modal-log-id" class="font-bold text-[#1a5c2a]"></span> • <span id="modal-timestamp" class="text-gray-400"></span></p>
            </div>
            <button onclick="document.getElementById('log-modal').classList.add('hidden')" class="w-10 h-10 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-red-500 hover:border-red-100 hover:bg-red-50 transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <div class="p-10 max-h-[70vh] overflow-y-auto">
            <!-- Metadata Bar -->
            <div class="grid grid-cols-3 gap-4 mb-8">
                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Actor (IP)</p>
                    <p id="modal-ip" class="text-sm font-bold text-gray-700"></p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 col-span-2">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">System Environment (User Agent)</p>
                    <p id="modal-agent" class="text-xs font-bold text-gray-700 truncate"></p>
                </div>
            </div>

            <!-- Comparison Table -->
            <div class="space-y-4">
                <div class="flex items-center justify-between px-2">
                    <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest">Field Comparison</h4>
                    <div class="flex gap-4">
                        <span class="flex items-center gap-1.5 text-[10px] font-bold text-red-500"><span class="w-2 h-2 rounded-full bg-red-500"></span> Was</span>
                        <span class="flex items-center gap-1.5 text-[10px] font-bold text-green-600"><span class="w-2 h-2 rounded-full bg-green-600"></span> Now</span>
                    </div>
                </div>
                
                <div id="modal-diff-container" class="border border-gray-100 rounded-3xl overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-6 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest w-1/3">Field Name</th>
                                <th class="px-6 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Change Detail</th>
                            </tr>
                        </thead>
                        <tbody id="modal-diff-body" class="divide-y divide-gray-50">
                            <!-- Injected via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Compliance Notes -->
            <div class="mt-8 p-6 bg-blue-50/50 rounded-2xl border border-blue-100 flex gap-4">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex-shrink-0 flex items-center justify-center text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <h5 class="text-sm font-bold text-blue-900">Privacy Compliance Note</h5>
                    <p class="text-xs text-blue-700 leading-relaxed mt-1">
                        Any <strong>Personally Identifiable Information (PII)</strong> in the "Now" column has been processed by the system's masking engine. 
                        Records older than 2 years are subject to the <span class="font-bold underline cursor-help group/ret">Data Retention Policy
                            <span class="absolute bottom-full left-0 mb-2 hidden group-hover/ret:block w-64 p-3 bg-gray-900 text-white text-[10px] rounded-xl shadow-2xl z-[60] normal-case font-normal leading-normal">
                                <b>Minimization:</b> Data older than 2 years is partially anonymized.<br>
                                <b>Archiving:</b> Data older than 5 years has descriptions stripped to reduce storage liability.
                            </span>
                        </span>.
                    </p>
                </div>
            </div>
        </div>

        <div class="px-10 py-5 bg-[#1a5c2a] text-center">
            <p class="text-[10px] font-black text-white/50 uppercase tracking-[0.4em]">Verified Immutable Record • MTRTS Compliance Engine</p>
        </div>
    </div>
</div>

<script>
function viewLogDetails(log) {
    document.getElementById('modal-log-id').textContent = log.log_id;
    document.getElementById('modal-timestamp').textContent = log.created_at;
    document.getElementById('modal-ip').textContent = log.ip_address || 'Internal/CLI';
    document.getElementById('modal-agent').textContent = log.user_agent || 'Unknown Agent';
    
    const body = document.getElementById('modal-diff-body');
    body.innerHTML = '';

    const oldVals = log.old_values ? JSON.parse(log.old_values) : {};
    const newVals = log.new_values ? JSON.parse(log.new_values) : {};

    // Combine all unique keys
    const keys = Array.from(new Set([...Object.keys(oldVals), ...Object.keys(newVals)]));

    if (keys.length === 0) {
        body.innerHTML = '<tr><td colspan="2" class="px-6 py-8 text-center text-gray-400 italic">No field-level changes recorded for this action.</td></tr>';
    } else {
        keys.forEach(key => {
            const tr = document.createElement('tr');
            
            // Format Field Name
            const niceKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            
            const oldRaw = oldVals[key];
            const newRaw = newVals[key];
            
            // Format Values
            const formatVal = (v) => {
                if (v === null || v === undefined) return '<span class="text-gray-300 font-normal italic">null</span>';
                if (typeof v === 'boolean') return v ? 'Yes' : 'No';
                if (typeof v === 'object') return '<span class="text-[10px] font-mono">' + JSON.stringify(v) + '</span>';
                return `<span class="font-bold">${v}</span>`;
            };

            tr.innerHTML = `
                <td class="px-6 py-4">
                    <span class="text-xs font-black text-gray-500 tracking-tight">${niceKey}</span>
                </td>
                <td class="px-6 py-4">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2 text-xs text-red-400 line-through decoration-red-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                            ${formatVal(oldRaw)}
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-900">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#1a5c2a]"></span>
                            ${formatVal(newRaw)}
                        </div>
                    </div>
                </td>
            `;
            body.appendChild(tr);
        });
    }

    document.getElementById('log-modal').classList.remove('hidden');
}
</script>
