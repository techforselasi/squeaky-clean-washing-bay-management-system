<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard — Squeaky Clean</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="app-shell">
        <?php require __DIR__ . '/partials/manager_sidebar.blade.php'; ?>

        <main class="main-area">
            <header class="topbar">
                <div class="search-box">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input type="search" placeholder="Search reports..." autocomplete="off">
                    <span class="kbd">⌘F</span>
                </div>
                <div class="topbar-right">
                    <div class="icon-btn" title="Notifications">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>
                        </svg>
                    </div>
                    <div class="user-chip">
                        <?php $username = $_SESSION['username'] ?? 'Manager'; ?>
                        <div class="avatar"><?= e(strtoupper(substr($username, 0, 1))) ?></div>
                        <div class="meta">
                            <strong><?= e($username) ?></strong>
                            <span>Manager</span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="page-heading">
                <h2>Manager Dashboard</h2>
                <p>Daily performance, pricing control, and equipment status.</p>
            </div>

            <div class="grid-4" id="metrics-row">
                <div class="metric-card accent-blue">
                    <div class="label">Daily Revenue</div>
                    <div class="value" id="metric-daily">GH₵ 0.00</div>
                    <div class="hint">Paid transactions today</div>
                </div>
                <div class="metric-card featured metric-money">
                    <div class="label">Weekly Revenue</div>
                    <div class="value" id="metric-weekly">GH₵ 0.00</div>
                    <div class="hint">Since Monday</div>
                </div>
                <div class="metric-card">
                    <div class="label">Vehicles Washed</div>
                    <div class="value" id="metric-vehicles">0</div>
                    <div class="hint">Completed today</div>
                </div>
                <div class="metric-card attention">
                    <div class="label">Open Bays</div>
                    <div class="value" id="metric-bays">0</div>
                    <div class="hint">Ready for intake</div>
                </div>
            </div>

            <section class="card section-gap" id="recent">
                <div class="card-header">
                    <h3>Recent Paid Jobs</h3>
                    <a class="btn btn-secondary" href="/manager_reports.php">Full revenue report</a>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Plate</th>
                                <th>Service</th>
                                <th>Washer</th>
                                <th>Method</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody id="recent-body">
                            <tr>
                                <td colspan="5" class="empty-state">No paid jobs yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <div class="toast" id="toast"></div>
    <script src="/js/manager.js"></script>
</body>
</html>
