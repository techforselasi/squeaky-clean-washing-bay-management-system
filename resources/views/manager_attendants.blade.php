<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendant Accounts — Squeaky Clean</title>
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
                    <input type="search" id="attendant-search" placeholder="Search username..." autocomplete="off">
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

            <div class="page-heading">
                <h2>Attendant Accounts</h2>
                <p>Only managers can create, deactivate, or reset attendant login access.</p>
            </div>

            <section class="card" id="add-attendant">
                <div class="card-header">
                    <h3>Add attendant</h3>
                    <span class="badge badge-blue">Manager only</span>
                </div>
                <form class="intake-form" id="add-attendant-form" autocomplete="off">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_username">Username</label>
                            <input class="form-control" type="text" id="new_username" name="username" placeholder="e.g. ama_desk" required minlength="3" maxlength="50" pattern="[A-Za-z0-9._\-]+">
                        </div>
                        <div class="form-group">
                            <label for="new_password">Temporary password</label>
                            <div class="password-field">
                                <input class="form-control" type="password" id="new_password" name="password" required minlength="6">
                                <button class="password-toggle" type="button" data-toggle-password="new_password" aria-label="Show password">
                                    <svg class="icon-eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                    <svg class="icon-eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" hidden><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="new_password_confirm">Confirm password</label>
                            <div class="password-field">
                                <input class="form-control" type="password" id="new_password_confirm" name="password_confirm" required minlength="6">
                                <button class="password-toggle" type="button" data-toggle-password="new_password_confirm" aria-label="Show password">
                                    <svg class="icon-eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                    <svg class="icon-eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" hidden><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="action-row">
                        <button class="btn btn-primary" type="submit">Create account</button>
                    </div>
                </form>
            </section>

            <section class="card section-gap" id="attendant-list">
                <div class="card-header">
                    <h3>Attendant logins</h3>
                    <span class="badge badge-amber" id="attendant-count">0 accounts</span>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="attendants-body">
                            <tr>
                                <td colspan="3" class="empty-state">Loading accounts...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card section-gap" id="reset-password-card" hidden>
                <div class="card-header">
                    <h3>Reset password</h3>
                    <button class="btn btn-secondary" type="button" id="cancel-reset-btn">Cancel</button>
                </div>
                <p class="hint-text" id="reset-target-label">Set a new temporary password.</p>
                <form class="intake-form" id="reset-password-form" autocomplete="off">
                    <input type="hidden" id="reset_user_id" name="user_id" value="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reset_password">New password</label>
                            <div class="password-field">
                                <input class="form-control" type="password" id="reset_password" required minlength="6">
                                <button class="password-toggle" type="button" data-toggle-password="reset_password" aria-label="Show password">
                                    <svg class="icon-eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                    <svg class="icon-eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" hidden><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="reset_password_confirm">Confirm password</label>
                            <div class="password-field">
                                <input class="form-control" type="password" id="reset_password_confirm" required minlength="6">
                                <button class="password-toggle" type="button" data-toggle-password="reset_password_confirm" aria-label="Show password">
                                    <svg class="icon-eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                    <svg class="icon-eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" hidden><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="action-row">
                        <button class="btn btn-primary" type="submit">Save new password</button>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <div class="toast" id="toast"></div>
    <script src="/js/manager_attendants.js"></script>
</body>
</html>
