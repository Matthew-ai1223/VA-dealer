<?php
/**
 * Dynamic theme CSS overrides based on active setting
 */
$config = appConfig();
$theme = $config['admin_theme'] ?? 'slate';

if ($theme === 'dark'): ?>
<style>
    :root {
        --admin-bg: #0f172a;
        --admin-card-bg: #1e293b;
        --admin-border-color: #334155;
        --admin-text-main: #f8fafc;
        --admin-text-muted: #94a3b8;
        --admin-header-bg: #020617;
        --admin-footer-bg: #020617;
        --admin-input-bg: #1e293b;
        --admin-input-border: #475569;
        --admin-input-text: #f8fafc;
        --admin-table-hover: #1e293b;
    }
    
    body.admin-body {
        background: var(--admin-bg);
        color: var(--admin-text-main);
    }
    .admin-header {
        background: var(--admin-header-bg) !important;
    }
    .admin-footer {
        background: var(--admin-footer-bg) !important;
        border-color: var(--admin-border-color) !important;
        color: var(--admin-text-muted) !important;
    }
    .stat-card, .crm-panel, .admin-form, .table-responsive, .kanban-col, .kanban-card {
        background: var(--admin-card-bg) !important;
        color: var(--admin-text-main) !important;
        border-color: var(--admin-border-color) !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
    }
    .stat-card__value, h1, h2, h3, h4, th, td, label, legend, .lead-notes h4, .lead-notes p {
        color: var(--admin-text-main) !important;
    }
    .stat-card__label, .text-muted, .lead-note__meta, .campaign-card__meta {
        color: var(--admin-text-muted) !important;
    }
    input, select, textarea, .form-control {
        background: var(--admin-input-bg) !important;
        color: var(--admin-input-text) !important;
        border-color: var(--admin-input-border) !important;
    }
    .table th, .table td {
        border-color: var(--admin-border-color) !important;
    }
    .table tbody tr:hover {
        background-color: var(--admin-table-hover) !important;
    }
    .table-responsive {
        border: 1px solid var(--admin-border-color) !important;
    }
    .toast {
        background: var(--admin-card-bg) !important;
        border-color: var(--admin-border-color) !important;
        color: var(--admin-text-main) !important;
    }
</style>
<?php elseif ($theme === 'blue'): ?>
<style>
    :root {
        --admin-bg: #f0f4f8;
        --admin-card-bg: #ffffff;
        --admin-border-color: #d9e2ec;
        --admin-text-main: #102a43;
        --admin-text-muted: #627d98;
        --admin-header-bg: #0b69a3;
        --admin-footer-bg: #102a43;
        --admin-input-bg: #ffffff;
        --admin-input-border: #bcccdc;
        --admin-input-text: #102a43;
    }
    body.admin-body {
        background: var(--admin-bg);
        color: var(--admin-text-main);
    }
    .admin-header {
        background: var(--admin-header-bg) !important;
    }
    .admin-header__brand:hover { color: #e1f5fe !important; }
    .admin-nav a { color: rgba(255,255,255,0.85) !important; }
    .admin-nav a:hover { color: #fff !important; }
</style>
<?php elseif ($theme === 'emerald'): ?>
<style>
    :root {
        --admin-bg: #f4f7f6;
        --admin-card-bg: #ffffff;
        --admin-border-color: #e1e8e6;
        --admin-text-main: #064e3b;
        --admin-text-muted: #34d399;
        --admin-header-bg: #065f46;
        --admin-footer-bg: #064e3b;
    }
    body.admin-body {
        background: var(--admin-bg);
        color: var(--admin-text-main);
    }
    .admin-header {
        background: var(--admin-header-bg) !important;
    }
    .admin-header__brand:hover { color: #a7f3d0 !important; }
    .admin-nav a { color: rgba(255,255,255,0.85) !important; }
    .admin-nav a:hover { color: #fff !important; }
</style>
<?php endif; ?>
