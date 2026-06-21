<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../models/Lead.php';

$leadModel = new Lead();
$id = (int) ($_GET['id'] ?? 0);
$lead = $leadModel->getById($id);

if (!$lead) {
    $_SESSION['flash_error'] = 'Lead not found.';
    header('Location: leads.php');
    exit;
}

$pageTitle = 'Lead — ' . $lead['full_name'];
$notes = $leadModel->getNotes($id);
$activities = $leadModel->getActivities($id);
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert--success"><?= sanitize($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert--error"><?= sanitize($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="admin-toolbar">
    <h2>Lead Details</h2>
    <a href="leads.php" class="btn btn--outline btn--sm">← Back to Leads</a>
</div>

<div class="crm-detail-grid">
    <div class="crm-detail-main">
        <div class="admin-form">
            <h3>Customer Information</h3>
            <dl class="crm-info-list">
                <div><dt>Name</dt><dd><?= sanitize($lead['full_name']) ?></dd></div>
                <div><dt>Phone</dt><dd><a href="tel:<?= sanitize(preg_replace('/\s+/', '', $lead['phone_number'])) ?>"><?= sanitize($lead['phone_number']) ?></a></dd></div>
                <div><dt>Email</dt><dd><?= $lead['email'] ? sanitize($lead['email']) : '—' ?></dd></div>
                <div><dt>Vehicle</dt><dd><?= sanitize($lead['interested_vehicle']) ?></dd></div>
                <div><dt>Budget</dt><dd><?= $lead['budget_formatted'] ? sanitize($lead['budget_formatted']) : '—' ?></dd></div>
                <div><dt>Inquiry Type</dt><dd><?= sanitize($lead['inquiry_label']) ?></dd></div>
                <div><dt>Source</dt><dd><?= sanitize($lead['source_label']) ?></dd></div>
                <div><dt>Submitted</dt><dd><?= sanitize(date('M j, Y g:i A', strtotime($lead['created_at']))) ?></dd></div>
            </dl>

            <?php if (!empty($lead['message'])): ?>
            <h3>Message</h3>
            <p class="crm-message"><?= nl2br(sanitize($lead['message'])) ?></p>
            <?php endif; ?>
        </div>

        <div class="admin-form">
            <h3>Internal Notes</h3>
            <?php if (empty($notes)): ?>
                <p class="crm-empty">No notes yet.</p>
            <?php else: ?>
                <ul class="crm-notes">
                    <?php foreach ($notes as $note): ?>
                    <li>
                        <p><?= nl2br(sanitize($note['note'])) ?></p>
                        <small><?= sanitize($note['admin_username']) ?> · <?= sanitize(date('M j, Y g:i A', strtotime($note['created_at']))) ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="POST" action="save-lead.php" class="crm-note-form">
                <input type="hidden" name="lead_id" value="<?= (int) $lead['id'] ?>">
                <input type="hidden" name="action" value="note">
                <div class="form-group">
                    <label for="note">Add Note</label>
                    <textarea id="note" name="note" rows="3" required placeholder="Follow-up details, call summary…"></textarea>
                </div>
                <button type="submit" class="btn btn--primary btn--sm">Save Note</button>
            </form>
        </div>
    </div>

    <aside class="crm-detail-sidebar">
        <div class="admin-form">
            <h3>Update Status</h3>
            <form method="POST" action="save-lead.php">
                <input type="hidden" name="lead_id" value="<?= (int) $lead['id'] ?>">
                <input type="hidden" name="action" value="status">
                <div class="form-group">
                    <label for="status">Pipeline Status</label>
                    <select id="status" name="status" required>
                        <?php foreach (Lead::STATUSES as $s): ?>
                        <option value="<?= sanitize($s) ?>" <?= $lead['status'] === $s ? 'selected' : '' ?>><?= sanitize(ucwords(str_replace('_', ' ', $s))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assigned_to">Assigned To</label>
                    <input type="text" id="assigned_to" name="assigned_to" value="<?= sanitize($lead['assigned_to'] ?? '') ?>" placeholder="Admin name">
                </div>
                <button type="submit" class="btn btn--primary btn--block">Update Status</button>
            </form>
        </div>

        <div class="admin-form">
            <h3>Activity History</h3>
            <?php if (empty($activities)): ?>
                <p class="crm-empty">No tracked activities.</p>
            <?php else: ?>
                <ul class="crm-timeline">
                    <?php foreach ($activities as $act): ?>
                    <li>
                        <strong><?= sanitize(ucwords(str_replace('_', ' ', $act['activity_type']))) ?></strong>
                        <small><?= sanitize(date('M j, Y g:i A', strtotime($act['created_at']))) ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
