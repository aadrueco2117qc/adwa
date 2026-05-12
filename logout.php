<?php
// logout.php — destroys the session and redirects to login.
session_start();
require_once '../config/db.php';
require_once '../config/auth.php';
logout();
