<?php
$module = 'technician';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/conflict_resolver.php';

header('Content-Type: application/json; charset=utf-8');

// File validation rules (must match frontend idb-storage.js)
define('FILE_RULES', [
    'config' => [
        'maxSize' => 50 * 1024 * 1024, // 50MB
        'extensions' => ['.json', '.xml', '.cfg', '.conf', '.ini', '.txt'],
    ],
    'log' => [
        'maxSize' => 50 * 1024 * 1024, // 50MB
        'extensions' => ['.log', '.txt', '.csv'],
    ],
    'backup' => [
        'maxSize' => 50 * 1024 * 1024, // 50MB
        'extensions' => ['.zip', '.tar', '.gz', '.bak', '.img'],
    ],
    'image' => [
        'maxSize' => 20 * 1024 * 1024, // 20MB
        'extensions' => ['.jpg', '.jpeg', '.png', '.gif', '.webp'],
    ],
    'video' => [
        'maxSize' => 100 * 1024 * 1024, // 100MB
        'extensions' => ['.mp4', '.webm', '.mov'],
    ],
]);

// Get all allowed extensions for config uploads
function getAllowedConfigExtensions() {
    return array_unique(array_merge(
        FILE_RULES['config']['extensions'],
        FILE_RULES['log']['extensions'],
        FILE_RULES['backup']['extensions']
    ));
}

// Validate uploaded file
function validateUploadedFile($file, $fileType = 'image') {
    $filename = $file['name'] ?? '';
    $size = $file['size'] ?? 0;
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    
    // Check for upload errors
    if ($error !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server maximum size',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form maximum size',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        ];
        return ['valid' => false, 'error' => $errorMessages[$error] ?? 'Upload error'];
    }
    
    // Get file extension
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $ext = $ext ? '.' . $ext : '';
    
    // For config type, check against all config-related extensions
    if ($fileType === 'config') {
        $allowedExts = getAllowedConfigExtensions();
        $maxSize = 50 * 1024 * 1024; // 50MB
        
        if (!in_array($ext, $allowedExts)) {
            return [
                'valid' => false, 
                'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExts)
            ];
        }
    } else {
        // Check specific file type rules
        $rules = FILE_RULES[$fileType] ?? FILE_RULES['image'];
        $allowedExts = $rules['extensions'];
        $maxSize = $rules['maxSize'];
        
        if (!in_array($ext, $allowedExts)) {
            return [
                'valid' => false,
                'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExts)
            ];
        }
    }
    
    // Check file size
    if ($size > $maxSize) {
        $maxMB = round($maxSize / 1024 / 1024);
        return ['valid' => false, 'error' => "File exceeds maximum size of {$maxMB}MB"];
    }
    
    // Additional security: check for dangerous extensions
    $dangerousExts = ['.php', '.phtml', '.php3', '.php4', '.php5', '.exe', '.sh', '.bat', '.cmd', '.js', '.html', '.htm'];
    if (in_array($ext, $dangerousExts)) {
        return ['valid' => false, 'error' => 'File type not allowed for security reasons'];
    }
    
    return ['valid' => true, 'extension' => $ext];
}

