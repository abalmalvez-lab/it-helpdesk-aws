<?php
$pageTitle = 'View Ticket';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();

$pdo = getDBConnection();
$ticketId = intval($_GET['id'] ?? 0);

// Fetch ticket with related data
$stmt = $pdo->prepare("SELECT t.*, c.category_name, c.sla_hours, u.full_name as user_name, u.email as user_email, u.department, s.full_name as staff_name, s.specialization, s.staff_id as staff_record_id FROM tickets t LEFT JOIN categories c ON t.category_id = c.category_id LEFT JOIN users u ON t.user_id = u.user_id LEFT JOIN support_staff s ON t.assigned_staff_id = s.staff_id WHERE t.ticket_id = ?");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    setFlashMessage('error', 'Ticket not found.');
    header('Location: index.php');
    exit;
}

// Access control
if (isEndUser() && $ticket['user_id'] != getCurrentUserId()) {
    setFlashMessage('error', 'Access denied.');
    header('Location: index.php');
    exit;
}

// Fetch logs
$logs = $pdo->prepare("SELECT l.*, u.full_name FROM ticket_logs l LEFT JOIN users u ON l.user_id = u.user_id WHERE l.ticket_id = ? ORDER BY l.created_datetime DESC");
$logs->execute([$ticketId]);
$logs = $logs->fetchAll();

// Fetch resolution
$resolution = $pdo->prepare("SELECT r.*, s.full_name as staff_name FROM resolutions r LEFT JOIN support_staff s ON r.staff_id = s.staff_id WHERE r.ticket_id = ? ORDER BY r.created_datetime DESC LIMIT 1");
$resolution->execute([$ticketId]);
$resolution = $resolution->fetch();

// Fetch feedback
$feedback = $pdo->prepare("SELECT f.*, u.full_name FROM ticket_feedback f LEFT JOIN users u ON f.user_id = u.user_id WHERE f.ticket_id = ?");
$feedback->execute([$ticketId]);
$feedback = $feedback->fetch();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';
    $role = getCurrentUserRole();
    
    switch ($action) {
        case 'assign':
            if (!isAdmin()) break;
            $staffId = intval($_POST['staff_id'] ?? 0);
            if ($staffId > 0) {
                $oldStatus = $ticket['status'];
                $pdo->prepare("UPDATE tickets SET assigned_staff_id = ?, status = 'Assigned', updated_datetime = NOW() WHERE ticket_id = ?")->execute([$staffId, $ticketId]);
                logTicketAction($ticketId, getCurrentUserId(), 'Ticket Assigned', $oldStatus, 'Assigned', 'Assigned to staff');
                setFlashMessage('success', 'Ticket assigned successfully.');
            }
            break;
            
        case 'update_status':
            $newStatus = $_POST['new_status'] ?? '';
            $notes = trim($_POST['notes'] ?? '');
            $validStatuses = ['In Progress', 'Escalated'];
            if (in_array($newStatus, $validStatuses)) {
                $oldStatus = $ticket['status'];
                $pdo->prepare("UPDATE tickets SET status = ?, updated_datetime = NOW() WHERE ticket_id = ?")->execute([$newStatus, $ticketId]);
                logTicketAction($ticketId, getCurrentUserId(), 'Status Updated', $oldStatus, $newStatus, $notes);
                setFlashMessage('success', "Status updated to $newStatus.");
            }
            break;
            
        case 'resolve':
            $resolutionDetails = trim($_POST['resolution_details'] ?? '');
            $aiDraft = $_POST['ai_drafted_resolution'] ?? null;
            if (!empty($resolutionDetails)) {
                $staffId = getCurrentStaffId();
                if (!$staffId && isAdmin()) $staffId = $ticket['staff_record_id'] ?? 1;
                $createdAt = $ticket['created_datetime'];
                $resMinutes = round((time() - strtotime($createdAt)) / 60);
                
                $pdo->prepare("INSERT INTO resolutions (ticket_id, staff_id, resolution_details, ai_drafted_resolution, resolution_status, resolution_time_minutes) VALUES (?, ?, ?, ?, 'Final', ?)")->execute([$ticketId, $staffId, $resolutionDetails, $aiDraft, $resMinutes]);
                
                $oldStatus = $ticket['status'];
                $pdo->prepare("UPDATE tickets SET status = 'Resolved', resolved_datetime = NOW(), updated_datetime = NOW() WHERE ticket_id = ?")->execute([$ticketId]);
                logTicketAction($ticketId, getCurrentUserId(), 'Ticket Resolved', $oldStatus, 'Resolved', 'Resolution submitted');
                setFlashMessage('success', 'Ticket resolved successfully.');
            }
            break;
            
        case 'close':
            $rating = intval($_POST['rating'] ?? 0);
            $comments = trim($_POST['comments'] ?? '');
            
            $oldStatus = $ticket['status'];
            $pdo->prepare("UPDATE tickets SET status = 'Closed', closed_datetime = NOW(), updated_datetime = NOW() WHERE ticket_id = ?")->execute([$ticketId]);
            logTicketAction($ticketId, getCurrentUserId(), 'Ticket Closed', $oldStatus, 'Closed', 'Ticket closed');
            
            if ($rating > 0) {
                $satisfaction = match(true) {
                    $rating >= 4 => 'Very Satisfied',
                    $rating === 3 => 'Neutral',
                    default => 'Unsatisfied'
                };
                $pdo->prepare("INSERT INTO ticket_feedback (ticket_id, user_id, rating, comments, satisfaction_status) VALUES (?, ?, ?, ?, ?)")->execute([$ticketId, getCurrentUserId(), $rating, $comments, $satisfaction]);
            }
            setFlashMessage('success', 'Ticket closed.');
            break;
    }
    
    header("Location: view.php?id=$ticketId");
    exit;
}

