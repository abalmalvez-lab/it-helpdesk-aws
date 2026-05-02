<?php
$pageTitle = 'Edit Ticket';
require_once __DIR__ . '/../../includes/header.php';
requireRole(['Admin']);

$pdo = getDBConnection();
$ticketId = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    setFlashMessage('error', 'Ticket not found.');
    header('Location: index.php');
    exit;
}

$categories = getCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $title = trim($_POST['issue_title'] ?? '');
    $description = trim($_POST['issue_description'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $priority = $_POST['priority_level'] ?? 'Medium';
    $status = $_POST['status'] ?? $ticket['status'];
    
    if (!empty($title) && !empty($description) && $categoryId > 0) {
        $pdo->prepare("UPDATE tickets SET issue_title = ?, issue_description = ?, category_id = ?, priority_level = ?, status = ?, sla_due_datetime = ?, updated_datetime = NOW() WHERE ticket_id = ?")
            ->execute([$title, $description, $categoryId, $priority, $status, calculateSLADue($categoryId, $priority, $ticket['created_datetime']), $ticketId]);
        
        logTicketAction($ticketId, getCurrentUserId(), 'Ticket Edited', $ticket['status'], $status, 'Ticket details updated by admin');
        setFlashMessage('success', 'Ticket updated successfully.');
        header("Location: view.php?id=$ticketId");
        exit;
    }
}
?>

<div class="page-header">
    <h1><i class="fas fa-edit me-2"></i>Edit Ticket</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Tickets</a></li>
            <li class="breadcrumb-item active">Edit <?= e($ticket['ticket_number']) ?></li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <?= csrfTokenField() ?>
            <div class="mb-3">
                <label class="form-label">Issue Title <span class="text-danger">*</span></label>
                <input type="text" name="issue_title" class="form-control" value="<?= e($ticket['issue_title']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Issue Description <span class="text-danger">*</span></label>
                <textarea name="issue_description" class="form-control" rows="5" required><?= e($ticket['issue_description']) ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select" required>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= $ticket['category_id'] == $cat['category_id'] ? 'selected' : '' ?>><?= e($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Priority</label>
                    <select name="priority_level" class="form-select">
                        <?php foreach (['Low','Medium','High','Critical'] as $p): ?>
                        <option value="<?= $p ?>" <?= $ticket['priority_level'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['Open','Assigned','In Progress','Escalated','Resolved','Closed'] as $s): ?>
                        <option value="<?= $s ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
                <a href="view.php?id=<?= $ticketId ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
