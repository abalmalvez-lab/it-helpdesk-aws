<?php
$pageTitle = 'Create Ticket';
require_once __DIR__ . '/../../includes/header.php';
requireLogin();
if (isStaff()) { requireRole(['Admin', 'End User']); }

$pdo = getDBConnection();
$categories = getCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $title = trim($_POST['issue_title'] ?? '');
    $description = trim($_POST['issue_description'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $priority = $_POST['priority_level'] ?? 'Medium';
    $aiCategory = $_POST['ai_suggested_category'] ?? null;
    $aiPriority = $_POST['ai_suggested_priority'] ?? null;
    $aiReason = $_POST['ai_recommendation_reason'] ?? null;
    
    $errors = [];
    if (empty($title)) $errors[] = 'Issue title is required.';
    if (empty($description)) $errors[] = 'Issue description is required.';
    if ($categoryId <= 0) $errors[] = 'Please select a category.';
    if (!in_array($priority, ['Low','Medium','High','Critical'])) $errors[] = 'Invalid priority level.';
    
    if (empty($errors)) {
        $ticketNumber = generateTicketNumber();
        $slaDue = calculateSLADue($categoryId, $priority);
        
        $stmt = $pdo->prepare("INSERT INTO tickets (ticket_number, user_id, category_id, issue_title, issue_description, priority_level, status, ai_suggested_category, ai_suggested_priority, ai_recommendation_reason, sla_due_datetime) VALUES (?, ?, ?, ?, ?, ?, 'Open', ?, ?, ?, ?)");
        $stmt->execute([$ticketNumber, getCurrentUserId(), $categoryId, $title, $description, $priority, $aiCategory, $aiPriority, $aiReason, $slaDue]);
        
        $ticketId = $pdo->lastInsertId();
        logTicketAction($ticketId, getCurrentUserId(), 'Ticket Created', null, 'Open', 'Ticket submitted by user');
        
        setFlashMessage('success', "Ticket $ticketNumber created successfully!");
        header('Location: view.php?id=' . $ticketId);
        exit;
    }
}
?>

<div class="page-header">
    <h1><i class="fas fa-plus-circle me-2"></i>Submit New Ticket</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/modules/tickets/index.php">Tickets</a></li>
            <li class="breadcrumb-item active">Create</li>
        </ol>
    </nav>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle me-1"></i>
    <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="fas fa-edit me-2"></i>Ticket Details</div>
            <div class="card-body">
                <form method="POST" id="ticketForm">
                    <?= csrfTokenField() ?>
                    <input type="hidden" name="ai_suggested_category" id="ai_suggested_category">
                    <input type="hidden" name="ai_suggested_priority" id="ai_suggested_priority">
                    <input type="hidden" name="ai_recommendation_reason" id="ai_recommendation_reason">
                    
                    <div class="mb-3">
                        <label class="form-label">Issue Title <span class="text-danger">*</span></label>
                        <input type="text" name="issue_title" id="issue_title" class="form-control" placeholder="Brief summary of the issue" value="<?= e($_POST['issue_title'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Issue Description <span class="text-danger">*</span></label>
                        <textarea name="issue_description" id="issue_description" class="form-control" rows="5" placeholder="Describe the issue in detail..." required><?= e($_POST['issue_description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <button type="button" class="btn btn-ai" id="aiClassifyBtn" onclick="classifyWithAI(this)">
                            <i class="fas fa-robot me-1"></i> Suggest Category & Priority with AI
                        </button>
                    </div>
                    
                    <!-- AI Suggestion Area -->
                    <div id="aiSuggestionArea" class="mb-3" style="display:none;"></div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="category_id" class="form-select" required>
                                <option value="">Select category...</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['category_id'] ? 'selected' : '' ?>><?= e($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Priority Level <span class="text-danger">*</span></label>
                            <select name="priority_level" id="priority_level" class="form-select" required>
                                <?php foreach (['Low','Medium','High','Critical'] as $p): ?>
                                <option value="<?= $p ?>" <?= ($_POST['priority_level'] ?? 'Medium') === $p ? 'selected' : '' ?>><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Submit Ticket</button>
                        <a href="<?= $baseUrl ?>/modules/tickets/index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-info-circle me-2"></i>Tips</div>
            <div class="card-body fs-sm text-muted">
                <p><strong>Title:</strong> Provide a concise summary of the issue.</p>
                <p><strong>Description:</strong> Include details like error messages, when the issue started, and what you've tried.</p>
                <p><strong>AI Classification:</strong> Click the AI button to get a suggested category and priority based on your description.</p>
                <p class="mb-0"><strong>Priority Guide:</strong></p>
                <ul class="mb-0">
                    <li><strong>Critical:</strong> System down, security breach</li>
                    <li><strong>High:</strong> Major function impaired</li>
                    <li><strong>Medium:</strong> Normal issue, workaround available</li>
                    <li><strong>Low:</strong> Minor issue, enhancement request</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function classifyWithAI(btn) {
    const title = document.getElementById('issue_title').value.trim();
    const desc = document.getElementById('issue_description').value.trim();
    if (!title || !desc) {
        alert('Please enter both title and description before using AI classification.');
        return;
    }
    
    aiAction(btn, '<?= $baseUrl ?>/modules/ai/classify_ticket.php', { title: title, description: desc }, function(data) {
        const area = document.getElementById('aiSuggestionArea');
        if (data.success && data.data) {
            const d = data.data;
            document.getElementById('ai_suggested_category').value = d.suggested_category || '';
            document.getElementById('ai_suggested_priority').value = d.suggested_priority || '';
            document.getElementById('ai_recommendation_reason').value = d.reason || '';
            
            // Set form fields
            const catSelect = document.getElementById('category_id');
            for (let opt of catSelect.options) {
                if (opt.text === d.suggested_category) { catSelect.value = opt.value; break; }
            }
            document.getElementById('priority_level').value = d.suggested_priority || 'Medium';
            
            area.style.display = 'block';
            area.innerHTML = '<div class="ai-suggestion-box">' +
                '<strong>Suggested Category:</strong> ' + (d.suggested_category || '—') + '<br>' +
                '<strong>Suggested Priority:</strong> ' + (d.suggested_priority || '—') + '<br>' +
                '<strong>Reason:</strong> ' + (d.reason || '—') +
                '<div class="ai-disclaimer">AI-generated suggestion. Please review before applying.</div>' +
                '</div>';
        } else {
            area.style.display = 'block';
            area.innerHTML = '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-circle me-1"></i>' + (data.error || 'AI classification failed.') + '</div>';
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
