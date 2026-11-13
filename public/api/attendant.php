<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/Controllers/AttendantController.php';
require_auth('attendant');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$action = $input['action'] ?? ($_GET['action'] ?? '');

switch ($action) {
    case 'calculate':
        json_response(AttendantController::calculateTotal(
            (int) ($input['service_id'] ?? 0),
            $input['car_type'] ?? ''
        ));
        break;

    case 'check_in':
        json_response(AttendantController::checkIn($input));
        break;

    case 'mark_paid':
        json_response(AttendantController::markPaid(
            (int) ($input['transaction_id'] ?? 0),
            $input['payment_method'] ?? ''
        ));
        break;

    case 'paystack_initialize':
        json_response(AttendantController::initializePaystack(
            (int) ($input['transaction_id'] ?? 0)
        ));
        break;

    case 'paystack_verify':
        json_response(AttendantController::verifyPaystack(
            (string) ($input['reference'] ?? '')
        ));
        break;

    case 'complete_wash':
        json_response(AttendantController::completeWash(
            (int) ($input['transaction_id'] ?? 0)
        ));
        break;

    case 'send_receipt':
        json_response(AttendantController::sendReceipt($input));
        break;

    case 'download_receipt_pdf':
        AttendantController::downloadReceiptPdf(
            (int) ($input['transaction_id'] ?? ($_GET['transaction_id'] ?? 0))
        );
        break;

    default:
        json_response(['success' => false, 'message' => 'Unknown action.'], 400);
}
