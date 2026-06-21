<?php
$pageTitle = 'CRM — Leads';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../models/Lead.php';
require_once __DIR__ . '/../models/Car.php';

$leadModel = new Lead();
$carModel = new Car();

$filters = [
    'status' => $_GET['status'] ?? '',
    'source' => $_GET['source'] ?? '',
    'search' => trim($_GET['search'] ?? ''),
];
$page = max(1, (int) ($_GET['page'] ?? 1));
$result = $leadModel->getAll($filters, $page, 20);
$overview = $leadModel->getOverviewStats();
$sources = $leadModel->getSourceStats();
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert--success"><?= sanitize($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<div class="admin-stats admin-stats--5">
    <div class="stat-card">
        <span class="stat-card__value"><?= (int) ($overview['total'] ?? 0) ?></span>
        <span class="stat-card__label">Total Leads</span>
    </div>
    <div class="stat-card stat-card--info">
        <span class="stat-card__value"><?= (int) ($overview['new_leads'] ?? 0) ?></span>
        <span class="stat-card__label">New Leads</span>
    </div>
    <div class="stat-card stat-card--success">
        <span class="stat-card__value"><?= (int) ($overview['interested'] ?? 0) ?></span>
        <span class="stat-card__label">Interested</span>
    </div>
    <div class="stat-card stat-card--warning">
        <span class="stat-card__value"><?= (int) ($overview['negotiating'] ?? 0) ?></span>
        <span class="stat-card__label">Negotiating</span>
    </div>
    <div class="stat-card stat-card--success">
        <span class="stat-card__value"><?= (int) ($overview['closed_won'] ?? 0) ?></span>
        <span class="stat-card__label">Closed Won</span>
    </div>
</div>

<div class="crm-grid">
    <div class="crm-panel">
        <h3>Leads by Source</h3>
        <?php if (empty($sources)): ?>
            <p class="crm-empty">No lead data yet.</p>
        <?php else: ?>
            <ul class="crm-source-list">
                <?php foreach ($sources as $src): ?>
                <li>
                    <span class="crm-source-list__name"><?= sanitize(ucfirst($src['source'])) ?></span>
                    <span class="crm-source-list__count"><?= (int) $src['count'] ?> leads</span>
                    <span class="crm-source-list__rate"><?= (float) $src['conversion_rate'] ?>% won</span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/vehicle-analytics.php'; ?>

<div class="admin-toolbar">
    <h2>All Leads</h2>
    <div style="display: flex; gap: 10px;">
        <button type="button" class="btn btn--outline btn--sm" id="btn-simulate-facebook" style="border-color:#3b82f6; color:#3b82f6;">Simulate Facebook Lead</button>
        <button type="button" class="btn btn--outline btn--sm" id="btn-simulate-instagram" style="border-color:#ec4899; color:#ec4899;">Simulate Instagram Lead</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var fbBtn = document.getElementById('btn-simulate-facebook');
    var igBtn = document.getElementById('btn-simulate-instagram');
    var webhookUrl = '<?= url("Backend/api/meta-webhook.php") ?>';

    function simulateLead(source) {
        var names = ['Chidi Egwu', 'Fatima Bello', 'Olumide Johnson', 'Amara Okafor'];
        var randomName = names[Math.floor(Math.random() * names.length)];
        var randomPhone = '+234803' + Math.floor(1000000 + Math.random() * 9000000);
        var randomEmail = randomName.toLowerCase().replace(' ', '.') + '@example.com';
        
        var vehicles = ['2022 Toyota Camry XSE', '2021 Honda Accord Sport', '2020 Mercedes-Benz C300'];
        var randomVehicle = vehicles[Math.floor(Math.random() * vehicles.length)];

        var payload = {
            simulated: true,
            source: source,
            full_name: randomName + ' (Mock)',
            phone_number: randomPhone,
            email: randomEmail,
            interested_vehicle: randomVehicle,
            budget: Math.floor(12000000 + Math.random() * 8000000),
            message: 'I would like to check availability and schedule an inspection for this car from ' + source + ' ads.'
        };

        fetch(webhookUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                alert('Mock lead synced from Meta Ads: #' + data.lead_id + ' (' + randomName + ')');
                window.location.reload();
            } else {
                alert('Simulation failed: ' + data.message);
            }
        })
        .catch(function() {
            alert('Error calling webhook API');
        });
    }

    if (fbBtn) fbBtn.addEventListener('click', function() { simulateLead('facebook'); });
    if (igBtn) igBtn.addEventListener('click', function() { simulateLead('instagram'); });
});
</script>

<form method="GET" class="crm-filters admin-form admin-form--inline">
    <div class="form-group">
        <label for="search">Search</label>
        <input type="search" id="search" name="search" value="<?= sanitize($filters['search']) ?>" placeholder="Name, phone, vehicle…">
    </div>
    <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All statuses</option>
            <?php foreach (Lead::STATUSES as $s): ?>
            <option value="<?= sanitize($s) ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= sanitize(ucwords(str_replace('_', ' ', $s))) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="source">Source</label>
        <select id="source" name="source">
            <option value="">All sources</option>
            <?php foreach (Lead::SOURCES as $s): ?>
            <option value="<?= sanitize($s) ?>" <?= $filters['source'] === $s ? 'selected' : '' ?>><?= sanitize(ucfirst($s)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn--primary btn--sm">Filter</button>
    <?php if ($filters['search'] || $filters['status'] || $filters['source']): ?>
    <a href="leads.php" class="btn btn--outline btn--sm">Clear</a>
    <?php endif; ?>
</form>

<?php if (empty($result['items'])): ?>
    <div class="empty-state">
        <p>No leads found.</p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Vehicle</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result['items'] as $lead): ?>
                <tr>
                    <td><?= sanitize($lead['full_name']) ?></td>
                    <td><?= sanitize($lead['phone_number']) ?></td>
                    <td><?= sanitize($lead['interested_vehicle']) ?></td>
                    <td><span class="badge badge--source"><?= sanitize($lead['source_label']) ?></span></td>
                    <td><span class="badge badge--status badge--<?= sanitize($lead['status']) ?>"><?= sanitize($lead['status_label']) ?></span></td>
                    <td><?= sanitize(date('M j, Y', strtotime($lead['created_at']))) ?></td>
                    <td><a href="lead-detail.php?id=<?= (int) $lead['id'] ?>" class="btn btn--sm btn--outline">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($result['pages'] > 1): ?>
    <nav class="crm-pagination" aria-label="Leads pagination">
        <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
            <?php
            $qs = http_build_query(array_filter([
                'page'   => $p,
                'search' => $filters['search'],
                'status' => $filters['status'],
                'source' => $filters['source'],
            ]));
            ?>
            <a href="leads.php?<?= sanitize($qs) ?>" class="crm-pagination__link <?= $p === $page ? 'is-active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
