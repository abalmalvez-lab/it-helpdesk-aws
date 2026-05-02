<?php
/**
 * CSRF Token Protection
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function validateCSRFToken($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken()) {
            http_response_code(403);
            die('Invalid CSRF token. Please refresh the page and try again.');
        }
    }
}