// Handle both JSON and FormData (multipart) requests
$payload = [];
$action = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // JSON payload
        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $payload['action'] ?? '';
        
        // Check if this is a batch of items (from offline sync)
        if (empty($action) && isset($payload['items']) && is_array($payload['items'])) {
            $action = 'json_batch_sync';
        }
    } else {
        // FormData or URL-encoded payload
        $payload = $_POST;
        $action = $_POST['action'] ?? '';
        
        if (empty($action) && isset($_POST['items']) && is_array($_POST['items'])) {
            $action = 'batch_sync';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $wo_id = (int)($_GET['wo_id'] ?? 0);

    if ($action === 'get_state' && $wo_id > 0) {
        // Return current server state for a work order so the client can merge it
        // into its local draft after a sync cycle.
        try {
            $checklist    = get_checklist_for_work_order($pdo, $wo_id);
            $safety       = get_safety_checks_for_work_order($pdo, $wo_id);
            $notes        = get_work_order_notes($pdo, $wo_id);
            $media        = get_work_order_media($pdo, $wo_id);
            $parts        = get_work_order_parts($pdo, $wo_id);
            $time_logs    = get_time_logs($pdo, $wo_id);

            // Normalise checklist items to the same shape workorder.js expects
            $checklist_out = array_map(fn($item) => [
                'id'                => $item['item_id'] ?? $item['id'] ?? 0,
                'text'              => $item['item_text'] ?? $item['text'] ?? '',
                'required'          => (bool)($item['is_mandatory'] ?? true),
                'verification_type' => $item['verification_type'] ?? null,
                'is_done'           => (bool)($item['is_done'] ?? false),
            ], $checklist);

            $safety_out = array_map(fn($s) => [
                'id'        => $s['safety_id'] ?? $s['id'] ?? 0,
                'text'      => $s['safety_text'] ?? $s['check_text'] ?? $s['text'] ?? '',
                'mandatory' => (bool)($s['is_mandatory'] ?? true),
                'is_done'   => (bool)($s['is_done'] ?? false),
            ], $safety ?? []);

            echo json_encode([
                'success'    => true,
                'wo_id'      => $wo_id,
                'checklist'  => $checklist_out,
                'safety'     => $safety_out,
                'notes'      => $notes ?? [],
                'media'      => $media ?? [],
                'parts'      => $parts ?? [],
                'time_logs'  => $time_logs ?? [],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid GET action']);
    exit;
}

switch ($action) {
    case 'json_batch_sync':
        // Handle JSON batch sync for non-blob items
        $results = [];
        $items = $payload['items'] ?? [];
        
        foreach ($items as $item) {
            $itemId = $item['id'] ?? '';
            $itemAction = $item['type'] ?? $item['action'] ?? '';
            $woId = (int)($item['workOrderId'] ?? $item['wo_id'] ?? 0);
            $data = $item['data'] ?? [];
            
            if ($itemAction === 'checklist_update') {
                $checkItemId = (int)($data['itemId'] ?? $data['item_id'] ?? $data['id'] ?? 0);
                $completed = (bool)($data['completed'] ?? $data['is_done'] ?? false);
                if ($woId && $checkItemId) {
                    update_checklist_completion($pdo, $woId, $checkItemId, $completed);
                    $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction];
                } else {
                    $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Missing wo_id or itemId'];
                }
            } elseif ($itemAction === 'safety_update') {
                $safetyId = (int)($data['safetyId'] ?? $data['safety_id'] ?? $data['id'] ?? 0);
                $completed = (bool)($data['completed'] ?? $data['is_done'] ?? false);
                if ($woId && $safetyId) {
                    update_safety_completion($pdo, $woId, $safetyId, $completed);
                    $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction];
                } else {
                    $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Missing wo_id or safetyId'];
                }
            } elseif ($itemAction === 'note_add') {
                $noteText = trim($data['text'] ?? '');
                if ($woId && $noteText) {
                    add_work_order_note($pdo, $woId, $noteText, false);
                    $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction];
                } else {
                    $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Missing wo_id or text'];
                }
            } elseif ($itemAction === 'workorder_complete') {
                if ($woId > 0) {
                    try {
                        $completionPayload = is_array($data) ? $data : [];
                        $completionPayload['wo_id'] = $woId;
                        complete_work_order_transactional($pdo, $completionPayload, (int)($_SESSION['user_id'] ?? 0));
                        $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction];
                    } catch (Throwable $e) {
                        $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Error completing work order: ' . $e->getMessage()];
                    }
                } else {
                    $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Missing wo_id'];
                }
            } elseif ($itemAction === 'time_start') {
                if ($woId > 0) {
                    $labor_type = trim((string)($data['labor_type'] ?? '')) ?: null;
                    save_time_log($pdo, $woId, (int)($_SESSION['user_id'] ?? 0), 'start', $labor_type);
                    $cur = $pdo->prepare("SELECT status FROM work_orders WHERE wo_id = ?");
                    $cur->execute([$woId]);
                    $curStatus = $cur->fetchColumn();
                    if (!in_array($curStatus, ['resolved', 'closed'])) {
                        update_work_order_status($pdo, $woId, 'in_progress');
                    }
                    $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction];
                } else {
                    $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Missing wo_id'];
                }
            } elseif ($itemAction === 'time_pause') {
                if ($woId > 0) {
                    save_time_log($pdo, $woId, (int)($_SESSION['user_id'] ?? 0), 'pause');
                    $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction];
                } else {
                    $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Missing wo_id'];
                }
            } elseif ($itemAction === 'time_resume') {
                if ($woId > 0) {
                    save_time_log($pdo, $woId, (int)($_SESSION['user_id'] ?? 0), 'resume');
                    $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction];
                } else {
                    $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Missing wo_id'];
                }
            } elseif ($itemAction === 'time_stop') {
                $elapsed_ms = (int)($data['total_elapsed_ms'] ?? 0);
                $labor_type = trim((string)($data['labor_type'] ?? '')) ?: null;
                if ($woId > 0 && $elapsed_ms > 0) {
                    $pdo->prepare("
                        INSERT INTO wo_time_logs (wo_id, technician_id, action, labor_type, elapsed_ms, notes, logged_at)
                        VALUES (?, ?, 'stop', ?, ?, 'Time segment saved', NOW())
                    ")->execute([$woId, (int)($_SESSION['user_id'] ?? 0), $labor_type, $elapsed_ms]);
                    $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction];
                } else {
                    $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Missing wo_id or total_elapsed_ms'];
                }
            } elseif ($itemAction === 'note_add') {
                $noteText  = trim($data['text'] ?? '');
                $noteTitle = trim($data['title'] ?? '');
                $fullText  = $noteTitle !== '' ? "[{$noteTitle}] {$noteText}" : $noteText;
                if ($woId && $fullText) {
                    add_work_order_note($pdo, $woId, $fullText, false);
                    $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction];
                } else {
                    $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Missing wo_id or text'];
                }
            } elseif ($itemAction === 'part_add') {
                $partKey  = trim((string)($data['partNumber'] ?? ''));
                $qty      = (int)($data['qty'] ?? 1);
                $serial   = trim((string)($data['serial'] ?? ''));
                $category = trim((string)($data['category'] ?? ''));
                if ($woId <= 0 || $partKey === '' || $qty <= 0) {
                    $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Missing wo_id, partNumber or qty'];
                } elseif ($category === 'manual') {
                    $look = $pdo->prepare("SELECT part_id FROM parts_inventory WHERE is_active = 1 AND (part_number = ? OR part_name = ?) LIMIT 1");
                    $look->execute([$partKey, $partKey]);
                    $part = $look->fetch();
                    if ($part) {
                        try { save_work_order_part($pdo, $woId, (int)$part['part_id'], $qty, $serial ?: null); } catch (Throwable $e) {}
                    }
                    $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction];
                } else {
                    $look = $pdo->prepare("SELECT part_id FROM parts_inventory WHERE is_active = 1 AND (part_number = ? OR part_name = ?) LIMIT 1");
                    $look->execute([$partKey, $partKey]);
                    $part = $look->fetch();
                    if (!$part) {
                        $partKeyNorm = str_replace(['µ','μ','Ω','ω'], ['u','u','Ohm','Ohm'], $partKey);
                        if ($partKeyNorm !== $partKey) { $look->execute([$partKeyNorm, $partKeyNorm]); $part = $look->fetch(); }
                    }
                    if (!$part) {
                        $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => "Part '$partKey' not found"];
                    } else {
                        try {
                            $info = save_work_order_part($pdo, $woId, (int)$part['part_id'], $qty, $serial ?: null);
                            $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction, 'usage_id' => $info['usage_id']];
                        } catch (Throwable $e) {
                            $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => $e->getMessage()];
                        }
                    }
                }
            } elseif ($itemAction === 'signature_save') {
                $signer_name = trim((string)($data['signer_name'] ?? ''));
                $sig_url     = trim((string)($data['signature_data_url'] ?? ''));
                $satisfaction = isset($data['signer_satisfaction']) ? (int)$data['signer_satisfaction'] : null;
                if ($satisfaction !== null && $satisfaction <= 0) $satisfaction = null;
                $feedback = trim((string)($data['signer_feedback'] ?? $data['feedback'] ?? ''));
                if ($woId <= 0 || $signer_name === '') {
                    $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Missing wo_id or signer_name'];
                } elseif ($sig_url === '') {
                    $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Missing signature_data_url'];
                } else {
                    $signature_path = persist_technician_signature_from_data_url($woId, $sig_url);
                    if ($signature_path === 'data:inline') {
                        $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Invalid or empty signature image'];
                    } else {
                        upsert_work_order_signoff($pdo, $woId, $signer_name, $signature_path, $satisfaction, $feedback !== '' ? $feedback : null);
                        $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction, 'serverUrl' => $signature_path];
                    }
                }
            } elseif (in_array($itemAction, ['note_remove','evidence_remove','config_remove',
                'signature_clear','draft_save','time_log_remove','part_remove'])) {
                // Client-side draft management — no server state to change.
                $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction];
            } else {
                $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => "Unknown action '$itemAction'"];
            }
        }
        
        echo json_encode(['results' => $results, 'ok' => true]);
        exit;

    case 'batch_sync':
        // Handle multipart FormData batch sync for evidence/config files with blobs
        $results = [];
        
        // Find all item_* fields and group by item ID
        $itemIds = [];
        foreach ($_POST as $key => $value) {
          if (preg_match('/^item_(.+?)_/', $key, $m)) {
            $itemIds[$m[1]] = true;
          }
        }
        
        foreach (array_keys($itemIds) as $itemId) {
          $itemAction = $_POST["item_{$itemId}_action"] ?? '';
          $woId = (int)($_POST["item_{$itemId}_wo_id"] ?? 0);
          
          if ($itemAction === 'evidence_add') {
            $side = $_POST["item_{$itemId}_side"] ?? '';
            $kind = $_POST["item_{$itemId}_kind"] ?? 'image';
            $name = $_POST["item_{$itemId}_name"] ?? '';
            
            if (isset($_FILES["item_{$itemId}_file"])) {
              $file = $_FILES["item_{$itemId}_file"];
              
              // Validate file type and size
              $validation = validateUploadedFile($file, $kind);
              if (!$validation['valid']) {
                $results[] = ['id' => $itemId, 'ok' => false, 'action' => 'evidence_add', 'error' => $validation['error']];
                continue;
              }
              
              $upload_dir = __DIR__ . '/uploads/evidence/' . $woId . '/';
              if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
              
              // Sanitize filename and add timestamp to prevent collisions
              $original_name = basename($file['name']);
              $ext = $validation['extension'];
              $safe_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
              $filename = $safe_name . '_' . time() . $ext;
              $file_path = $upload_dir . $filename;
              
              if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $serverUrl = BASE_URL . 'modules/technician/uploads/evidence/' . $woId . '/' . $filename;
                // Pass side info with caption so auto-verify can detect before/after photos
                $caption = $name ? $name : $side;
                save_work_order_media($pdo, $woId, $kind, $serverUrl, $file['type'], filesize($file_path), $caption);
                $results[] = ['id' => $itemId, 'ok' => true, 'action' => 'evidence_add', 'serverUrl' => $serverUrl];
              } else {
                $results[] = ['id' => $itemId, 'ok' => false, 'action' => 'evidence_add', 'error' => 'Upload failed'];
              }
            } else {
              $results[] = ['id' => $itemId, 'ok' => false, 'action' => 'evidence_add', 'error' => 'No file'];
            }
          } elseif ($itemAction === 'config_add') {
            $name = $_POST["item_{$itemId}_name"] ?? '';
            
            if (isset($_FILES["item_{$itemId}_file"])) {
              $file = $_FILES["item_{$itemId}_file"];
              
              // Validate file type and size (config includes logs and backups)
              $validation = validateUploadedFile($file, 'config');
              if (!$validation['valid']) {
                $results[] = ['id' => $itemId, 'ok' => false, 'action' => 'config_add', 'error' => $validation['error']];
                continue;
              }
              
              $upload_dir = __DIR__ . '/uploads/config/' . $woId . '/';
              if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
              
              // Sanitize filename and add timestamp to prevent collisions
              $original_name = basename($file['name']);
              $ext = $validation['extension'];
              $safe_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
              $filename = $safe_name . '_' . time() . $ext;
              $file_path = $upload_dir . $filename;
              
              if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $serverUrl = BASE_URL . 'modules/technician/uploads/config/' . $woId . '/' . $filename;
                save_work_order_media($pdo, $woId, 'config', $serverUrl, $file['type'], filesize($file_path), $name);
                $results[] = ['id' => $itemId, 'ok' => true, 'action' => 'config_add', 'serverUrl' => $serverUrl];
              } else {
                $results[] = ['id' => $itemId, 'ok' => false, 'action' => 'config_add', 'error' => 'Upload failed'];
              }
            } else {
              $results[] = ['id' => $itemId, 'ok' => false, 'action' => 'config_add', 'error' => 'No file'];
            }
          } elseif ($itemAction === 'checklist_update') {
            // Process checklist updates in batch sync
            $itemIdField = (int)($_POST["item_{$itemId}_itemId"] ?? $_POST["item_{$itemId}_item_id"] ?? $_POST["item_{$itemId}_id"] ?? 0);
            $completed = filter_var($_POST["item_{$itemId}_completed"] ?? $_POST["item_{$itemId}_is_done"] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($woId && $itemIdField) {
              update_checklist_completion($pdo, $woId, $itemIdField, $completed);
              $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction];
            } else {
              $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Missing wo_id or itemId'];
            }
          } elseif ($itemAction === 'safety_update') {
            // Process safety updates in batch sync
            $safetyIdField = (int)($_POST["item_{$itemId}_safetyId"] ?? $_POST["item_{$itemId}_safety_id"] ?? $_POST["item_{$itemId}_id"] ?? 0);
            $completed = filter_var($_POST["item_{$itemId}_completed"] ?? $_POST["item_{$itemId}_is_done"] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($woId && $safetyIdField) {
              update_safety_completion($pdo, $woId, $safetyIdField, $completed);
              $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction];
            } else {
              $results[] = ['id' => $itemId, 'ok' => false, 'action' => $itemAction, 'error' => 'Missing wo_id or safetyId'];
            }
          } else {
            // Other actions - log and acknowledge
            $results[] = ['id' => $itemId, 'ok' => true, 'action' => $itemAction];
          }
        }
        
        echo json_encode(['results' => $results, 'ok' => true]);
        break;

    case 'time_start':
        save_time_log($pdo, (int)($payload['wo_id'] ?? 0), $_SESSION['user_id'], 'start');
        update_work_order_status($pdo, (int)($payload['wo_id'] ?? 0), 'in_progress');
        echo json_encode(['success' => true]);
        break;

    case 'time_pause':
        save_time_log($pdo, (int)($payload['wo_id'] ?? 0), $_SESSION['user_id'], 'pause');
        echo json_encode(['success' => true]);
        break;

    case 'time_resume':
        save_time_log($pdo, (int)($payload['wo_id'] ?? 0), $_SESSION['user_id'], 'resume');
        echo json_encode(['success' => true]);
        break;

    case 'time_stop':
        // Stop creates a draft time log entry but doesn't persist yet
        echo json_encode(['success' => true, 'message' => 'Time segment stopped (draft)']);
        break;

    case 'time_log_remove':
        // Remove time log entry (draft management)
        echo json_encode(['success' => true, 'message' => 'Time entry removed']);
        break;

    case 'checklist_update':
        $wo_id = (int)($payload['wo_id'] ?? 0);
        $item_id = (int)($payload['itemId'] ?? $payload['item_id'] ?? $payload['id'] ?? 0);
        $is_done = (bool)($payload['completed'] ?? $payload['is_done'] ?? false);
        update_checklist_completion($pdo, $wo_id, $item_id, $is_done);
        echo json_encode(['success' => true]);
        break;

    case 'safety_update':
        $wo_id = (int)($payload['wo_id'] ?? 0);
        $safety_id = (int)($payload['safetyId'] ?? $payload['safety_id'] ?? $payload['id'] ?? 0);
        $is_done = (bool)($payload['completed'] ?? $payload['is_done'] ?? false);
        update_safety_completion($pdo, $wo_id, $safety_id, $is_done);
        echo json_encode(['success' => true]);
        break;

    case 'note_add':
        $wo_id = (int)($payload['wo_id'] ?? 0);
        $note_text = trim($payload['text'] ?? '');
        // FIX: 4th param of add_work_order_note() is bool $is_voice, not a title string.
        // Title is not stored in wo_notes; discard it here (JS sends it for display only).
        if ($note_text) {
            add_work_order_note($pdo, $wo_id, $note_text, false);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Note text required']);
        }
        break;

    case 'note_remove':
        // Note remove is draft management, no DB action needed
        echo json_encode(['success' => true, 'message' => 'Note removed']);
        break;

    case 'part_add':
        $wo_id = (int)($payload['wo_id'] ?? 0);
        $part_number = trim($payload['partNumber'] ?? '');
        $quantity = (int)($payload['qty'] ?? 1);
        $serial = trim($payload['serial'] ?? '');

        // Find part by number
        $stmt = $pdo->prepare("SELECT part_id FROM parts_inventory WHERE part_number = ?");
        $stmt->execute([$part_number]);
        $part = $stmt->fetch();
        if ($part) {
            save_work_order_part($pdo, $wo_id, $part['part_id'], $quantity, $serial ?: null);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Part not found']);
        }
        break;

    case 'evidence_add':
        $wo_id = (int)($payload['wo_id'] ?? 0);
        $side = trim($payload['side'] ?? '');
        $kind = trim($payload['kind'] ?? 'image');
        $name = trim($payload['name'] ?? '');
        
        // Check for uploaded file in $_FILES
        if (isset($_FILES[$name])) {
            $file = $_FILES[$name];
            $upload_path = __DIR__ . '/uploads/evidence/' . $wo_id . '/';
            @mkdir($upload_path, 0755, true);
            $file_path = $upload_path . basename($file['name']);
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $serverUrl = BASE_URL . 'modules/technician/uploads/evidence/' . $wo_id . '/' . basename($file['name']);
                save_work_order_media($pdo, $wo_id, $kind, $serverUrl, $file['type'], filesize($file_path), $name);
                echo json_encode(['success' => true, 'message' => 'Evidence saved', 'serverUrl' => $serverUrl]);
            } else {
                echo json_encode(['success' => false, 'message' => 'File upload failed']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No file provided']);
        }
        break;

    case 'config_add':
        $wo_id = (int)($payload['wo_id'] ?? 0);
        $name = trim($payload['name'] ?? '');
        
        // Check for uploaded file in $_FILES
        if (isset($_FILES[$name])) {
            $file = $_FILES[$name];
            $upload_path = __DIR__ . '/uploads/config/' . $wo_id . '/';
            @mkdir($upload_path, 0755, true);
            $file_path = $upload_path . basename($file['name']);
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $serverUrl = BASE_URL . 'modules/technician/uploads/config/' . $wo_id . '/' . basename($file['name']);
                save_work_order_media($pdo, $wo_id, 'config', $serverUrl, $file['type'], filesize($file_path), $name);
                echo json_encode(['success' => true, 'message' => 'Config file saved', 'serverUrl' => $serverUrl]);
            } else {
                echo json_encode(['success' => false, 'message' => 'File upload failed']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No file provided']);
        }
        break;

    case 'config_remove':
        // For now, not implemented, as media remove not in functions
        echo json_encode(['success' => true]);
        break;

    case 'workorder_complete':
        try {
            complete_work_order_transactional($pdo, $payload, (int)($_SESSION['user_id'] ?? 0));
            echo json_encode(['success' => true, 'message' => 'Work order completed']);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error completing work order: ' . $e->getMessage()]);
        }
        break;

        case 'start_work':
            $wo_id = (int)($payload['wo_id'] ?? $_POST['wo_id'] ?? 0);
            $user_id = (int)($_SESSION['user_id'] ?? 0);
            
            // Debug logging
            error_log('[START_WORK] wo_id=' . $wo_id . ', user_id=' . $user_id . ', POST=' . json_encode($_POST));
            
            if ($wo_id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Work order ID required']);
                exit;
            }
        
        try {
            // Verify the work order exists and is assigned to this user
            $stmt = $pdo->prepare("SELECT wo_id, status, assigned_to FROM work_orders WHERE wo_id = ?");
            $stmt->execute([$wo_id]);
            $wo = $stmt->fetch();
            
            if (!$wo) {
                echo json_encode(['success' => false, 'message' => 'Work order not found']);
                break;
            }
            
            if ($wo['status'] !== 'assigned') {
                echo json_encode(['success' => false, 'message' => 'Work order must be in assigned status to start work']);
                break;
            }
            
            if ($wo['assigned_to'] != $user_id) {
                echo json_encode(['success' => false, 'message' => 'You can only start work on orders assigned to you']);
                break;
            }
            
            // Update status to in_progress
            $stmt = $pdo->prepare("
                UPDATE work_orders 
                SET status = 'in_progress', updated_at = NOW() 
                WHERE wo_id = ?
            ");
            $stmt->execute([$wo_id]);
            
            echo json_encode(['success' => true, 'message' => 'Work started successfully']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error starting work: ' . $e->getMessage()]);
        }
        break;

    case 'sync_complete':
        // Handle end-of-session sync with conflict detection and retry processing
        $wo_id = (int)($payload['wo_id'] ?? 0);
        $local_data = $payload['local_data'] ?? [];
        
        if (!$wo_id) {
            echo json_encode(['success' => false, 'message' => 'Work order ID required']);
            break;
        }
        
        try {
            // Fetch server version for conflict detection
            $stmt = $pdo->prepare("
                SELECT wo_id, status, assigned_to, notes, findings, actions_taken, updated_at
                FROM work_orders
                WHERE wo_id = ?
            ");
            $stmt->execute([$wo_id]);
            $server_wo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$server_wo) {
                echo json_encode(['success' => false, 'message' => 'Work order not found']);
                break;
            }
            
            // Detect conflicts
            $conflict = detect_conflict($local_data, $server_wo);
            
            if (!empty($conflict)) {
                // Merge using conflict resolution strategy
                $merged = merge_work_order($local_data, $server_wo, $conflict);
                
                // Update work order with merged data (but preserve server status/assignment)
                $stmt = $pdo->prepare("
                    UPDATE work_orders 
                    SET notes = ?, findings = ?, actions_taken = ?, last_synced_at = NOW()
                    WHERE wo_id = ?
                ");
                $stmt->execute([
                    $merged['notes'] ?? $server_wo['notes'],
                    $merged['findings'] ?? $server_wo['findings'],
                    $merged['actions_taken'] ?? $server_wo['actions_taken'],
                    $wo_id
                ]);
            }
            
            // Process retry queue for this work order
            $queue_items = get_sync_queue($pdo, $wo_id);
            $retry_results = [];
            
            foreach ($queue_items as $item) {
                $ready_to_retry = process_retry_queue($pdo, $item['id']);
                
                if ($ready_to_retry) {
                    // Item is ready to retry - this would be handled separately
                    // For now just flag it for later processing
                    $retry_results[] = [
                        'queue_id' => $item['id'],
                        'status' => 'pending_retry',
                        'retry_count' => $item['retry_count'] + 1
                    ];
                } else if ($item['retry_count'] >= 10) {
                    $retry_results[] = [
                        'queue_id' => $item['id'],
                        'status' => 'failed',
                        'needs_attention' => true
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Sync complete',
                'conflict_detected' => !empty($conflict),
                'retry_queue' => $retry_results
            ]);
        } catch (Throwable $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Sync error: ' . $e->getMessage()
            ]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

?>