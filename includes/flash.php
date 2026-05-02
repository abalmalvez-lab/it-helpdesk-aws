<?php
/**
 * Flash Message Helper
 * Toast-style notifications
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function setFlashMessage($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

function renderFlashMessages() {
    $messages = getFlashMessages();
    if (empty($messages)) return '';
    
    $html = '';
    foreach ($messages as $msg) {
        $type = $msg['type'];
        $icon = match($type) {
            'success' => 'fa-check-circle',
            'error', 'danger' => 'fa-exclamation-circle',
            'warning' => 'fa-exclamation-triangle',
            'info' => 'fa-info-circle',
            default => 'fa-info-circle'
        };
        $bsType = ($type === 'error') ? 'danger' : $type;
        
        $html .= '<div class="alert alert-' . $bsType . ' alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="fas ' . $icon . ' me-2"></i>
            <div>' . htmlspecialchars($msg['message']) . '</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
    return $html;
}
