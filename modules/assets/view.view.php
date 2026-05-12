<?php
/**
 * @var array      $asset
 * @var array|null $warranty
 * @var array      $documents
 * @var array      $children
 * @var array      $history
 * @var int        $open_ticket_count
 * @var array      $wp
 * @var string     $active_tab
 * @var bool       $show_warn_banner
 * @var bool       $expired_banner
 * @var int        $id
 */
?>
<!-- Back + breadcrumb row -->
<div class="flex items-center gap-2 mb-4">
  <a href="index.php"
     class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    Back to Asset Registry
  </a>
  <span class="text-gray-300">/</span>
  <span class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($asset['asset_tag']) ?></span>
</div>

<?php if ($show_warn_banner): ?>
<div class="asset-banner banner-info mb-4">
  <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span>Warranty expiring in <strong><?= $wp['days_left'] ?> day<?= $wp['days_left'] !== 1 ? 's' : '' ?></strong> — consider renewing or flagging for review.</span>
</div>
<?php elseif ($expired_banner): ?>
<div class="asset-banner banner-warn mb-4">
  <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span>Warranty has <strong>expired</strong> as of <?= htmlspecialchars((new DateTime($warranty['warranty_end']))->format('M j, Y')) ?>.</span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_260px] gap-4 items-start">

  <!-- LEFT COLUMN -->
  <div class="flex flex-col gap-4">

    <!-- Asset info card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
            <span class="block w-0.5 h-4 bg-olfu-green rounded"></span>
            Asset Information
          </h2>
          <p class="text-xs text-gray-400 mt-0.5">
            Viewing <?= htmlspecialchars($asset['asset_tag']) ?> — <?= htmlspecialchars($asset['manufacturer'] . ' ' . $asset['model']) ?>
          </p>
        </div>
        <div class="flex gap-2 flex-wrap">
          <button onclick="openQrModal()"
                  class="inline-flex items-center gap-1.5 bg-gray-50 border border-gray-300 text-gray-700 hover:bg-gray-100 text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
            QR Code
          </button>
          <a href="edit.php?id=<?= $id ?>"
             class="inline-flex items-center gap-1.5 bg-amber-50 border border-amber-300 text-amber-700 hover:bg-amber-100 text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit
          </a>
        </div>
      </div>

      <div class="p-6">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
          <div>
            <div class="vf-lbl">Asset Tag</div>
            <div class="vf-val vf-mono"><?= htmlspecialchars($asset['asset_tag']) ?></div>
          </div>
          <div>
            <div class="vf-lbl">Category</div>
            <div class="vf-val"><?= cat_badge($asset['category_name'] ?? '—') ?></div>
          </div>
          <div>
            <div class="vf-lbl">Status</div>
            <div class="vf-val"><?= status_badge($asset['status']) ?></div>
          </div>
          <div>
            <div class="vf-lbl">Manufacturer</div>
            <div class="vf-val"><?= htmlspecialchars($asset['manufacturer']) ?></div>
          </div>
          <div>
            <div class="vf-lbl">Model</div>
            <div class="vf-val"><?= htmlspecialchars($asset['model']) ?></div>
          </div>
          <div>
            <div class="vf-lbl">Serial Number</div>
            <?php if ($asset['serial_number']): ?>
              <div class="vf-val vf-mono"><?= htmlspecialchars($asset['serial_number']) ?></div>
            <?php else: ?>
              <div class="vf-val vf-empty">Not set</div>
            <?php endif; ?>
          </div>

          <!-- Location -->
          <div class="col-span-2 md:col-span-3">
            <div class="sdiv">Location</div>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <div class="vf-lbl">Building</div>
                <div class="vf-val"><?= $asset['building'] ? htmlspecialchars($asset['building']) : '<span class="vf-empty">Not assigned</span>' ?></div>
              </div>
              <div>
                <div class="vf-lbl">Floor</div>
                <div class="vf-val"><?= $asset['floor'] ? htmlspecialchars($asset['floor']) : '<span class="vf-empty">Not assigned</span>' ?></div>
              </div>
              <div>
                <div class="vf-lbl">Room</div>
                <div class="vf-val"><?= $asset['room'] ? htmlspecialchars($asset['room']) : '<span class="vf-empty">Not assigned</span>' ?></div>
              </div>
            </div>
          </div>

          <div>
            <div class="vf-lbl">Install Date</div>
            <div class="vf-val"><?= $asset['install_date'] ? (new DateTime($asset['install_date']))->format('M j, Y') : '<span class="vf-empty">—</span>' ?></div>
          </div>
          <div>
            <div class="vf-lbl">Firmware Version</div>
            <?php if ($asset['firmware_version']): ?>
              <div class="vf-val"><?= htmlspecialchars($asset['firmware_version']) ?></div>
            <?php else: ?>
              <div class="vf-empty">Not set</div>
            <?php endif; ?>
          </div>
          <?php if ($asset['has_bulb_hours']): ?>
          <div>
            <div class="vf-lbl">Bulb Hours</div>
            <div class="vf-val"><?= $asset['bulb_hours'] !== null ? number_format($asset['bulb_hours']) . ' hrs' : '<span class="vf-empty">Not set</span>' ?></div>
          </div>
          <?php endif; ?>
          <div>
            <div class="vf-lbl">Network Info</div>
            <?php if ($asset['network_info']): ?>
              <div class="vf-val vf-mono"><?= htmlspecialchars($asset['network_info']) ?></div>
            <?php else: ?>
              <div class="vf-empty">Not set</div>
            <?php endif; ?>
          </div>
          <div>
            <div class="vf-lbl">Parent Asset</div>
            <?php if ($asset['parent_tag']): ?>
              <a href="view.php?id=<?= $asset['parent_asset_id'] ?>"
                 class="vf-val vf-mono hover:underline"><?= htmlspecialchars($asset['parent_tag']) ?></a>
            <?php else: ?>
              <div class="vf-empty">None</div>
            <?php endif; ?>
          </div>
          <div>
            <div class="vf-lbl">Cost Center</div>
            <div class="vf-val"><?= $asset['department_name'] ? htmlspecialchars($asset['department_name']) : '<span class="vf-empty">Not assigned</span>' ?></div>
          </div>
          <div>
            <div class="vf-lbl">Owner</div>
            <div class="vf-val"><?= $asset['owner_name'] ? htmlspecialchars($asset['owner_name']) : '<span class="vf-empty">Not assigned</span>' ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="tab-nav">
        <?php
        $tabs = ['warranty' => 'Warranty & Contract', 'documents' => 'Documents', 'history' => 'Repair History (' . count($history) . ')', 'children' => 'Child Assets (' . count($children) . ')'];
        foreach ($tabs as $key => $label):
        ?>
        <button class="tab-btn <?= $active_tab === $key ? 'tab-on' : '' ?>"
                onclick="switchTab('<?= $key ?>')">
          <?= htmlspecialchars($label) ?>
        </button>
        <?php endforeach; ?>
      </div>

      <!-- Warranty tab -->
      <div id="tab-warranty" class="p-5 <?= $active_tab !== 'warranty' ? 'hidden' : '' ?>">
        <?php if ($warranty): ?>
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <div class="vf-lbl">Warranty Start</div>
              <div class="vf-val"><?= (new DateTime($warranty['warranty_start']))->format('M j, Y') ?></div>
            </div>
            <div>
              <div class="vf-lbl">Warranty End</div>
              <div class="vf-val <?= $wp['expired'] ? 'text-red-600' : ($wp['days_left'] <= 30 ? 'text-amber-600' : '') ?>">
                <?= (new DateTime($warranty['warranty_end']))->format('M j, Y') ?>
                <?php if ($wp['expired']): ?><span class="ml-1 text-xs">(Expired)</span><?php endif; ?>
              </div>
            </div>
            <div>
              <div class="vf-lbl">Coverage Type</div>
              <div class="vf-val"><?= coverage_label($warranty['coverage_type']) ?></div>
            </div>
            <div>
              <div class="vf-lbl">Vendor / Contract</div>
              <div class="vf-val">
                <?= $warranty['vendor_name'] ? htmlspecialchars($warranty['vendor_name']) : '<span class="vf-empty">—</span>' ?>
                <?php if ($warranty['contract_reference']): ?>
                  <span class="text-gray-400"> — <?= htmlspecialchars($warranty['contract_reference']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="mt-3">
            <div class="flex justify-between text-xs text-gray-400 mb-1">
              <span><?= (new DateTime($warranty['warranty_start']))->format('M Y') ?></span>
              <span class="font-semibold <?= $wp['expired'] ? 'text-red-600' : ($wp['days_left'] <= 30 ? 'text-amber-600' : 'text-green-600') ?>">
                <?= $wp['expired'] ? 'Expired' : $wp['days_left'] . ' days remaining' ?>
              </span>
            </div>
            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full <?= $wp['color'] ?>" style="width:<?= $wp['pct'] ?>%"></div>
            </div>
          </div>

          <?php
          $wdocs = array_filter($documents, fn($d) => in_array($d['document_type'], ['warranty', 'contract']));
          ?>
          <?php if ($wdocs): ?>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Related Documents</p>
            <div class="flex flex-col gap-2">
              <?php foreach ($wdocs as $wd): ?>
                <?php
                $wd_lbl   = $wd['document_type'] === 'contract' ? 'Contract' : 'Warranty';
                $wd_color = $wd['document_type'] === 'contract' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700';
                $wd_sz    = $wd['file_size_kb'] >= 1024
                    ? round($wd['file_size_kb'] / 1024, 1) . ' MB'
                    : number_format($wd['file_size_kb']) . ' KB';
                $wd_viewable = in_array($wd['file_type'], ['pdf', 'jpg', 'jpeg', 'png']);
                ?>
                <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
                  <span class="text-xs font-semibold <?= $wd_color ?> px-2 py-0.5 rounded-full flex-shrink-0"><?= $wd_lbl ?></span>
                  <span class="text-sm text-gray-700 flex-1 truncate"><?= htmlspecialchars($wd['document_name']) ?></span>
                  <span class="text-xs text-gray-400 flex-shrink-0"><?= $wd_sz ?></span>
                  <?php if ($wd_viewable): ?>
                  <a href="doc_view.php?id=<?= $wd['document_id'] ?>" target="_blank"
                     class="text-gray-400 hover:text-blue-500 transition-colors flex-shrink-0" title="View">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                  </a>
                  <?php endif; ?>
                  <a href="doc_download.php?id=<?= $wd['document_id'] ?>"
                     class="text-olfu-green hover:text-olfu-green-md transition-colors flex-shrink-0" title="Download">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
          <div class="mt-3">
            <a href="edit.php?id=<?= $id ?>" class="text-xs text-olfu-green hover:underline">Edit warranty info →</a>
          </div>
        <?php else: ?>
          <p class="text-sm text-gray-400 italic">No warranty information recorded for this asset.</p>
          <a href="edit.php?id=<?= $id ?>" class="text-sm text-olfu-green hover:underline mt-2 inline-block">Add warranty info →</a>
        <?php endif; ?>
      </div>

      <!-- Documents tab -->
      <div id="tab-documents" class="p-5 <?= $active_tab !== 'documents' ? 'hidden' : '' ?>">

        <!-- Document list -->
        <div id="doc-list" class="flex flex-col gap-2 mb-4">
          <?php if ($documents): ?>
            <?php foreach ($documents as $doc): ?>
              <?php
              $ic_colors = [
                'pdf'  => 'bg-red-100 text-red-600',
                'jpg'  => 'bg-blue-100 text-blue-600',
                'jpeg' => 'bg-blue-100 text-blue-600',
                'png'  => 'bg-blue-100 text-blue-600',
                'dwg'  => 'bg-purple-100 text-purple-600',
                'zip'  => 'bg-yellow-100 text-yellow-600',
              ];
              $ic       = $ic_colors[$doc['file_type']] ?? 'bg-gray-100 text-gray-600';
              $size_fmt = $doc['file_size_kb'] >= 1024
                ? round($doc['file_size_kb'] / 1024, 1) . ' MB'
                : number_format($doc['file_size_kb']) . ' KB';
              $viewable = in_array($doc['file_type'], ['pdf','jpg','jpeg','png']);
              $type_labels = [
                'warranty'      => ['Warranty',      'bg-blue-100 text-blue-700'],
                'contract'      => ['Contract',       'bg-purple-100 text-purple-700'],
                'manual'        => ['Manual',         'bg-gray-100 text-gray-600'],
                'wiring_diagram'=> ['Wiring',         'bg-yellow-100 text-yellow-700'],
                'config_backup' => ['Config',         'bg-orange-100 text-orange-700'],
              ];
              $type_info = !empty($doc['document_type']) ? ($type_labels[$doc['document_type']] ?? null) : null;
              ?>
              <div class="doc-row" data-doc-id="<?= $doc['document_id'] ?>">
                <div class="doc-ic <?= $ic ?>"><?= strtoupper(htmlspecialchars($doc['file_type'])) ?></div>
                <div class="flex-1 min-w-0">
                  <!-- Name display / edit toggle -->
                  <div class="flex items-center gap-1 group">
                    <span class="doc-name text-sm font-medium text-gray-800 truncate">
                      <?= htmlspecialchars($doc['document_name']) ?>
                    </span>
                    <button onclick="startRename(<?= $doc['document_id'] ?>, this)"
                            class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-gray-700 transition-all flex-shrink-0" title="Rename">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                      </svg>
                    </button>
                  </div>
                  <div class="doc-meta flex items-center gap-2">
                    <?php if ($type_info): ?>
                      <span class="text-xs font-semibold <?= $type_info[1] ?> px-1.5 py-0 rounded-full"><?= $type_info[0] ?></span>
                    <?php endif; ?>
                    <?= (new DateTime($doc['uploaded_at']))->format('M j, Y') ?>
                    · <?= $size_fmt ?>
                  </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                  <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">v<?= $doc['version'] ?></span>
                  <?php if ($viewable): ?>
                  <a href="doc_view.php?id=<?= $doc['document_id'] ?>" target="_blank"
                     class="text-gray-400 hover:text-blue-500 transition-colors" title="View">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                  </a>
                  <?php endif; ?>
                  <a href="doc_download.php?id=<?= $doc['document_id'] ?>"
                     class="text-olfu-green hover:text-olfu-green-md transition-colors" title="Download">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                  </a>
                  <button onclick="deleteDoc(<?= $doc['document_id'] ?>, this)"
                          class="text-gray-400 hover:text-red-500 transition-colors" title="Delete">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p id="doc-empty" class="text-sm text-gray-400 italic">No documents uploaded yet.</p>
          <?php endif; ?>
        </div>

        <!-- Upload area -->
        <div id="doc-dropzone"
             class="upzone cursor-pointer transition-colors"
             ondragover="docDragOver(event)" ondragleave="docDragLeave(event)" ondrop="docDrop(event)"
             onclick="document.getElementById('doc-file-input').click()">
          <svg class="w-6 h-6 mx-auto mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
          </svg>
          <p class="text-sm font-semibold text-gray-600">Drag & drop or <span class="text-olfu-green underline">browse</span></p>
          <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG, DWG, ZIP — Max 50 MB</p>
        </div>
        <input type="file" id="doc-file-input" class="hidden"
               accept=".pdf,.jpg,.jpeg,.png,.dwg,.zip"
               onchange="docFileSelected(this.files)">

        <!-- Upload panel -->
        <div id="doc-upload-panel" class="hidden mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
          <div class="flex items-center gap-3 flex-wrap">
            <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span id="doc-selected-name" class="text-sm text-gray-700 truncate flex-1 min-w-0"></span>
            <select id="doc-type-sel" class="text-sm border border-gray-200 rounded-lg px-2 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-1 focus:ring-olfu-green flex-shrink-0">
              <option value="">General</option>
              <option value="manual">Manual</option>
              <option value="warranty">Warranty</option>
              <option value="contract">Contract</option>
              <option value="wiring_diagram">Wiring Diagram</option>
              <option value="config_backup">Config Backup</option>
            </select>
            <button onclick="docSubmitUpload()" id="doc-upload-btn"
                    class="bg-olfu-green text-white text-sm font-semibold px-4 py-1.5 rounded-lg hover:bg-olfu-green-md transition-colors whitespace-nowrap">
              Upload
            </button>
            <button onclick="docCancelUpload()" class="text-gray-400 hover:text-gray-600">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
          <div id="doc-upload-bar" class="hidden mt-2">
            <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
              <div id="doc-upload-fill" class="h-full bg-olfu-green rounded-full transition-all" style="width:0%"></div>
            </div>
            <p id="doc-upload-status" class="text-xs text-gray-500 mt-1"></p>
          </div>
        </div>

        <p id="doc-upload-msg" class="text-xs mt-2 hidden"></p>
      </div>

      <!-- Repair history tab -->
      <div id="tab-history" class="p-5 <?= $active_tab !== 'history' ? 'hidden' : '' ?>">
        <div class="flex justify-end mb-3">
          <a href="<?= BASE_URL ?>modules/tickets/add.php?asset_id=<?= $id ?>&asset_tag=<?= urlencode($asset['asset_tag']) ?>"
             class="inline-flex items-center gap-1.5 border border-olfu-green text-olfu-green text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-green-50 transition-colors">
            + Submit Ticket for this Asset
          </a>
        </div>
        <?php if ($history): ?>
          <table class="w-full border-collapse text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Ticket #</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Date</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Issue</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Priority</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Status</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">WO #</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($history as $h): ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50 cursor-pointer"
                    onclick="window.location='<?= BASE_URL ?>modules/tickets/view.php?id=<?= $h['ticket_id'] ?>'">
                  <td class="py-2 px-3">
                    <span class="asset-tag"><?= htmlspecialchars($h['ticket_number']) ?></span>
                  </td>
                  <td class="py-2 px-3 text-gray-500 text-xs whitespace-nowrap">
                    <?= (new DateTime($h['created_at']))->format('M j, Y') ?>
                  </td>
                  <td class="py-2 px-3 text-gray-700 max-w-xs truncate">
                    <?= htmlspecialchars($h['title']) ?>
                  </td>
                  <td class="py-2 px-3"><?= ticket_priority_badge($h['priority']) ?></td>
                  <td class="py-2 px-3"><?= ticket_status_badge($h['status']) ?></td>
                  <td class="py-2 px-3">
                    <?php if ($h['wo_number']): ?>
                      <span class="asset-tag text-xs"><?= htmlspecialchars($h['wo_number']) ?></span>
                    <?php else: ?>
                      <span class="text-gray-300 text-xs">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-sm text-gray-400 italic">No repair history for this asset yet.</p>
        <?php endif; ?>
      </div>

      <!-- Child assets tab -->
      <div id="tab-children" class="p-5 <?= $active_tab !== 'children' ? 'hidden' : '' ?>">
        <div class="flex justify-end mb-3">
          <a href="add.php?parent_id=<?= $id ?>"
             class="inline-flex items-center gap-1.5 border border-olfu-green text-olfu-green text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-green-50 transition-colors">
            + Add Child Asset
          </a>
        </div>
        <?php if ($children): ?>
          <table class="w-full border-collapse text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Asset Tag</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Model</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Category</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($children as $ch): ?>
                <tr class="row-link border-b border-gray-50"
                    onclick="window.location='view.php?id=<?= $ch['asset_id'] ?>'">
                  <td class="py-2 px-3"><span class="asset-tag"><?= htmlspecialchars($ch['asset_tag']) ?></span></td>
                  <td class="py-2 px-3 text-gray-700"><?= htmlspecialchars($ch['model']) ?></td>
                  <td class="py-2 px-3"><?= cat_badge($ch['category_name'] ?? '—') ?></td>
                  <td class="py-2 px-3"><?= status_badge($ch['status']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-sm text-gray-400 italic">No child assets linked to this asset.</p>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /left column -->

  <!-- RIGHT PANEL -->
  <div class="flex flex-col gap-3">

    <!-- Quick info -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="rp-hdr">Asset Quick Info</div>
      <div class="px-4 py-2">
        <div class="rp-row">
          <span class="rp-lbl">Asset Tag</span>
          <span class="rp-val asset-tag"><?= htmlspecialchars($asset['asset_tag']) ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Status</span>
          <span><?= status_badge($asset['status']) ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Open Tickets</span>
          <?php if ($open_ticket_count > 0): ?>
            <a href="<?= BASE_URL ?>modules/tickets/index.php?asset_id=<?= $id ?>"
               class="rp-val font-semibold text-red-600 hover:underline">
              <?= $open_ticket_count ?>
            </a>
          <?php else: ?>
            <span class="rp-val text-gray-500">0</span>
          <?php endif; ?>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Building</span>
          <span class="rp-val"><?= htmlspecialchars($asset['building'] ?? '—') ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Floor</span>
          <span class="rp-val"><?= htmlspecialchars($asset['floor'] ?? '—') ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Room</span>
          <span class="rp-val"><?= htmlspecialchars($asset['room'] ?? '—') ?></span>
        </div>
        <?php if ($asset['has_bulb_hours'] && $asset['bulb_hours'] !== null): ?>
        <div class="rp-row">
          <span class="rp-lbl">Bulb Hours</span>
          <span class="rp-val"><?= number_format($asset['bulb_hours']) ?> hrs</span>
        </div>
        <?php endif; ?>
        <div class="rp-row">
          <span class="rp-lbl">Child Assets</span>
          <span class="rp-val"><?= count($children) ?></span>
        </div>
      </div>
    </div>

    <!-- Warranty status -->
    <?php if ($warranty): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="rp-hdr">Warranty Status</div>
      <div class="px-4 py-3">
        <p class="text-sm text-gray-600 mb-3">
          Ends <strong class="<?= $wp['expired'] ? 'text-red-600' : ($wp['days_left'] <= 30 ? 'text-amber-600' : 'text-gray-800') ?>">
            <?= (new DateTime($warranty['warranty_end']))->format('M j, Y') ?>
          </strong>
        </p>
        <div class="flex justify-between text-xs text-gray-400 mb-1">
          <span><?= (new DateTime($warranty['warranty_start']))->format('M Y') ?></span>
          <span class="font-semibold <?= $wp['expired'] ? 'text-red-600' : ($wp['days_left'] <= 30 ? 'text-amber-600' : 'text-green-600') ?>">
            <?= $wp['expired'] ? 'Expired' : $wp['days_left'] . ' days left' ?>
          </span>
        </div>
        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
          <div class="h-full rounded-full <?= $wp['color'] ?>" style="width:<?= $wp['pct'] ?>%"></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Audit stamp -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="rp-hdr">Record Info</div>
      <div class="px-4 py-2">
        <div class="rp-row">
          <span class="rp-lbl">Created</span>
          <span class="rp-val text-xs"><?= (new DateTime($asset['created_at']))->format('M j, Y') ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Last Updated</span>
          <span class="rp-val text-xs"><?= time_ago($asset['updated_at']) ?></span>
        </div>
      </div>
    </div>

  </div><!-- /right panel -->

</div><!-- /grid -->

<!-- QR Code Modal -->
<div id="qr-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.45)" onclick="if(event.target===this)closeQrModal()">
  <div class="bg-white rounded-2xl shadow-xl w-80 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
      <h3 class="text-sm font-bold text-gray-800">QR Code — <?= htmlspecialchars($asset['asset_tag']) ?></h3>
      <button onclick="closeQrModal()" class="text-gray-400 hover:text-gray-700 transition-colors">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
    <div class="flex flex-col items-center gap-4 px-5 py-6">
      <div id="qr-canvas-wrap" class="w-52 h-52 flex items-center justify-center bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
        <!-- JS renders canvas here on open -->
      </div>
      <p class="text-xs text-gray-400 text-center">Scan to open this asset's page.<br>Print and attach to the physical device.</p>
      <button onclick="printQr()"
              class="w-full bg-olfu-green text-white text-sm font-semibold py-2 rounded-lg hover:bg-olfu-green-md transition-colors">
        Print Label
      </button>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const _qrUrl  = <?= json_encode('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL . 'modules/assets/view.php?id=' . $id) ?>;
const _qrTag  = <?= json_encode($asset['asset_tag']) ?>;
let   _qrInst = null;

function switchTab(key) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('tab-on'));
  document.querySelectorAll('[id^="tab-"]').forEach(p => p.classList.add('hidden'));
  event.currentTarget.classList.add('tab-on');
  const panel = document.getElementById('tab-' + key);
  if (panel) panel.classList.remove('hidden');
}

// ── Document upload ───────────────────────────────────────────
const _assetId  = <?= $id ?>;
const _csrfToken = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
let   _docFile  = null;

const docIcColors = {
  pdf: 'bg-red-100 text-red-600', jpg: 'bg-blue-100 text-blue-600',
  jpeg:'bg-blue-100 text-blue-600', png:'bg-blue-100 text-blue-600',
  dwg: 'bg-purple-100 text-purple-600', zip:'bg-yellow-100 text-yellow-600',
};

function docDragOver(e) {
  e.preventDefault();
  document.getElementById('doc-dropzone').classList.add('border-olfu-green','bg-green-50');
}
function docDragLeave(e) {
  document.getElementById('doc-dropzone').classList.remove('border-olfu-green','bg-green-50');
}
function docDrop(e) {
  e.preventDefault();
  docDragLeave(e);
  if (e.dataTransfer.files.length) docFileSelected(e.dataTransfer.files);
}
function docFileSelected(files) {
  if (!files.length) return;
  _docFile = files[0];
  document.getElementById('doc-selected-name').textContent = _docFile.name;
  document.getElementById('doc-upload-panel').classList.remove('hidden');
  document.getElementById('doc-upload-msg').classList.add('hidden');
}
function docCancelUpload() {
  _docFile = null;
  document.getElementById('doc-file-input').value = '';
  document.getElementById('doc-upload-panel').classList.add('hidden');
  document.getElementById('doc-upload-bar').classList.add('hidden');
  document.getElementById('doc-upload-fill').style.width = '0%';
  document.getElementById('doc-type-sel').value = '';
}
function docSubmitUpload() {
  if (!_docFile) return;
  const btn  = document.getElementById('doc-upload-btn');
  const bar  = document.getElementById('doc-upload-bar');
  const fill = document.getElementById('doc-upload-fill');
  const stat = document.getElementById('doc-upload-status');
  const msg  = document.getElementById('doc-upload-msg');

  btn.disabled = true;
  bar.classList.remove('hidden');
  stat.textContent = 'Uploading…';
  fill.style.width = '0%';

  const fd = new FormData();
  fd.append('csrf_token',    _csrfToken);
  fd.append('asset_id',      _assetId);
  fd.append('file',          _docFile);
  fd.append('document_type', document.getElementById('doc-type-sel')?.value || '');

  const xhr = new XMLHttpRequest();
  xhr.upload.onprogress = e => {
    if (e.lengthComputable) fill.style.width = Math.round(e.loaded / e.total * 100) + '%';
  };
  xhr.onload = () => {
    btn.disabled = false;
    let data;
    try { data = JSON.parse(xhr.responseText); } catch { data = {success:false,message:'Invalid server response.'}; }
    if (data.success) {
      stat.textContent = 'Done.';
      docCancelUpload();
      docAppendRow(data);
      msg.textContent = 'File uploaded successfully.';
      msg.className = 'text-xs mt-2 text-green-600';
      msg.classList.remove('hidden');
      document.getElementById('doc-empty')?.remove();
    } else {
      stat.textContent = '';
      msg.textContent = data.message || 'Upload failed.';
      msg.className = 'text-xs mt-2 text-red-500';
      msg.classList.remove('hidden');
    }
  };
  xhr.onerror = () => {
    btn.disabled = false;
    msg.textContent = 'Network error. Please try again.';
    msg.className = 'text-xs mt-2 text-red-500';
    msg.classList.remove('hidden');
  };
  xhr.open('POST', 'doc_upload.php');
  xhr.send(fd);
}

function docTypeBadge(type) {
  const map = {
    warranty:       ['Warranty',  'bg-blue-100 text-blue-700'],
    contract:       ['Contract',  'bg-purple-100 text-purple-700'],
    manual:         ['Manual',    'bg-gray-100 text-gray-600'],
    wiring_diagram: ['Wiring',    'bg-yellow-100 text-yellow-700'],
    config_backup:  ['Config',    'bg-orange-100 text-orange-700'],
  };
  if (!type || !map[type]) return '';
  const [lbl, cls] = map[type];
  return `<span class="text-xs font-semibold ${cls} px-1.5 py-0 rounded-full">${lbl}</span>`;
}
function docAppendRow(d) {
  const list = document.getElementById('doc-list');
  const ic   = docIcColors[d.file_type] || 'bg-gray-100 text-gray-600';
  const size = d.file_size_kb >= 1024
    ? (d.file_size_kb/1024).toFixed(1) + ' MB'
    : d.file_size_kb.toLocaleString() + ' KB';
  const row  = document.createElement('div');
  row.className = 'doc-row';
  row.dataset.docId = d.document_id;
  const viewable = ['pdf','jpg','jpeg','png'].includes(d.file_type.toLowerCase());
  const viewBtn  = viewable
    ? `<a href="doc_view.php?id=${d.document_id}" target="_blank"
          class="text-gray-400 hover:text-blue-500 transition-colors" title="View">
         <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
           <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
           <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
         </svg>
       </a>` : '';
  row.innerHTML = `
    <div class="doc-ic ${ic}">${d.file_type.toUpperCase()}</div>
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-1 group">
        <span class="doc-name text-sm font-medium text-gray-800 truncate">${d.document_name}</span>
        <button onclick="startRename(${d.document_id}, this)"
                class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-gray-700 transition-all flex-shrink-0" title="Rename">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
        </button>
      </div>
      <div class="doc-meta" style="display:flex;align-items:center;gap:6px">${docTypeBadge(d.document_type)}${d.uploaded_at} · ${size}</div>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
      <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">v${d.version}</span>
      ${viewBtn}
      <a href="doc_download.php?id=${d.document_id}"
         class="text-olfu-green hover:text-olfu-green-md transition-colors" title="Download">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
      </a>
      <button onclick="deleteDoc(${d.document_id}, this)"
              class="text-gray-400 hover:text-red-500 transition-colors" title="Delete">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
      </button>
    </div>`;
  list.prepend(row);
}

function startRename(docId, btn) {
  const row      = btn.closest('.doc-row');
  const nameSpan = row.querySelector('.doc-name');
  const current  = nameSpan.textContent.trim();

  // Replace span with input
  const input = document.createElement('input');
  input.type      = 'text';
  input.value     = current;
  input.className = 'text-sm font-medium text-gray-800 border border-olfu-green rounded px-1.5 py-0.5 w-full outline-none';
  nameSpan.replaceWith(input);
  input.focus();
  input.select();
  btn.classList.add('hidden');

  const commit = () => {
    const newName = input.value.trim();
    if (!newName || newName === current) {
      input.replaceWith(nameSpan);
      btn.classList.remove('hidden');
      return;
    }
    const fd = new FormData();
    fd.append('csrf_token',   _csrfToken);
    fd.append('document_id',  docId);
    fd.append('document_name', newName);
    fetch('doc_rename.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          nameSpan.textContent = data.document_name;
        }
        input.replaceWith(nameSpan);
        btn.classList.remove('hidden');
      });
  };

  input.addEventListener('blur',  commit);
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter')  { e.preventDefault(); input.blur(); }
    if (e.key === 'Escape') { input.value = current; input.blur(); }
  });
}

