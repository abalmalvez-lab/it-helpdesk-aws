<?php
/**
 * Authentication Helper
 * Login, logout, session management, role-based access
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function loginUser($email, $password) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'Active' LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['department'] = $user['department'];
        $_SESSION['employee_id'] = $user['employee_id'];
        
        // If support staff, store staff_id
        if ($user['role'] === 'Support Staff') {
            $stmt2 = $pdo->prepare("SELECT staff_id FROM support_staff WHERE user_id = ? LIMIT 1");
            $stmt2->execute([$user['user_id']]);
            $staff = $stmt2->fetch();
            if ($staff) {
                $_SESSION['staff_id'] = $staff['staff_id'];
            }
        }
        
        return true;
    }
    return false;
}

function logoutUser() {
    session_unset();
    session_destroy();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . '/login.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($_SESSION['role'], $roles)) {
        setFlashMessage('error', 'You do not have permission to access this page.');
        header('Location: ' . getBaseUrl() . '/dashboard.php');
        exit;
    }
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

function getCurrentUserName() {
    return $_SESSION['full_name'] ?? 'Guest';
}

function getCurrentStaffId() {
    return $_SESSION['staff_id'] ?? null;
}

function isAdmin() {
    return ($_SESSION['role'] ?? '') === 'Admin';
}

function isStaff() {
    return ($_SESSION['role'] ?? '') === 'Support Staff';
}

function isEndUser() {
    return ($_SESSION['role'] ?? '') === 'End User';
}

function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    
    // Find the project root relative to current script
    $dir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
    $projectRoot = realpath(__DIR__ . '/..');
    
    if ($dir === $projectRoot) {
        return rtrim($scriptDir, '/');
    }
    
    // Walk up to find project base
    $relative = str_replace($projectRoot, '', $dir);
    $depth = substr_count($relative, DIRECTORY_SEPARATOR);
    $base = $scriptDir;
    for ($i = 0; $i < $depth; $i++) {
        $base = dirname($base);
    }
    
    return rtrim($base, '/');
}
