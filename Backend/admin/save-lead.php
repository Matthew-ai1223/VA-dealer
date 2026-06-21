<?php
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/Lead.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: leads.php');
    exit;
}

$leadId = (int) ($_POST['lead_id'] ?? 0);
$action = $_POST['action'] ?? '';
$leadModel = new Lead();

if (!$leadModel->getById($leadId)) {
    $_SESSION['flash_error'] = 'Lead not found.';
    header('Location: leads.php');
    exit;
}

if ($action === 'status') {
    $status = $_POST['status'] ?? '';
    $assigned = trim($_POST['assigned_to'] ?? '') ?: null;

    if ($leadModel->updateStatus($leadId, $status, $assigned)) {
        $_SESSION['flash_success'] = 'Lead status updated.';
    } else {
        $_SESSION['flash_error'] = 'Could not update status.';
    }
}

if ($action === 'note') {
    $note = trim($_POST['note'] ?? '');
    if ($note !== '') {
        $leadModel->addNote($leadId, $_SESSION['admin_username'] ?? 'admin', $note);
        $_SESSION['flash_success'] = 'Note added.';
    } else {
        $_SESSION['flash_error'] = 'Note cannot be empty.';
    }
}

header('Location: lead-detail.php?id=' . $leadId);
exit;
