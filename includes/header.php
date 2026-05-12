<?php
// includes/header.php
// Outputs: <!DOCTYPE> through <head>, opens <body> and the outer layout wrapper.
// Also defines $module_labels and $page_title — used by navbar.php too.
//
// Variables available here (set by index.php):
//   $pdo, $_SESSION, $page

$module_labels = [
    'tickets'       => 'Request Submission',
    'assets'        => 'Asset Management',
    'workorders'    => 'Work Orders',
    'technician'    => 'Technician Ops',
    'reports'       => 'Reports & Audit',
    'users'         => 'User Access Control',
    'profile'       => 'My Profile',
    'notifications' => 'Notifications',
];

$page_title = !empty($page) ? ($module_labels[$page] ?? 'MTRTS') : 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title) ?> — MTRTS</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/typography.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
          },
          colors: {
            olfu: {
              green:      '#1a5c2a',
              'green-md': '#1f6e32',
              'green-lt': '#256b38',
            }
          }
        }
      }
    }
  </script>
</head>
<body class="bg-gray-50 text-gray-800 antialiased h-screen overflow-hidden">
<div class="flex h-full overflow-hidden">
