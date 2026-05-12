<?php
// modules/workorders/edit.php — Edit existing work order.
// Logic only: guard, load data, require the shared form view.

$module = 'workorders';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { header('Location: index.php'); exit; }

$wo = get_wo_by_id($pdo, $id);
if (!$wo) { header('Location: index.php'); exit; }

$is_edit     = true;
$errors      = $_SESSION['wo_errors'] ?? [];
$old         = $_SESSION['wo_old'] ?? [];
unset($_SESSION['wo_errors'], $_SESSION['wo_old']);

$technicians = get_all_technicians($pdo);
$tickets     = get_available_tickets($pdo);
$all_parts   = get_all_parts($pdo);
$kb_articles = get_related_kb_articles($pdo, $wo['category_id'] ?? null);

require __DIR__ . '/_form.view.php';
require_once __DIR__ . '/../../includes/footer.php';
