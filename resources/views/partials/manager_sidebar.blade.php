<?php
/**
 * Manager sidebar. Set $activeNav before including:
 * 'dashboard' | 'reports' | 'attendants' | 'pricing' | 'machinery' | 'performance'
 */
$activeNav = $activeNav ?? 'dashboard';
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-mark">SC</div>
        <div>
            <strong>Squeaky Clean</strong>
            <span>Manager Console</span>
        </div>
    </div>

    <div class="nav-label">Menu</div>
    <a class="nav-link<?= $activeNav === 'dashboard' ? ' active' : '' ?>" href="/manager.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 12l9-9 9 9"/><path d="M5 10v10h14V10"/>
        </svg>
        Dashboard
    </a>
    <a class="nav-link<?= $activeNav === 'reports' ? ' active' : '' ?>" href="/manager_reports.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/>
        </svg>
        Revenue Reports
    </a>
    <a class="nav-link<?= $activeNav === 'attendants' ? ' active' : '' ?>" href="/manager_attendants.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        Attendants
    </a>
    <a class="nav-link<?= $activeNav === 'pricing' ? ' active' : '' ?>" href="/manager_pricing.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
        </svg>
        Pricing
    </a>
    <a class="nav-link<?= $activeNav === 'machinery' ? ' active' : '' ?>" href="/manager_machinery.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9c0 .7.4 1.3 1 1.5H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>
        </svg>
        Machinery
    </a>
    <a class="nav-link<?= $activeNav === 'performance' ? ' active' : '' ?>" href="/manager_performance.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        Performance
    </a>

    <div class="sidebar-spacer"></div>

    <div class="nav-label">General</div>
    <a class="nav-link" href="/logout.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>
        </svg>
        Logout
    </a>

    <div class="sidebar-card">
        <h4>Operations Pulse</h4>
        <p>Track revenue, keep prices current, and keep equipment online.</p>
    </div>
</aside>
