<?php
/**
 * Entry point — send visitors to login or their role dashboard.
 */
require_once __DIR__ . '/../app/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    $target = ($_SESSION['role'] ?? '') === 'manager'
        ? '/manager.php'
        : '/attendant.php';
    header('Location: ' . $target);
    exit;
}

header('Location: /login.php');
exit;