$allStaff = getActiveStaff();
$slaBreach = isSLABreached($ticket['sla_due_datetime'], $ticket['status']);
?>

<div class="page-header">
    <h1><i class="fas fa-ticket-alt me-2"></i><?= e($ticket['ticket_number']) ?></h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/modules/tickets/index.php">Tickets</a></li>
            <li class="breadcrumb-item active"><?= e($ticket['ticket_number']) ?></li>
        </ol>
    </nav>
</div>

<div class="row">
    <!-- Main Content -->
    <div class="col-lg-8">
        <!-- Ticket Details -->
        <div class="card mb-3">
            <div class="card-header">
                <span><i class="fas fa-info-circle me-2"></i>Ticket Details</span>
                <div>
                    <span class="badge <?= getStatusBadgeClass($ticket['status']) ?> me-1"><?= e($ticket['status']) ?></span>
                    <span class="badge <?= getPriorityBadgeClass($ticket['priority_level']) ?>"><?= e($ticket['priority_level']) ?></span>
                    <?php if ($slaBreach): ?><span class="badge bg-danger ms-1"><i class="fas fa-clock"></i> SLA Breached</span><?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <h5 class="fw-600 mb-3"><?= e($ticket['issue_title']) ?></h5>
                <div class="mb-3 p-3 rounded" style="background:#f8fafc;">
                    <?= nl2br(e($ticket['issue_description'])) ?>
                </div>
                
                <?php if ($ticket['ai_suggested_category']): ?>
                <div class="ai-suggestion-box mb-3">
                    <strong>AI Suggested Category:</strong> <?= e($ticket['ai_suggested_category']) ?><br>
                    <strong>AI Suggested Priority:</strong> <?= e($ticket['ai_suggested_priority']) ?><br>
                    <?php if ($ticket['ai_recommendation_reason']): ?>
                    <strong>Reason:</strong> <?= e($ticket['ai_recommendation_reason']) ?>
                    <?php endif; ?>
                    <div class="ai-disclaimer">AI-generated suggestion. Please review before applying.</div>
                </div>
                <?php endif; ?>
                
                <div class="row fs-sm">
                    <div class="col-6 col-md-3 mb-2"><strong class="text-muted d-block">Category</strong><?= e($ticket['category_name'] ?? '—') ?></div>
                    <div class="col-6 col-md-3 mb-2"><strong class="text-muted d-block">Submitted By</strong><?= e($ticket['user_name']) ?></div>
                    <div class="col-6 col-md-3 mb-2"><strong class="text-muted d-block">Assigned To</strong><?= e($ticket['staff_name'] ?? 'Unassigned') ?></div>
                    <div class="col-6 col-md-3 mb-2"><strong class="text-muted d-block">Department</strong><?= e($ticket['department'] ?? '—') ?></div>
                    <div class="col-6 col-md-3 mb-2"><strong class="text-muted d-block">Created</strong><?= formatDateTime($ticket['created_datetime']) ?></div>
                    <div class="col-6 col-md-3 mb-2"><strong class="text-muted d-block">SLA Due</strong>
                        <span class="<?= $slaBreach ? 'sla-breached' : '' ?>"><?= formatDateTime($ticket['sla_due_datetime']) ?></span>
                    </div>
                    <div class="col-6 col-md-3 mb-2"><strong class="text-muted d-block">Resolved</strong><?= formatDateTime($ticket['resolved_datetime']) ?></div>
                    <div class="col-6 col-md-3 mb-2"><strong class="text-muted d-block">Closed</strong><?= formatDateTime($ticket['closed_datetime']) ?></div>
                </div>
            </div>
        </div>
        
        <!-- Resolution -->
        <?php if ($resolution): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-check-circle me-2 text-success"></i>Resolution</div>
            <div class="card-body">
                <div class="mb-2"><?= nl2br(e($resolution['resolution_details'])) ?></div>
                <div class="fs-xs text-muted">
                    Resolved by <strong><?= e($resolution['staff_name']) ?></strong> on <?= formatDateTime($resolution['created_datetime']) ?>
                    <?php if ($resolution['resolution_time_minutes']): ?>
                    &bull; Resolution time: <?= round($resolution['resolution_time_minutes'] / 60, 1) ?> hours
                    <?php endif; ?>
                </div>
                <?php if ($resolution['ai_drafted_resolution']): ?>
                <div class="ai-suggestion-box mt-2">
                    <strong>AI Draft Used:</strong><br><?= nl2br(e($resolution['ai_drafted_resolution'])) ?>
                    <div class="ai-disclaimer">AI-generated draft was reviewed and edited by staff.</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Feedback -->
        <?php if ($feedback): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-star me-2 text-warning"></i>User Feedback</div>
            <div class="card-body">
                <div class="mb-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star <?= $i <= $feedback['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                    <?php endfor; ?>
                    <span class="ms-2 badge bg-light text-dark"><?= e($feedback['satisfaction_status']) ?></span>
                </div>
                <?php if ($feedback['comments']): ?>
                <p class="mb-0 text-muted"><?= nl2br(e($feedback['comments'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Activity Log -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-history me-2"></i>Activity Log</div>
            <div class="card-body p-0">
                <?php if (empty($logs)): ?>
                <div class="p-3 text-muted">No activity recorded yet.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Action</th><th>Status Change</th><th>Notes</th><th>By</th><th>When</th></tr></thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="fw-600 fs-sm"><?= e($log['action']) ?></td>
                                <td class="fs-sm">
                                    <?php if ($log['old_status'] || $log['new_status']): ?>
                                    <?= e($log['old_status'] ?? '—') ?> → <span class="badge <?= getStatusBadgeClass($log['new_status']) ?>"><?= e($log['new_status']) ?></span>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td class="fs-sm"><?= e(truncateText($log['notes'] ?? '', 60)) ?></td>
                                <td class="fs-sm"><?= e($log['full_name'] ?? 'System') ?></td>
                                <td class="fs-xs text-muted"><?= formatDateTime($log['created_datetime']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar Actions -->
    <div class="col-lg-4">
        <?php if (isAdmin() && in_array($ticket['status'], ['Open', 'Escalated'])): ?>
        <!-- Assign Staff -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-user-plus me-2"></i>Assign Staff</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfTokenField() ?>
                    <input type="hidden" name="action" value="assign">
                    <div class="mb-3">
                        <select name="staff_id" class="form-select form-select-sm" required>
                            <option value="">Select staff member...</option>
                            <?php foreach ($allStaff as $s): ?>
                            <option value="<?= $s['staff_id'] ?>" <?= $ticket['assigned_staff_id'] == $s['staff_id'] ? 'selected' : '' ?>><?= e($s['full_name']) ?> (<?= e($s['specialization']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-user-check me-1"></i>Assign</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ((isStaff() || isAdmin()) && in_array($ticket['status'], ['Assigned', 'In Progress'])): ?>
        <!-- Update Status -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-exchange-alt me-2"></i>Update Status</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfTokenField() ?>
                    <input type="hidden" name="action" value="update_status">
                    <div class="mb-2">
                        <select name="new_status" class="form-select form-select-sm" required>
                            <option value="In Progress" <?= $ticket['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Escalated">Escalated</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Add notes..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-warning w-100"><i class="fas fa-save me-1"></i>Update</button>
                </form>
            </div>
        </div>
        
        <!-- Resolve -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-check-circle me-2 text-success"></i>Resolve Ticket</div>
            <div class="card-body">
                <button type="button" class="btn btn-sm btn-ai w-100 mb-2" id="aiTroubleshootBtn" onclick="getTroubleshooting(this)">
                    <i class="fas fa-robot me-1"></i>AI Troubleshooting
                </button>
                <div id="troubleshootingArea" class="mb-2" style="display:none;"></div>
                
                <form method="POST" id="resolveForm">
                    <?= csrfTokenField() ?>
                    <input type="hidden" name="action" value="resolve">
                    <input type="hidden" name="ai_drafted_resolution" id="ai_drafted_resolution">
                    <div class="mb-2">
                        <textarea name="staff_notes" id="staff_notes" class="form-control form-control-sm" rows="2" placeholder="Internal notes (optional)..."></textarea>
                    </div>
                    <button type="button" class="btn btn-sm btn-ai w-100 mb-2" onclick="draftAIResolution(this)">
                        <i class="fas fa-robot me-1"></i>Draft Resolution with AI
                    </button>
                    <div id="draftArea" class="mb-2" style="display:none;"></div>
                    <div class="mb-2">
                        <textarea name="resolution_details" id="resolution_details" class="form-control form-control-sm" rows="3" placeholder="Final resolution details..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success w-100"><i class="fas fa-check me-1"></i>Resolve</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($ticket['status'] === 'Resolved' && (isEndUser() || isAdmin())): ?>
        <!-- Close Ticket -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-lock me-2"></i>Close Ticket</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfTokenField() ?>
                    <input type="hidden" name="action" value="close">
                    <div class="mb-2">
                        <label class="form-label fs-sm">Satisfaction Rating</label>
                        <select name="rating" class="form-select form-select-sm">
                            <option value="5">★★★★★ Excellent</option>
                            <option value="4">★★★★ Good</option>
                            <option value="3">★★★ Average</option>
                            <option value="2">★★ Poor</option>
                            <option value="1">★ Very Poor</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <textarea name="comments" class="form-control form-control-sm" rows="2" placeholder="Optional feedback..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-secondary w-100"><i class="fas fa-lock me-1"></i>Close Ticket</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- AI Tools -->
        <?php if (!isEndUser() || true): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-robot me-2"></i>AI Tools <span class="badge badge-ai">AI</span></div>
            <div class="card-body">
                <button class="btn btn-sm btn-ai w-100 mb-2" onclick="summarizeTicket(this)"><i class="fas fa-align-left me-1"></i>AI Summary</button>
                <?php if (!isEndUser()): ?>
                <button class="btn btn-sm btn-ai w-100 mb-2" onclick="escalationCheck(this)"><i class="fas fa-level-up-alt me-1"></i>Escalation Recommendation</button>
                <?php endif; ?>
                <div id="aiToolsOutput"></div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isAdmin() && !in_array($ticket['status'], ['Closed'])): ?>
        <!-- Admin Actions -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-cog me-2"></i>Admin Actions</div>
            <div class="card-body">
                <a href="<?= $baseUrl ?>/modules/tickets/edit.php?id=<?= $ticketId ?>" class="btn btn-sm btn-outline-secondary w-100 mb-2"><i class="fas fa-edit me-1"></i>Edit Ticket</a>
                <form method="POST" action="<?= $baseUrl ?>/modules/tickets/delete.php" onsubmit="return confirm('Delete this ticket permanently?')">
                    <?= csrfTokenField() ?>
                    <input type="hidden" name="ticket_id" value="<?= $ticketId ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="fas fa-trash me-1"></i>Delete Ticket</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
const ticketData = {
    ticket_id: <?= $ticketId ?>,
    issue_title: <?= json_encode($ticket['issue_title']) ?>,
    issue_description: <?= json_encode($ticket['issue_description']) ?>,
    category_name: <?= json_encode($ticket['category_name'] ?? '') ?>,
    priority_level: <?= json_encode($ticket['priority_level']) ?>,
    status: <?= json_encode($ticket['status']) ?>
};

function getTroubleshooting(btn) {
    aiAction(btn, '<?= $baseUrl ?>/modules/ai/troubleshooting.php', ticketData, function(data) {
        const area = document.getElementById('troubleshootingArea');
        if (data.success && data.data) {
            const d = data.data;
            let html = '<div class="ai-suggestion-box fs-sm">';
            html += '<strong>Possible Cause:</strong> ' + (d.possible_cause || '—') + '<br>';
            if (d.troubleshooting_steps && d.troubleshooting_steps.length) {
                html += '<strong>Steps:</strong><ol class="mb-1">';
                d.troubleshooting_steps.forEach(s => html += '<li>' + s + '</li>');
                html += '</ol>';
            }
            if (d.information_to_collect && d.information_to_collect.length) {
                html += '<strong>Collect:</strong> ' + d.information_to_collect.join(', ') + '<br>';
            }
            html += '<strong>Escalate if:</strong> ' + (d.escalation_condition || '—');
            html += '<div class="ai-disclaimer">AI-generated suggestion. Please review before applying.</div></div>';
            area.innerHTML = html;
            area.style.display = 'block';
        } else {
            area.innerHTML = '<div class="alert alert-warning fs-sm mb-0">' + (data.error || 'Failed') + '</div>';
            area.style.display = 'block';
        }
    });
}

function draftAIResolution(btn) {
    const notes = document.getElementById('staff_notes').value;
    const payload = Object.assign({}, ticketData, { staff_notes: notes });
    
    aiAction(btn, '<?= $baseUrl ?>/modules/ai/draft_resolution.php', payload, function(data) {
        const area = document.getElementById('draftArea');
        if (data.success && data.data) {
            document.getElementById('resolution_details').value = data.data.draft_resolution || '';
            document.getElementById('ai_drafted_resolution').value = data.data.draft_resolution || '';
            area.innerHTML = '<div class="ai-suggestion-box fs-sm"><strong>Recommended Status:</strong> ' + (data.data.recommended_status || '—') + '<div class="ai-disclaimer">AI-generated suggestion. Please review and edit before submitting.</div></div>';
            area.style.display = 'block';
        } else {
            area.innerHTML = '<div class="alert alert-warning fs-sm mb-0">' + (data.error || 'Failed') + '</div>';
            area.style.display = 'block';
        }
    });
}

function summarizeTicket(btn) {
    aiAction(btn, '<?= $baseUrl ?>/modules/ai/summarize_ticket.php', { ticket_id: ticketData.ticket_id }, function(data) {
        const output = document.getElementById('aiToolsOutput');
        if (data.success && data.data) {
            const d = data.data;
            let html = '<div class="ai-suggestion-box fs-sm mt-2">';
            html += '<strong>Summary:</strong> ' + (d.summary || '—') + '<br>';
            html += '<strong>Status:</strong> ' + (d.current_status || '—') + '<br>';
            if (d.actions_taken && d.actions_taken.length) {
                html += '<strong>Actions:</strong> ' + d.actions_taken.join('; ') + '<br>';
            }
            html += '<strong>Next Step:</strong> ' + (d.recommended_next_step || '—');
            html += '<div class="ai-disclaimer">AI-generated suggestion. Please review before applying.</div></div>';
            output.innerHTML = html;
        } else {
            output.innerHTML = '<div class="alert alert-warning fs-sm mt-2">' + (data.error || 'Failed') + '</div>';
        }
    });
}

function escalationCheck(btn) {
    aiAction(btn, '<?= $baseUrl ?>/modules/ai/escalation_recommendation.php', { ticket_id: ticketData.ticket_id }, function(data) {
        const output = document.getElementById('aiToolsOutput');
        if (data.success && data.data) {
            const d = data.data;
            let html = '<div class="ai-suggestion-box fs-sm mt-2">';
            html += '<strong>Escalation Needed:</strong> ' + (d.escalation_needed ? '<span class="text-danger">Yes</span>' : '<span class="text-success">No</span>') + '<br>';
            html += '<strong>Reason:</strong> ' + (d.reason || '—') + '<br>';
            html += '<strong>Specialization:</strong> ' + (d.suggested_specialization || '—') + '<br>';
            html += '<strong>Urgency:</strong> ' + (d.urgency_level || '—');
            html += '<div class="ai-disclaimer">AI-generated suggestion. Please review before applying.</div></div>';
            output.innerHTML = html;
        } else {
            output.innerHTML = '<div class="alert alert-warning fs-sm mt-2">' + (data.error || 'Failed') + '</div>';
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
