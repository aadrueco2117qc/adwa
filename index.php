<?php
// index.php — entry point. Redirects to the appropriate landing page.
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: modules/login.php');
} else {
    header('Location: modules/dashboard/index.php');
}
exit;
