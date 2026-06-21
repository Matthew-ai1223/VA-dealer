<?php
$pageTitle = 'Email Outreach & Campaigns';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../models/Lead.php';

$leadModel = new Lead();
$db = Database::getConnection();

// Handle Form Submission
$flashMessage = "";
$flashClass = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_campaign') {
    $title = trim($_POST['title'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $templateType = $_POST['template_type'] ?? 'promo';
    $segment = $_POST['audience_segment'] ?? 'all';

    if ($title === '' || $subject === '' || $body === '') {
        $flashMessage = "All campaign fields (title, subject, body) are required.";
        $flashClass = "alert--error";
    } else {
        try {
            // Find target leads based on segment
            $sql = "SELECT * FROM leads WHERE email IS NOT NULL AND email != ''";
            $params = [];

            if ($segment === 'hot') {
                $sql .= " AND lead_category = 'hot'";
            } elseif ($segment === 'warm') {
                $sql .= " AND lead_category = 'warm'";
            } elseif ($segment === 'cold') {
                $sql .= " AND lead_category = 'cold'";
            } elseif (in_array($segment, Lead::STATUSES, true)) {
                $sql .= " AND status = ?";
                $params[] = $segment;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $targetLeads = $stmt->fetchAll();

            if (empty($targetLeads)) {
                $flashMessage = "No leads found matching the selected audience segment.";
                $flashClass = "alert--error";
            } else {
                // 1. Insert Campaign
                $stmtInsertCampaign = $db->prepare("INSERT INTO email_campaigns (title, subject, body, template_type, audience_segment, status, sent_at) VALUES (?, ?, ?, ?, ?, 'sent', CURRENT_TIMESTAMP)");
                $stmtInsertCampaign->execute([$title, $subject, $body, $templateType, $segment]);
                $campaignId = (int) $db->lastInsertId();

                $sentCount = 0;
                $failedCount = 0;

                // 2. Loop and Send
                $stmtLog = $db->prepare("INSERT INTO email_logs (campaign_id, lead_id, email, status) VALUES (?, ?, ?, ?)");
                
                foreach ($targetLeads as $lead) {
                    $recipientEmail = $lead['email'];
                    
                    // Replace variables
                    $personalBody = str_replace(
                        ['{name}', '{vehicle}'],
                        [$lead['full_name'], $lead['interested_vehicle']],
                        $body
                    );

                    // Add HTML wrapping based on template styling for premium feel
                    $emailSubject = str_replace(['{name}', '{vehicle}'], [$lead['full_name'], $lead['interested_vehicle']], $subject);
                    
                    require_once __DIR__ . '/../lib/mail.php';

                    $htmlBody = '
<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
  <div style="background:#1e40af;padding:20px 28px;">
    <h2 style="color:#fff;margin:0;font-size:18px;">VA Auto Sales</h2>
  </div>
  <div style="padding:28px;background:#f8fafc;font-size:15px;line-height:1.7;color:#0f172a;">
    ' . nl2br(htmlspecialchars($personalBody, ENT_QUOTES)) . '
  </div>
  <div style="padding:16px 28px;background:#fff;border-top:1px solid #e2e8f0;font-size:12px;color:#94a3b8;text-align:center;">
    VA Auto Sales · Nigeria<br>
    <a href="' . fullUrl('Frontend/listings.php') . '" style="color:#1e40af;">Browse our listings</a>
  </div>
</div>';

                    $sent = sendMail($recipientEmail, $emailSubject, $personalBody, $htmlBody);

                    if ($sent) {
                        $sentCount++;
                        $stmtLog->execute([$campaignId, $lead['id'], $recipientEmail, 'sent']);
                    } else {
                        $failedCount++;
                        $stmtLog->execute([$campaignId, $lead['id'], $recipientEmail, 'failed']);
                    }
                }

                $flashMessage = "Campaign \"$title\" sent successfully! Total sent: $sentCount. Failed: $failedCount.";
                $flashClass = "alert--success";
            }
        } catch (Exception $e) {
            $flashMessage = "Error sending campaign: " . $e->getMessage();
            $flashClass = "alert--error";
        }
    }
}

// Fetch Campaign History
$campaignsStmt = $db->query("SELECT c.*, 
    (SELECT COUNT(*) FROM email_logs WHERE campaign_id = c.id AND status = 'sent') as sent_count,
    (SELECT COUNT(*) FROM email_logs WHERE campaign_id = c.id AND status = 'failed') as failed_count
    FROM email_campaigns c ORDER BY c.created_at DESC");
$campaignHistory = $campaignsStmt->fetchAll();

// Get counts for segments to show in frontend
$segmentsInfo = [
    'all'         => (int) $db->query("SELECT COUNT(*) FROM leads WHERE email IS NOT NULL AND email != ''")->fetchColumn(),
    'hot'         => (int) $db->query("SELECT COUNT(*) FROM leads WHERE email IS NOT NULL AND email != '' AND lead_category = 'hot'")->fetchColumn(),
    'warm'        => (int) $db->query("SELECT COUNT(*) FROM leads WHERE email IS NOT NULL AND email != '' AND lead_category = 'warm'")->fetchColumn(),
    'cold'        => (int) $db->query("SELECT COUNT(*) FROM leads WHERE email IS NOT NULL AND email != '' AND lead_category = 'cold'")->fetchColumn(),
    'new'         => (int) $db->query("SELECT COUNT(*) FROM leads WHERE email IS NOT NULL AND email != '' AND status = 'new'")->fetchColumn(),
    'negotiating' => (int) $db->query("SELECT COUNT(*) FROM leads WHERE email IS NOT NULL AND email != '' AND status = 'negotiating'")->fetchColumn()
];
?>

<div class="admin-toolbar">
    <h2>Email Automation & Campaign Outreach</h2>
</div>

<?php if ($flashMessage): ?>
    <div class="alert <?= $flashClass ?>"><?= sanitize($flashMessage) ?></div>
<?php endif; ?>

<div class="crm-detail-grid">
    <div class="crm-detail-main">
        <form method="POST" class="admin-form">
            <input type="hidden" name="action" value="send_campaign">
            
            <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 800;">Create Campaign</h3>

            <div class="form-grid">
                <div class="form-group">
                    <label for="title">Campaign Title (Internal)</label>
                    <input type="text" id="title" name="title" required placeholder="e.g. June Camry Price Drop Alert">
                </div>
                <div class="form-group">
                    <label for="template_type">Template Style</label>
                    <select id="template_type" name="template_type" onchange="applyTemplatePreset()">
                        <option value="new_arrival">New Arrival Alert</option>
                        <option value="promo" selected>Dealership Promotion</option>
                        <option value="price_reduction">Price Drop Alert</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="audience_segment">Target Audience Segment</label>
                    <select id="audience_segment" name="audience_segment" onchange="updateSegmentCounter()">
                        <option value="all">All Subscribers (<?= $segmentsInfo['all'] ?> leads)</option>
                        <option value="hot">Hot Leads Category (<?= $segmentsInfo['hot'] ?> leads)</option>
                        <option value="warm">Warm Leads Category (<?= $segmentsInfo['warm'] ?> leads)</option>
                        <option value="cold">Cold Leads Category (<?= $segmentsInfo['cold'] ?> leads)</option>
                        <option value="new">New Leads Status (<?= $segmentsInfo['new'] ?> leads)</option>
                        <option value="negotiating">Negotiating Leads Status (<?= $segmentsInfo['negotiating'] ?> leads)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="subject">Email Subject</label>
                    <input type="text" id="subject" name="subject" required placeholder="Subject..." value="Special Pre-owned Deal: {vehicle} for you!">
                </div>
                
                <div class="form-group form-group--full">
                    <label for="body">Email Body (Markdown/Plain Text)</label>
                    <div style="font-size:0.75rem; color:#94a3b8; margin-bottom:4px;">Supported variables: <strong>{name}</strong>, <strong>{vehicle}</strong></div>
                    <textarea id="body" name="body" rows="10" required placeholder="Type email message here..."></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary" onclick="return confirm('Are you sure you want to send this campaign instantly?')">Send Campaign Now</button>
            </div>
        </form>
        
        <div class="crm-panel" style="margin-top: 24px;">
            <h3 style="margin-top:0;">Outreach History</h3>
            <?php if (empty($campaignHistory)): ?>
                <p class="crm-empty">No email campaigns sent yet.</p>
            <?php else: ?>
                <div class="table-responsive" style="box-shadow:none; border: 1px solid #e2e8f0;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Template</th>
                                <th>Segment</th>
                                <th>Delivery Success</th>
                                <th>Date Sent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaignHistory as $camp): ?>
                            <tr>
                                <td style="font-weight: 700;"><?= sanitize($camp['title']) ?></td>
                                <td><span class="badge badge--source"><?= sanitize(ucwords(str_replace('_', ' ', $camp['template_type']))) ?></span></td>
                                <td><span class="badge badge--status"><?= sanitize(ucfirst($camp['audience_segment'])) ?></span></td>
                                <td>
                                    <span style="color:#059669; font-weight:600;"><?= (int) $camp['sent_count'] ?> sent</span> 
                                    <?php if ($camp['failed_count'] > 0): ?>
                                        · <span style="color:#ef4444;"><?= (int) $camp['failed_count'] ?> failed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M j, Y H:i', strtotime($camp['sent_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="crm-detail-sidebar">
        <div class="crm-panel">
            <h3 style="margin-top: 0;">Live Email Preview</h3>
            <div id="preview-box" style="border:1px solid #e2e8f0; border-radius:8px; padding:16px; background:#fafafa; font-family:monospace; font-size:0.8125rem; white-space:pre-wrap; min-height: 250px;">
                Subject: <span id="preview-subject" style="font-weight:700;"></span>
                <hr style="border:0; border-top:1px solid #e2e8f0; margin:10px 0;">
                <span id="preview-body"></span>
            </div>
        </div>
    </div>
</div>

<script>
var presets = {
    new_arrival: {
        subject: "New Arrival Alert: 2022 Toyota Camry XSE now available!",
        body: "Hi {name},\n\nWe are excited to notify you that a new vehicle matching our premium pre-owned inventory has just arrived!\n\nListing: {vehicle}\nCheck details here: " + window.location.origin + "/Frontend/listings.php\n\nContact us on WhatsApp to book an inspection before it's gone!\n\nBest regards,\nVA Auto Sales Team"
    },
    promo: {
        subject: "Exclusive Pre-owned Deals just for you, {name}!",
        body: "Hi {name},\n\nLooking for a quality car? We are offering special discounts this weekend on all our listings, including the {vehicle} you viewed!\n\nOur pre-owned vehicles undergo 150-point inspections, giving you total peace of mind.\n\nReply to this email or speak to a sales rep on WhatsApp to get our updated inventory price list!\n\nBest regards,\nVA Auto Sales Team"
    },
    price_reduction: {
        subject: "Price Drop Notification: {vehicle} has been reduced!",
        body: "Hi {name},\n\nGreat news! The price of the {vehicle} has just been reduced.\n\nIf you were waiting for the right deal, now is the perfect time to make a decision.\n\nWhatsApp us immediately to secure this listing:\nhttps://wa.me/" + '<?= appConfig()["whatsapp_number"] ?>' + "\n\nBest regards,\nVA Auto Sales Team"
    }
};

function applyTemplatePreset() {
    var type = document.getElementById("template_type").value;
    var preset = presets[type];
    if (preset) {
        document.getElementById("subject").value = preset.subject;
        document.getElementById("body").value = preset.body;
        updatePreview();
    }
}

function updatePreview() {
    var sub = document.getElementById("subject").value;
    var b = document.getElementById("body").value;
    
    // Mock replaces
    var mockName = "Chidi Egwu";
    var mockVehicle = "2022 Toyota Camry XSE";
    
    var subRep = sub.replace(/{name}/g, mockName).replace(/{vehicle}/g, mockVehicle);
    var bodyRep = b.replace(/{name}/g, mockName).replace(/{vehicle}/g, mockVehicle);
    
    document.getElementById("preview-subject").textContent = subRep;
    document.getElementById("preview-body").textContent = bodyRep;
}

document.getElementById("subject").addEventListener("input", updatePreview);
document.getElementById("body").addEventListener("input", updatePreview);

// Run initial loading
document.addEventListener("DOMContentLoaded", function() {
    applyTemplatePreset();
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