function deleteDoc(docId, btn) {
  if (!confirm('Delete this document? This cannot be undone.')) return;
  btn.disabled = true;
  const fd = new FormData();
  fd.append('csrf_token',  _csrfToken);
  fd.append('document_id', docId);
  fetch('doc_delete.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const row = document.querySelector(`[data-doc-id="${docId}"]`);
        if (row) row.remove();
        if (!document.querySelector('#doc-list .doc-row')) {
          const p = document.createElement('p');
          p.id = 'doc-empty';
          p.className = 'text-sm text-gray-400 italic';
          p.textContent = 'No documents uploaded yet.';
          document.getElementById('doc-list').prepend(p);
        }
      } else {
        btn.disabled = false;
        alert(data.message || 'Delete failed.');
      }
    });
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    if (!document.getElementById('qr-modal').classList.contains('hidden')) closeQrModal();
  }
});

// ── QR Code ───────────────────────────────────────────────────
function openQrModal() {
  document.getElementById('qr-modal').classList.remove('hidden');
  if (!_qrInst) {
    const wrap = document.getElementById('qr-canvas-wrap');
    wrap.innerHTML = '';
    _qrInst = new QRCode(wrap, {
      text:         _qrUrl,
      width:         208,
      height:        208,
      colorDark:    '#000000',
      colorLight:   '#f9fafb',
      correctLevel:  QRCode.CorrectLevel.M
    });
  }
}

function closeQrModal() {
  document.getElementById('qr-modal').classList.add('hidden');
}

function printQr() {
  const canvas = document.querySelector('#qr-canvas-wrap canvas');
  const img    = document.querySelector('#qr-canvas-wrap img');
  const src    = canvas ? canvas.toDataURL('image/png') : (img ? img.src : null);
  if (!src) return;

  const win = window.open('', '_blank', 'width=400,height=400');
  win.document.write(`<!DOCTYPE html><html><head><style>
    body { margin:0; display:flex; flex-direction:column; align-items:center;
           justify-content:center; height:100vh; font-family:monospace; }
    img  { width:200px; height:200px; }
    p    { margin:8px 0 0; font-size:14px; font-weight:bold; letter-spacing:1px; }
  </style></head><body>
    <img src="${src}">
    <p>${_qrTag}</p>
    <script>window.onload=()=>{window.print();}<\/script>
  </body></html>`);
  win.document.close();
}
</script>
