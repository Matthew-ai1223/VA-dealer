<?php
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';
requireAdmin();

require_once __DIR__ . '/../models/Setting.php';

$settingModel = new Setting();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. General Settings
    $siteName = trim($_POST['site_name'] ?? '');
    $siteTagline = trim($_POST['site_tagline'] ?? '');
    $whatsappNumber = trim($_POST['whatsapp_number'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    
    // 2. Theme Setting
    $adminTheme = $_POST['admin_theme'] ?? 'slate';
    if (!in_array($adminTheme, ['slate', 'dark', 'blue', 'emerald'], true)) {
        $adminTheme = 'slate';
    }

    // 3. Widget Visibility Settings
    $showVisitors = isset($_POST['dash_show_visitors']) ? '1' : '0';
    $showLeads = isset($_POST['dash_show_leads']) ? '1' : '0';
    $showRevenue = isset($_POST['dash_show_revenue']) ? '1' : '0';
    $showConvRate = isset($_POST['dash_show_conv_rate']) ? '1' : '0';
    $showHotLeads = isset($_POST['dash_show_hot_leads']) ? '1' : '0';
    $showWAClicks = isset($_POST['dash_show_whatsapp_clicks']) ? '1' : '0';
    
    // 4. Chart Visibility Settings
    $showChartSources = isset($_POST['dash_show_chart_sources']) ? '1' : '0';
    $showChartSales = isset($_POST['dash_show_chart_sales']) ? '1' : '0';
    $showChartFunnel = isset($_POST['dash_show_chart_funnel']) ? '1' : '0';

    if (empty($siteName)) {
        $error = 'Site Name is required.';
    } else {
        $settingModel->set('site_name', $siteName);
        $settingModel->set('site_tagline', $siteTagline);
        $settingModel->set('whatsapp_number', $whatsappNumber);
        $settingModel->set('admin_email', $adminEmail);
        $settingModel->set('admin_theme', $adminTheme);
        
        $settingModel->set('dash_show_visitors', $showVisitors);
        $settingModel->set('dash_show_leads', $showLeads);
        $settingModel->set('dash_show_revenue', $showRevenue);
        $settingModel->set('dash_show_conv_rate', $showConvRate);
        $settingModel->set('dash_show_hot_leads', $showHotLeads);
        $settingModel->set('dash_show_whatsapp_clicks', $showWAClicks);
        
        $settingModel->set('dash_show_chart_sources', $showChartSources);
        $settingModel->set('dash_show_chart_sales', $showChartSales);
        $settingModel->set('dash_show_chart_funnel', $showChartFunnel);

        $_SESSION['flash_success'] = 'Settings saved successfully. Refreshing theme context...';
        header('Location: dashboard-settings.php');
        exit;
    }
}

$currentSettings = $settingModel->getAll();

// Get setting value helper
function getSettingVal(array $settings, string $key, $default = '') {
    return isset($settings[$key]) ? $settings[$key] : $default;
}

if (!empty($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

$pageTitle = 'Dashboard & Site Settings';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert--success"><?= sanitize($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert--error"><?= sanitize($error) ?></div>
<?php endif; ?>

<div class="admin-toolbar">
    <h2>Dashboard & Site Configuration</h2>
</div>

<form method="POST" class="admin-form" style="max-width: 1000px; margin-bottom: 40px;">
    
    <!-- SECTION 1: Site Branding & Info -->
    <div style="margin-bottom: 32px; border-bottom: 1px solid var(--admin-border-color, #e2e8f0); padding-bottom: 24px;">
        <h3 style="margin-bottom: 16px; display:flex; align-items:center; gap:8px">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            Site Branding & Contact Details
        </h3>
        
        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Site Name *</label>
                <input type="text" name="site_name" class="form-control" placeholder="e.g. VA Auto Sales" value="<?= sanitize(getSettingVal($currentSettings, 'site_name', $config['site_name'])) ?>" required>
            </div>
            
            <div class="form-group">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Site Tagline</label>
                <input type="text" name="site_tagline" class="form-control" placeholder="e.g. Premium Pre-Owned Vehicles" value="<?= sanitize(getSettingVal($currentSettings, 'site_tagline', $config['site_tagline'])) ?>">
            </div>
            
            <div class="form-group">
                <label style="display:block; font-weight:600; margin-bottom:6px;">WhatsApp Phone Number</label>
                <input type="text" name="whatsapp_number" class="form-control" placeholder="e.g. 2348110575847" value="<?= sanitize(getSettingVal($currentSettings, 'whatsapp_number', $config['whatsapp_number'])) ?>">
                <small class="text-muted">Use international format without + or spaces (e.g. 2348110575847).</small>
            </div>

            <div class="form-group">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Admin Contact Email</label>
                <input type="email" name="admin_email" class="form-control" placeholder="e.g. admin@vaautosales.com" value="<?= sanitize(getSettingVal($currentSettings, 'admin_email', $config['admin_email'])) ?>">
                <small class="text-muted">For lead notifications and dashboard correspondence.</small>
            </div>
        </div>
    </div>

    <!-- SECTION 2: Layout Options & Custom Theme -->
    <div style="margin-bottom: 32px; border-bottom: 1px solid var(--admin-border-color, #e2e8f0); padding-bottom: 24px;">
        <h3 style="margin-bottom: 16px; display:flex; align-items:center; gap:8px">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"/><path d="M12 18C15.3137 18 18 15.3137 18 12C18 8.68629 15.3137 6 12 6C8.68629 6 6 8.68629 6 12C6 15.3137 8.68629 18 12 18Z"/></svg>
            Admin Workspace Customization
        </h3>
        
        <div class="form-group" style="max-width: 480px; margin-bottom: 20px;">
            <label style="display:block; font-weight:600; margin-bottom:6px;">Admin Panel Theme Color</label>
            <select name="admin_theme" class="form-control">
                <option value="slate" <?= getSettingVal($currentSettings, 'admin_theme') === 'slate' ? 'selected' : '' ?>>Default Slate (Light Mode)</option>
                <option value="dark" <?= getSettingVal($currentSettings, 'admin_theme') === 'dark' ? 'selected' : '' ?>>Sleek Dark Mode 🌙</option>
                <option value="blue" <?= getSettingVal($currentSettings, 'admin_theme') === 'blue' ? 'selected' : '' ?>>Classic Blue (Professional)</option>
                <option value="emerald" <?= getSettingVal($currentSettings, 'admin_theme') === 'emerald' ? 'selected' : '' ?>>Emerald Green (Premium Dealership)</option>
            </select>
        </div>
    </div>

    <!-- SECTION 3: Dashboard Widgets Toggles -->
    <div style="margin-bottom: 32px; padding-bottom: 24px;">
        <h3 style="margin-bottom: 8px; display:flex; align-items:center; gap:8px">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
            Executive Dashboard Layout
        </h3>
        <p class="text-muted" style="margin-bottom: 20px;">Toggle which analytics KPI cards and visual charts are visible on the admin dashboard page.</p>

        <h4 style="margin-bottom: 12px; font-weight: 700; color: var(--admin-text-main, #0f172a);">KPI Card Metrics</h4>
        <div class="form-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; border: 1px solid var(--admin-border-color, #e2e8f0); border-radius: 8px;">
                <input type="checkbox" name="dash_show_visitors" value="1" <?= getSettingVal($currentSettings, 'dash_show_visitors', '1') === '1' ? 'checked' : '' ?>>
                <span>Total Visitors</span>
            </label>
            
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; border: 1px solid var(--admin-border-color, #e2e8f0); border-radius: 8px;">
                <input type="checkbox" name="dash_show_leads" value="1" <?= getSettingVal($currentSettings, 'dash_show_leads', '1') === '1' ? 'checked' : '' ?>>
                <span>Total Leads</span>
            </label>
            
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; border: 1px solid var(--admin-border-color, #e2e8f0); border-radius: 8px;">
                <input type="checkbox" name="dash_show_revenue" value="1" <?= getSettingVal($currentSettings, 'dash_show_revenue', '1') === '1' ? 'checked' : '' ?>>
                <span>Closed Revenue</span>
            </label>
            
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; border: 1px solid var(--admin-border-color, #e2e8f0); border-radius: 8px;">
                <input type="checkbox" name="dash_show_conv_rate" value="1" <?= getSettingVal($currentSettings, 'dash_show_conv_rate', '1') === '1' ? 'checked' : '' ?>>
                <span>Conversion Rate</span>
            </label>
            
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; border: 1px solid var(--admin-border-color, #e2e8f0); border-radius: 8px;">
                <input type="checkbox" name="dash_show_hot_leads" value="1" <?= getSettingVal($currentSettings, 'dash_show_hot_leads', '1') === '1' ? 'checked' : '' ?>>
                <span>Hot Leads 🔥</span>
            </label>
            
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; border: 1px solid var(--admin-border-color, #e2e8f0); border-radius: 8px;">
                <input type="checkbox" name="dash_show_whatsapp_clicks" value="1" <?= getSettingVal($currentSettings, 'dash_show_whatsapp_clicks', '1') === '1' ? 'checked' : '' ?>>
                <span>WhatsApp Clicks</span>
            </label>
        </div>

        <h4 style="margin-bottom: 12px; font-weight: 700; color: var(--admin-text-main, #0f172a);">Charts & Visual Panels</h4>
        <div class="form-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; border: 1px solid var(--admin-border-color, #e2e8f0); border-radius: 8px;">
                <input type="checkbox" name="dash_show_chart_sources" value="1" <?= getSettingVal($currentSettings, 'dash_show_chart_sources', '1') === '1' ? 'checked' : '' ?>>
                <span>Leads by Source Chart</span>
            </label>
            
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; border: 1px solid var(--admin-border-color, #e2e8f0); border-radius: 8px;">
                <input type="checkbox" name="dash_show_chart_sales" value="1" <?= getSettingVal($currentSettings, 'dash_show_chart_sales', '1') === '1' ? 'checked' : '' ?>>
                <span>Sales Trend Chart</span>
            </label>
            
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; border: 1px solid var(--admin-border-color, #e2e8f0); border-radius: 8px;">
                <input type="checkbox" name="dash_show_chart_funnel" value="1" <?= getSettingVal($currentSettings, 'dash_show_chart_funnel', '1') === '1' ? 'checked' : '' ?>>
                <span>Conversion Funnel Chart</span>
            </label>
        </div>
    </div>

    <!-- SUBMIT -->
    <div style="margin-top: 12px;">
        <button type="submit" class="btn btn--primary btn--lg" style="min-width: 200px;">Save Settings</button>
    </div>

</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
