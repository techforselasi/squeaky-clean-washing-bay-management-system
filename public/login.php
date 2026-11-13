<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

if (!empty($_SESSION['user_id'])) {
    $target = ($_SESSION['role'] ?? '') === 'manager'
        ? '/manager.php'
        : '/attendant.php';
    header('Location: ' . $target);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $result   = AuthController::login($username, $password);

    if ($result['success']) {
        header('Location: ' . $result['redirect']);
        exit;
    }

    $_SESSION['login_error'] = $result['message'];
    header('Location: /login.php');
    exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

require __DIR__ . '/../resources/views/login.blade.php';
