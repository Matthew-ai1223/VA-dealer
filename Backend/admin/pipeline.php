<?php
$pageTitle = 'Sales Pipeline';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../models/Lead.php';

$leadModel = new Lead();

// Fetch all leads (no pagination, since Kanban boards usually show all active leads or a larger set)
$result = $leadModel->getAll([], 1, 100);
$leads = $result['items'] ?? [];

// Define pipeline columns in order
$columns = [
    'new'                  => ['label' => 'New Lead', 'color' => '#3b82f6'],
    'contacted'            => ['label' => 'Contacted', 'color' => '#6366f1'],
    'interested'           => ['label' => 'Interested', 'color' => '#ec4899'],
    'inspection_scheduled' => ['label' => 'Inspection Scheduled', 'color' => '#14b8a6'],
    'negotiating'          => ['label' => 'Negotiating', 'color' => '#f59e0b'],
    'closed_won'           => ['label' => 'Closed Won', 'color' => '#10b981'],
    'closed_lost'          => ['label' => 'Closed Lost', 'color' => '#ef4444']
];

// Group leads by status
$groupedLeads = [];
foreach (array_keys($columns) as $status) {
    $groupedLeads[$status] = [];
}
foreach ($leads as $lead) {
    $status = $lead['status'] ?? 'new';
    if (isset($groupedLeads[$status])) {
        $groupedLeads[$status][] = $lead;
    } else {
        $groupedLeads['new'][] = $lead;
    }
}
?>

<div class="admin-toolbar">
    <h2>Dealership Sales Pipeline</h2>
    <p class="text-warning" style="font-size:0.875rem; margin:0;">Drag and drop cards to update lead stages in real-time.</p>
</div>

<div class="pipeline-board">
    <?php foreach ($columns as $statusKey => $col): ?>
    <div class="pipeline-column" data-status="<?= sanitize($statusKey) ?>" ondragover="allowDrop(event)" ondrop="drop(event, this)">
        <div class="pipeline-column__header" style="border-top: 4px solid <?= $col['color'] ?>">
            <h3>
                <?= sanitize($col['label']) ?>
                <span class="pipeline-column__count"><?= count($groupedLeads[$statusKey]) ?></span>
            </h3>
        </div>
        <div class="pipeline-column__cards">
            <?php foreach ($groupedLeads[$statusKey] as $lead): ?>
                <?php
                $cat = $lead['lead_category'] ?? 'cold';
                $score = (int) ($lead['lead_score'] ?? 0);
                $badgeClass = $cat === 'hot' ? 'badge--danger' : ($cat === 'warm' ? 'badge--warning' : 'badge--muted');
                ?>
                <div 
                    class="pipeline-card" 
                    id="lead-card-<?= (int) $lead['id'] ?>" 
                    data-lead-id="<?= (int) $lead['id'] ?>"
                    draggable="true" 
                    ondragstart="drag(event)"
                >
                    <div class="pipeline-card__meta">
                        <span class="badge <?= $badgeClass ?>"><?= sanitize(strtoupper($cat)) ?> (<?= $score ?>)</span>
                        <span class="pipeline-card__date"><?= date('M j', strtotime($lead['created_at'])) ?></span>
                    </div>
                    <h4 class="pipeline-card__name"><?= sanitize($lead['full_name']) ?></h4>
                    <p class="pipeline-card__vehicle"><?= sanitize($lead['interested_vehicle']) ?></p>
                    
                    <div class="pipeline-card__footer">
                        <a href="tel:<?= sanitize($lead['phone_number']) ?>" class="pipeline-card__phone" title="Call Customer">
                            📞 <?= sanitize($lead['phone_number']) ?>
                        </a>
                        <a href="lead-detail.php?id=<?= (int) $lead['id'] ?>" class="btn btn--sm btn--outline" style="padding: 2px 8px; font-size: 0.75rem;">View</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<style>
.pipeline-board {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 16px;
    align-items: start;
    overflow-x: auto;
    padding-bottom: 20px;
    min-height: 70vh;
}

