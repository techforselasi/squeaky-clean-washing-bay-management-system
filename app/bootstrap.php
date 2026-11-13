<?php
/**
 * Shared bootstrap: starts session, loads DB helper, and simple helpers.
 * Include this at the top of every public PHP entry point.
 */

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paystack.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config/sms.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Paystack.php';
require_once __DIR__ . '/GhanaPlate.php';
require_once __DIR__ . '/ReceiptSender.php';
require_once __DIR__ . '/ReceiptPdf.php';

/**
 * Send a JSON response and stop the script.
 */
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Require the visitor to be logged in with an optional role.
 */
function require_auth(?string $role = null): void
{
    if (empty($_SESSION['user_id'])) {
        if (is_api_request()) {
            json_response(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        header('Location: /login.php');
        exit;
    }

    if ($role !== null && ($_SESSION['role'] ?? '') !== $role) {
        if (is_api_request()) {
            json_response(['success' => false, 'message' => 'Forbidden'], 403);
        }
        header('Location: /login.php');
        exit;
    }
}

/**
 * Detect whether the current request is an API call.
 */
function is_api_request(): bool
{
    $path = $_SERVER['SCRIPT_NAME'] ?? '';
    return str_contains($path, '/api/');
}

/**
 * Escape output for safe HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
