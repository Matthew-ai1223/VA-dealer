<?php
$pageTitle = 'Executive Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../models/Car.php';
require_once __DIR__ . '/../models/Lead.php';

$carModel = new Car();
$leadModel = new Lead();
$db = Database::getConnection();

// --- 1. Query Executive Metrics ---

// Total Visitors
$totalVisitors = (int) $db->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
$totalVisitors = max(1, $totalVisitors); // Avoid divide by zero

// Total Leads
$totalLeads = (int) $db->query("SELECT COUNT(*) FROM leads")->fetchColumn();

// Total Sales (Closed Won leads)
$totalSales = (int) $db->query("SELECT COUNT(*) FROM leads WHERE status = 'closed_won'")->fetchColumn();

// Conversion Rate
$conversionRate = round(($totalLeads / $totalVisitors) * 100, 1);

// Revenue Tracking (sum of budgets of closed won leads)
$revenue = (float) $db->query("SELECT SUM(budget) FROM leads WHERE status = 'closed_won'")->fetchColumn();
$revenueFormatted = formatPrice($revenue);

// Hot Leads count
$hotLeadsCount = (int) $db->query("SELECT COUNT(*) FROM leads WHERE lead_category = 'hot'")->fetchColumn();

// WhatsApp Clicks (Engagement)
$whatsAppClicks = (int) $db->query("SELECT COUNT(*) FROM lead_activities WHERE activity_type = 'whatsapp_click'")->fetchColumn();

// --- 2. Query Chart Data ---

// Leads by Source
$sourceCounts = ['website' => 0, 'nairaland' => 0, 'instagram' => 0, 'facebook' => 0, 'whatsapp' => 0];
$sourceStmt = $db->query("SELECT source, COUNT(*) as count FROM leads GROUP BY source");
foreach ($sourceStmt->fetchAll() as $row) {
    if (isset($sourceCounts[$row['source']])) {
        $sourceCounts[$row['source']] = (int) $row['count'];
    }
}

// Sales Trend (Closed Won leads over the last 10 days)
$salesTrend = [];
$trendStmt = $db->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m-%d') as date, COUNT(*) as count 
    FROM leads 
    WHERE status = 'closed_won' 
    GROUP BY date 
    ORDER BY date ASC 
    LIMIT 10
");
foreach ($trendStmt->fetchAll() as $row) {
    $salesTrend[$row['date']] = (int) $row['count'];
}
// Fill in today if empty
$todayStr = date('Y-m-d');
if (empty($salesTrend)) {
    $salesTrend[$todayStr] = 0;
}

// Conversion Funnel calculation
$funnel = [
    'Visitors'    => $totalVisitors,
    'Leads'       => $totalLeads,
    'Contacted'   => (int) $db->query("SELECT COUNT(*) FROM leads WHERE status != 'new'")->fetchColumn(),
    'Interested'  => (int) $db->query("SELECT COUNT(*) FROM leads WHERE status IN ('interested', 'inspection_scheduled', 'negotiating', 'closed_won')")->fetchColumn(),
    'Negotiating' => (int) $db->query("SELECT COUNT(*) FROM leads WHERE status IN ('negotiating', 'closed_won')")->fetchColumn(),
    'Closed Won'  => $totalSales
];

// Top Hot Leads
$hotLeadsStmt = $db->query("SELECT * FROM leads WHERE lead_category = 'hot' ORDER BY lead_score DESC, created_at DESC LIMIT 5");
$hotLeadsList = $hotLeadsStmt->fetchAll();

