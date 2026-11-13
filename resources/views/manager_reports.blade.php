<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Reports — Squeaky Clean</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="app-shell">
        <?php require __DIR__ . '/partials/manager_sidebar.blade.php'; ?>

        <main class="main-area">
            <header class="topbar no-print">
                <div class="search-box">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input type="search" placeholder="Search reports..." autocomplete="off">
                    <span class="kbd">⌘F</span>
                </div>
                <div class="topbar-right">
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

            <div class="page-heading no-print">
                <h2>Revenue Reports</h2>
                <p>Every Cash and MoMo payment is stored. Generate, download, or print a money report.</p>
            </div>

            <section class="card no-print" id="report-filters">
                <div class="card-header">
                    <h3>Report period</h3>
                </div>
                <form class="report-filter-form" id="report-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="from_date">From</label>
                            <input class="form-control" type="date" id="from_date" name="from_date" required>
                        </div>
                        <div class="form-group">
                            <label for="to_date">To</label>
                            <input class="form-control" type="date" id="to_date" name="to_date" required>
                        </div>
                    </div>
                    <div class="report-presets">
                        <button class="btn btn-secondary" type="button" data-preset="today">Today</button>
                        <button class="btn btn-secondary" type="button" data-preset="week">This week</button>
                        <button class="btn btn-secondary" type="button" data-preset="month">This month</button>
                        <button class="btn btn-primary" type="submit">Generate report</button>
                    </div>
                </form>
            </section>

            <div id="report-output" class="report-output section-gap" hidden>
                <div class="report-actions no-print">
                    <button class="btn btn-secondary" type="button" id="download-csv-btn">Download CSV</button>
                    <button class="btn btn-primary" type="button" id="print-report-btn">Print report</button>
                </div>

                <article class="card report-sheet" id="report-sheet">
                    <div class="report-sheet-header">
                        <div>
                            <h3>Squeaky Clean Washing Bay</h3>
                            <p class="report-subtitle">Revenue &amp; payments report</p>
                        </div>
                        <div class="report-period" id="report-period-label">—</div>
                    </div>

                    <div class="grid-4 report-metrics" id="report-metrics">
                        <div class="metric-card accent-blue">
                            <div class="label">Total revenue</div>
                            <div class="value" id="report-total">GH₵ 0.00</div>
                        </div>
                        <div class="metric-card featured metric-money">
                            <div class="label">Cash</div>
                            <div class="value" id="report-cash">GH₵ 0.00</div>
                        </div>
                        <div class="metric-card">
                            <div class="label">MoMo</div>
                            <div class="value" id="report-momo">GH₵ 0.00</div>
                        </div>
                        <div class="metric-card attention">
                            <div class="label">Payments</div>
                            <div class="value" id="report-count">0</div>
                        </div>
                    </div>

                    <div class="table-wrap section-gap">
                        <table class="data-table" id="report-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Paid at</th>
                                    <th>Plate</th>
                                    <th>Vehicle</th>
                                    <th>Service</th>
                                    <th>Washer</th>
                                    <th>Bay</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody id="report-body">
                                <tr>
                                    <td colspan="9" class="empty-state">Generate a report to see payments.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="report-footer">
                        <span>Generated for manager review</span>
                        <strong id="report-footer-total">Total: GH₵ 0.00</strong>
                    </div>
                </article>
            </div>
        </main>
    </div>

    <div class="toast" id="toast"></div>
    <script src="/js/manager_reports.js"></script>
</body>
</html>
