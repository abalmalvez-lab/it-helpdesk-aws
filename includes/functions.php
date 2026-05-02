<?php
/**
 * Common Helper Functions
 */

require_once __DIR__ . '/../config/database.php';

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function generateTicketNumber() {
    return 'TKT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

function calculateSLADue($categoryId, $priority, $createdAt = null) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT sla_hours FROM categories WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $cat = $stmt->fetch();
    
    $baseHours = $cat ? $cat['sla_hours'] : 24;
    
    // Adjust by priority
    $multiplier = match($priority) {
        'Critical' => 0.25,
        'High' => 0.5,
        'Medium' => 1.0,
        'Low' => 1.5,
        default => 1.0
    };
    
    $slaHours = max(1, round($baseHours * $multiplier));
    $created = $createdAt ? new DateTime($createdAt) : new DateTime();
    $created->modify("+{$slaHours} hours");
    
    return $created->format('Y-m-d H:i:s');
}

function getStatusBadgeClass($status) {
    return match($status) {
        'Open' => 'bg-primary',
        'Assigned' => 'bg-info',
        'In Progress' => 'bg-warning text-dark',
        'Escalated' => 'bg-danger',
        'Resolved' => 'bg-success',
        'Closed' => 'bg-secondary',
        default => 'bg-light text-dark'
    };
}

function getPriorityBadgeClass($priority) {
    return match($priority) {
        'Critical' => 'bg-danger',
        'High' => 'bg-orange',
        'Medium' => 'bg-warning text-dark',
        'Low' => 'bg-info',
        default => 'bg-secondary'
    };
}

function formatDateTime($dt) {
    if (empty($dt)) return '—';
    return date('M d, Y h:i A', strtotime($dt));
}

function formatDate($dt) {
    if (empty($dt)) return '—';
    return date('M d, Y', strtotime($dt));
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

function isSLABreached($slaDue, $status) {
    if (empty($slaDue)) return false;
    if (in_array($status, ['Resolved', 'Closed'])) return false;
    return strtotime($slaDue) < time();
}

function logTicketAction($ticketId, $userId, $action, $oldStatus = null, $newStatus = null, $notes = '') {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO ticket_logs (ticket_id, user_id, action, old_status, new_status, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$ticketId, $userId, $action, $oldStatus, $newStatus, $notes]);
}

function getCategories() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name");
    return $stmt->fetchAll();
}

function getActiveStaff() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM support_staff WHERE status = 'Active' ORDER BY full_name");
    return $stmt->fetchAll();
}

function getTicketCounts() {
    $pdo = getDBConnection();
    $counts = [];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets");
    $counts['total'] = $stmt->fetch()['total'];
    
    $statuses = ['Open', 'Assigned', 'In Progress', 'Escalated', 'Resolved', 'Closed'];
    foreach ($statuses as $status) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM tickets WHERE status = ?");
        $stmt->execute([$status]);
        $counts[strtolower(str_replace(' ', '_', $status))] = $stmt->fetch()['c'];
    }
    
    // SLA breached (open tickets past SLA)
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM tickets WHERE status NOT IN ('Resolved','Closed') AND sla_due_datetime < NOW()");
    $counts['sla_breached'] = $stmt->fetch()['c'];
    
    return $counts;
}

function truncateText($text, $length = 80) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

function paginate($totalRecords, $perPage = 15, $currentPage = 1) {
    $totalPages = max(1, ceil($totalRecords / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $totalRecords,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset
    ];
}

function renderPagination($pagination, $queryParams = []) {
    if ($pagination['total_pages'] <= 1) return '';
    
    $html = '<nav><ul class="pagination pagination-sm justify-content-center mb-0">';
    
    // Previous
    $prevDisabled = $pagination['current_page'] <= 1 ? 'disabled' : '';
    $prevPage = $pagination['current_page'] - 1;
    $queryParams['page'] = $prevPage;
    $html .= '<li class="page-item ' . $prevDisabled . '"><a class="page-link" href="?' . http_build_query($queryParams) . '">&laquo;</a></li>';
    
    // Pages
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $pagination['current_page'] ? 'active' : '';
        $queryParams['page'] = $i;
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="?' . http_build_query($queryParams) . '">' . $i . '</a></li>';
    }
    
    // Next
    $nextDisabled = $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '';
    $queryParams['page'] = $pagination['current_page'] + 1;
    $html .= '<li class="page-item ' . $nextDisabled . '"><a class="page-link" href="?' . http_build_query($queryParams) . '">&raquo;</a></li>';
    
    $html .= '</ul></nav>';
    return $html;
}
