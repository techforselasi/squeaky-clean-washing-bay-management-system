<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Intake — Squeaky Clean</title>
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
                    <input type="search" placeholder="Search plate or washer..." autocomplete="off">
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
                <h2>Vehicle Intake</h2>
                <p>Log plates, assign bays & washers, then bill the customer.</p>
            </div>

            <section class="card" id="intake">
                <div class="card-header">
                    <h3>Vehicle Intake</h3>
                    <span class="badge badge-blue">Live Counter</span>
                </div>

                <form class="intake-form" id="intake-form" novalidate>
                    <div class="form-group">
                        <label for="license_plate">License Plate</label>
                        <input class="form-control" type="text" id="license_plate" name="license_plate" placeholder="e.g. GR 2345-26" maxlength="12" autocomplete="off" required>
                        <p class="field-hint" id="plate-hint">Use a Ghana plate (e.g. GR 2345-26) or a custom plate with letters and numbers</p>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="car_type">Car Type</label>
                            <select class="form-control fancy-native" id="car_type" name="car_type" required>
                                <option value="">Select type</option>
                                <option value="Sedan">Sedan</option>
                                <option value="SUV">SUV</option>
                                <option value="Van">Van</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="service_id">Service Package</label>
                            <select class="form-control fancy-native" id="service_id" name="service_id" required>
                                <option value="">Select service</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="bay_id">Assign Bay</label>
                            <select class="form-control fancy-native" id="bay_id" name="bay_id" required>
                                <option value="">Select bay</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="washer_id">Assign Washer</label>
                            <select class="form-control fancy-native" id="washer_id" name="washer_id" data-fancy-avatars="true" data-empty-message="All washers are busy at the moment" required>
                                <option value="">Select washer</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="customer_phone">Customer phone <span class="optional-label">(optional)</span></label>
                        <input class="form-control" type="tel" id="customer_phone" name="customer_phone" placeholder="e.g. 0241234567" autocomplete="off">
                        <p class="field-hint">Used later to send the receipt by SMS or WhatsApp.</p>
                    </div>

                    <div class="total-box">
                        <span>Estimated Total</span>
                        <strong id="total-display">GH₵ 0.00</strong>
                    </div>

                    <div class="action-row">
                        <button class="btn btn-intake-secondary" type="button" id="clear-intake-btn">Clear</button>
                        <button class="btn btn-intake-primary" type="submit">Check In &amp; Bill</button>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <?php require __DIR__ . '/partials/receipt_modal.blade.php'; ?>

    <div class="toast" id="toast"></div>

    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="/js/attendant.js"></script>
</body>
</html>
