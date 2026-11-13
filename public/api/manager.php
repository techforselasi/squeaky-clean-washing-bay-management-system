<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/Controllers/ManagerController.php';
require_auth('manager');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_response([
        'success' => true,
        'data'    => ManagerController::dashboardData(),
    ]);
}

$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? '';

switch ($action) {
    case 'update_prices':
        json_response(ManagerController::updateServicePrices($input));
        break;

    case 'toggle_machinery':
        json_response(ManagerController::toggleMachinery(
            (int) ($input['machinery_id'] ?? 0)
        ));
        break;

    case 'add_machinery':
        json_response(ManagerController::addMachinery($input));
        break;

    case 'remove_machinery':
        json_response(ManagerController::removeMachinery(
            (int) ($input['machinery_id'] ?? 0)
        ));
        break;

    case 'revenue_report':
        json_response(ManagerController::revenueReport($input));
        break;

    case 'list_attendants':
        json_response(ManagerController::attendantsData());
        break;

    case 'create_attendant':
        json_response(ManagerController::createAttendant($input));
        break;

    case 'set_attendant_status':
        json_response(ManagerController::setAttendantStatus(
            (int) ($input['user_id'] ?? 0),
            $input['user_status'] ?? ''
        ));
        break;

    case 'reset_attendant_password':
        json_response(ManagerController::resetAttendantPassword($input));
        break;

    default:
        json_response(['success' => false, 'message' => 'Unknown action.'], 400);
}
