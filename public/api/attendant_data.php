<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/Controllers/AttendantController.php';
require_auth('attendant');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

json_response([
    'success' => true,
    'data'    => AttendantController::dashboardData(),
]);