.pipeline-column {
    background: #f8fafc;
    border-radius: 12px;
    padding: 12px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-height: 450px;
    border: 1px solid #e2e8f0;
}

.pipeline-column__header {
    padding-top: 8px;
    margin-bottom: 4px;
}

.pipeline-column__header h3 {
    font-size: 0.875rem;
    font-weight: 700;
    color: #334155;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0;
}

.pipeline-column__count {
    background: #e2e8f0;
    color: #475569;
    border-radius: 999px;
    padding: 2px 8px;
    font-size: 0.75rem;
    font-weight: 600;
}

.pipeline-column__cards {
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex-grow: 1;
    min-height: 380px;
}

.pipeline-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px;
    box-shadow: 0 2px 4px rgba(15, 23, 42, 0.03);
    cursor: grab;
    transition: transform 0.2s, box-shadow 0.2s;
}

.pipeline-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
}

.pipeline-card:active {
    cursor: grabbing;
}

.pipeline-card__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.pipeline-card__date {
    font-size: 0.75rem;
    color: #94a3b8;
}

.pipeline-card__name {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 4px;
}

.pipeline-card__vehicle {
    font-size: 0.8125rem;
    color: #475569;
    margin: 0 0 10px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pipeline-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-top: 1px solid #f1f5f9;
    padding-top: 8px;
}

.pipeline-card__phone {
    font-size: 0.75rem;
    color: #2563eb;
    font-weight: 500;
    text-decoration: none;
}
.pipeline-card__phone:hover {
    text-decoration: underline;
}

.badge--danger { background: #fef2f2; color: #b91c1c; }
.badge--warning { background: #fffbeb; color: #d97706; }

@media (max-width: 1200px) {
    .pipeline-board {
        grid-template-columns: repeat(3, 300px);
        overflow-x: auto;
    }
}
</style>

<script>
function drag(ev) {
    ev.dataTransfer.setData("text/plain", ev.target.id);
    ev.target.style.opacity = "0.5";
}

function allowDrop(ev) {
    ev.preventDefault();
}

function drop(ev, el) {
    ev.preventDefault();
    var id = ev.dataTransfer.getData("text/plain");
    var card = document.getElementById(id);
    if (!card) return;

    card.style.opacity = "1";
    
    var destinationColumn = el.querySelector(".pipeline-column__cards");
    destinationColumn.appendChild(card);

    // Update counts
    updateColumnCounts();

    // Trigger API request to save status
    var leadId = card.getAttribute("data-lead-id");
    var newStatus = el.getAttribute("data-status");
    updateLeadStatus(leadId, newStatus, card);
}

function updateColumnCounts() {
    document.querySelectorAll(".pipeline-column").forEach(function(col) {
        var count = col.querySelectorAll(".pipeline-card").length;
        col.querySelector(".pipeline-column__count").textContent = count;
    });
}

function updateLeadStatus(leadId, status, cardEl) {
    var apiUrl = '<?= url("Backend/api/leads.php") ?>?id=' + leadId;
    
    fetch(apiUrl, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status: status })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            // Success feedback animation
            cardEl.style.backgroundColor = "#ecfdf5";
            setTimeout(function() {
                cardEl.style.backgroundColor = "#fff";
            }, 1000);
            
            // If moved to Closed Won, show toast or congrats
            if (status === 'closed_won') {
                showToastNotification("Sale Closed! 🎉", "Lead " + data.data.full_name + " marked as Won!");
            }
        } else {
            alert('Failed to update lead status: ' + data.message);
            window.location.reload(); // Revert card position on failure
        }
    })
    .catch(function() {
        alert('Connection error occurred while updating pipeline.');
        window.location.reload();
    });
}

// Fallback helper for toast alerts
function showToastNotification(title, message) {
    if (window.VA_ADMIN_NOTIFY) {
        window.VA_ADMIN_NOTIFY.show(title, message, "success");
    } else {
        alert(title + "\n" + message);
    }
}

document.querySelectorAll(".pipeline-card").forEach(function(card) {
    card.addEventListener("dragend", function() {
        card.style.opacity = "1";
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