// Recent Activities
$recentActStmt = $db->query("
    SELECT a.*, l.full_name as lead_name 
    FROM lead_activities a 
    LEFT JOIN leads l ON a.lead_id = l.id 
    ORDER BY a.created_at DESC 
    LIMIT 6
");
$recentActivities = $recentActStmt->fetchAll();

// Read toggle configurations (defaulting to true/1)
$showVisitors = ($config['dash_show_visitors'] ?? '1') === '1';
$showLeads = ($config['dash_show_leads'] ?? '1') === '1';
$showRevenue = ($config['dash_show_revenue'] ?? '1') === '1';
$showConvRate = ($config['dash_show_conv_rate'] ?? '1') === '1';
$showHotLeads = ($config['dash_show_hot_leads'] ?? '1') === '1';
$showWAClicks = ($config['dash_show_whatsapp_clicks'] ?? '1') === '1';

$showChartSources = ($config['dash_show_chart_sources'] ?? '1') === '1';
$showChartSales = ($config['dash_show_chart_sales'] ?? '1') === '1';
$showChartFunnel = ($config['dash_show_chart_funnel'] ?? '1') === '1';
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="admin-toolbar">
    <h2>Executive Analytics Dashboard</h2>
    <span class="badge badge--success" style="padding: 6px 12px; font-weight:700;">Live Updates</span>
</div>

<!-- Executive KPI Metrics Grid -->
<div class="admin-stats admin-stats--5" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
    <?php if ($showVisitors): ?>
    <div class="stat-card" style="border-left-color: #3b82f6;">
        <span class="stat-card__value"><?= $totalVisitors ?></span>
        <span class="stat-card__label">Total Visitors</span>
    </div>
    <?php endif; ?>
    
    <?php if ($showLeads): ?>
    <div class="stat-card" style="border-left-color: #8b5cf6;">
        <span class="stat-card__value"><?= $totalLeads ?></span>
        <span class="stat-card__label">Total Leads</span>
    </div>
    <?php endif; ?>
    
    <?php if ($showRevenue): ?>
    <div class="stat-card" style="border-left-color: #10b981;">
        <span class="stat-card__value"><?= $revenueFormatted ?></span>
        <span class="stat-card__label">Closed Revenue</span>
    </div>
    <?php endif; ?>
    
    <?php if ($showConvRate): ?>
    <div class="stat-card" style="border-left-color: #f59e0b;">
        <span class="stat-card__value"><?= $conversionRate ?>%</span>
        <span class="stat-card__label">Conversion Rate</span>
    </div>
    <?php endif; ?>
    
    <?php if ($showHotLeads): ?>
    <div class="stat-card" style="border-left-color: #ef4444;">
        <span class="stat-card__value"><?= $hotLeadsCount ?></span>
        <span class="stat-card__label">Hot Leads 🔥</span>
    </div>
    <?php endif; ?>
    
    <?php if ($showWAClicks): ?>
    <div class="stat-card" style="border-left-color: #06b6d4;">
        <span class="stat-card__value"><?= $whatsAppClicks ?></span>
        <span class="stat-card__label">WhatsApp Clicks</span>
    </div>
    <?php endif; ?>
</div>

<!-- Charts Grid -->
<?php if ($showChartSources || $showChartSales): ?>
<div class="crm-grid" style="grid-template-columns: <?= ($showChartSources && $showChartSales) ? '1fr 1fr' : '1fr' ?>; margin-bottom: 24px;">
    <?php if ($showChartSources): ?>
    <div class="crm-panel">
        <h3>Leads by Source</h3>
        <div style="max-height: 250px; display: flex; justify-content: center;">
            <canvas id="chartSources" style="max-width: 250px; max-height: 250px;"></canvas>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($showChartSales): ?>
    <div class="crm-panel">
        <h3>Sales Trend (Closed Won)</h3>
        <div style="height: 250px;">
            <canvas id="chartSalesTrend" style="height: 250px; width: 100%;"></canvas>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="crm-grid" style="grid-template-columns: <?= $showChartFunnel ? '1fr 1fr' : '1fr' ?>; margin-bottom: 24px;">
    <?php if ($showChartFunnel): ?>
    <div class="crm-panel">
        <h3>Conversion Funnel</h3>
        <div style="height: 250px;">
            <canvas id="chartFunnel" style="height: 250px; width: 100%;"></canvas>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="crm-panel">
        <h3>Vehicle Views vs Engagement Clicks</h3>
        <?php require __DIR__ . '/includes/vehicle-analytics.php'; ?>
    </div>
</div>

<!-- Detailed Data Grid -->
<div class="crm-grid" style="grid-template-columns: 1.2fr 0.8fr;">
    <div class="crm-panel">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="margin:0;">High Priority Hot Leads</h3>
            <a href="leads.php?status=interested" class="btn btn--sm btn--outline">View CRM Leads</a>
        </div>
        
        <?php if (empty($hotLeadsList)): ?>
            <p class="crm-empty">No hot leads active at the moment.</p>
        <?php else: ?>
            <div class="table-responsive" style="box-shadow:none; border:1px solid #e2e8f0;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Vehicle</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hotLeadsList as $hLead): ?>
                        <tr>
                            <td style="font-weight: 700;"><?= sanitize($hLead['full_name']) ?></td>
                            <td><?= sanitize($hLead['interested_vehicle']) ?></td>
                            <td><span class="badge badge--danger">HOT (<?= (int) $hLead['lead_score'] ?>)</span></td>
                            <td><span class="badge badge--status badge--<?= sanitize($hLead['status']) ?>"><?= sanitize(ucwords(str_replace('_', ' ', $hLead['status']))) ?></span></td>
                            <td><a href="lead-detail.php?id=<?= (int) $hLead['id'] ?>" class="btn btn--sm btn--outline">View Profile</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="crm-panel">
        <h3>Recent Interactions & Activities</h3>
        <?php if (empty($recentActivities)): ?>
            <p class="crm-empty">No activity tracked yet.</p>
        <?php else: ?>
            <ul class="crm-timeline">
                <?php foreach ($recentActivities as $act): ?>
                <li>
                    <strong>
                        <?= $act['lead_name'] ? sanitize($act['lead_name']) : 'Anonymous Visitor' ?>
                    </strong> 
                    <?= str_replace('_', ' ', sanitize($act['activity_type'])) ?>
                    <small><?= date('M j, g:i a', strtotime($act['created_at'])) ?> (IP: <?= sanitize($act['ip_address']) ?>)</small>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<script>
// 1. Leads by Source Doughnut
var ctxSources = document.getElementById('chartSources');
if (ctxSources) {
    new Chart(ctxSources, {
        type: 'doughnut',
        data: {
            labels: ['Website', 'Nairaland', 'Instagram', 'Facebook', 'WhatsApp'],
            datasets: [{
                data: [
                    <?= $sourceCounts['website'] ?>,
                    <?= $sourceCounts['nairaland'] ?>,
                    <?= $sourceCounts['instagram'] ?>,
                    <?= $sourceCounts['facebook'] ?>,
                    <?= $sourceCounts['whatsapp'] ?>
                ],
                backgroundColor: ['#3b82f6', '#10b981', '#ec4899', '#4f46e5', '#f59e0b'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

// 2. Sales Trend Line
var ctxSalesTrend = document.getElementById('chartSalesTrend');
if (ctxSalesTrend) {
    new Chart(ctxSalesTrend, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($salesTrend)) ?>,
            datasets: [{
                label: 'Sales (Closed Won)',
                data: <?= json_encode(array_values($salesTrend)) ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.3,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
}

// 3. Funnel Chart
var ctxFunnel = document.getElementById('chartFunnel');
if (ctxFunnel) {
    new Chart(ctxFunnel, {
        type: 'bar',
        data: {
            labels: ['Visitors', 'Leads', 'Contacted', 'Interested', 'Negotiating', 'Won'],
            datasets: [{
                label: 'Conversion Stage Counts',
                data: [
                    <?= $funnel['Visitors'] ?>,
                    <?= $funnel['Leads'] ?>,
                    <?= $funnel['Contacted'] ?>,
                    <?= $funnel['Interested'] ?>,
                    <?= $funnel['Negotiating'] ?>,
                    <?= $funnel['Closed Won'] ?>
                ],
                backgroundColor: ['#64748b', '#3b82f6', '#6366f1', '#a855f7', '#f59e0b', '#10b981']
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
