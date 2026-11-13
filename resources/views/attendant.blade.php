<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendant Desk — Squeaky Clean</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="app-shell">
        <?php require __DIR__ . '/partials/attendant_sidebar.blade.php'; ?>

        <main class="main-area">
            <header class="topbar">
                <div class="search-box">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input type="search" id="job-search" placeholder="Search plate or washer..." autocomplete="off">
                    <span class="kbd">⌘F</span>
                </div>
                <div class="topbar-right">
                    <div class="icon-btn" title="Notifications">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>
                        </svg>
                    </div>
                    <div class="user-chip">
                        <?php $username = $_SESSION['username'] ?? 'Attendant'; ?>
                        <div class="avatar"><?= e(strtoupper(substr($username, 0, 1))) ?></div>
                        <div class="meta">
                            <strong><?= e($username) ?></strong>
                            <span>Attendant</span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="page-heading">
                <h2>Attendant Desk</h2>
                <p>Check in vehicles, assign washers, and process payments.</p>
            </div>

            <section class="card" id="jobs">
                <div class="card-header">
                    <h3>Jobs &amp; Today's Invoices</h3>
                    <span class="badge badge-amber" id="job-count">0 active</span>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Plate</th>
                                <th>Type</th>
                                <th>Service</th>
                                <th>Bay</th>
                                <th>Washer</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Wash</th>
                                <th class="no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody id="jobs-body">
                            <tr>
                                <td colspan="9" class="empty-state">No active jobs yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <?php require __DIR__ . '/partials/receipt_modal.blade.php'; ?>

    <div class="toast" id="toast"></div>

    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="/js/attendant.js"></script>
</body>
</html>
