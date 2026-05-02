<?php
// Redirect to login or dashboard
session_start();
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
