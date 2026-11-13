<?php
/**
 * Attendant sidebar. Set $activeNav before including:
 * 'dashboard' | 'intake' | 'bays'
 */
$activeNav = $activeNav ?? 'dashboard';
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-mark">SC</div>
        <div>
            <strong>Squeaky Clean</strong>
            <span>Washing Bay Ops</span>
        </div>
    </div>

    <div class="nav-label">Menu</div>
    <a class="nav-link<?= $activeNav === 'dashboard' ? ' active' : '' ?>" href="/attendant.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 12l9-9 9 9"/><path d="M5 10v10h14V10"/>
        </svg>
        Dashboard
    </a>
    <a class="nav-link<?= $activeNav === 'intake' ? ' active' : '' ?>" href="/attendant_intake.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/>
        </svg>
        Vehicle Intake
    </a>
    <a class="nav-link<?= $activeNav === 'bays' ? ' active' : '' ?>" href="/attendant_bays.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 7h16v12H4z"/><path d="M8 7V5h8v2"/>
        </svg>
        Bay Status
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
        <h4>Front Desk Mode</h4>
        <p>Log plates, assign bays & washers, then collect Cash or MoMo.</p>
        <a class="btn btn-primary" href="/attendant_intake.php" id="focus-intake-btn">New Check-In</a>
    </div>
</aside>
