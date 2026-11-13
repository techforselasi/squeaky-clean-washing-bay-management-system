<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machinery — Squeaky Clean</title>
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
                <h2>Machinery</h2>
                <p>Toggle equipment between Active and Under Repair.</p>
            </div>

            <section class="card" id="machinery">
                <div class="card-header">
                    <h3>Equipment Registry</h3>
                </div>

                <form class="add-equipment-form" id="add-equipment-form">
                    <div class="form-group">
                        <label for="machine_name">Add new equipment</label>
                        <div class="add-equipment-row">
                            <input
                                class="form-control"
                                type="text"
                                id="machine_name"
                                name="machine_name"
                                placeholder="e.g. Steam Cleaner"
                                required
                                maxlength="100"
                            >
                            <button class="btn btn-add-equipment" type="button" id="add-equipment-btn">Add Equipment</button>
                        </div>
                    </div>
                </form>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Machine</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="machinery-body">
                            <tr>
                                <td colspan="3" class="empty-state">Loading equipment...</td>
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
