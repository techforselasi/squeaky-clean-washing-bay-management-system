<?php

require_once __DIR__ . '/../Models/Bay.php';
require_once __DIR__ . '/../Models/Service.php';
require_once __DIR__ . '/../Models/Washer.php';
require_once __DIR__ . '/../Models/Vehicle.php';
require_once __DIR__ . '/../Models/Transaction.php';

class AttendantController
{
    public static function dashboardData(): array
    {
        return [
            'bays'     => Bay::all(),
            'services' => Service::all(),
            'washers'  => Washer::allAvailable(),
            'jobs'     => Vehicle::activeJobs(),
        ];
    }

    public static function calculateTotal(int $serviceId, string $carType): array
    {
        $service = Service::find($serviceId);
        if (!$service) {
            return ['success' => false, 'message' => 'Service not found.'];
        }

        $allowed = ['Sedan', 'SUV', 'Van'];
        if (!in_array($carType, $allowed, true)) {
            return ['success' => false, 'message' => 'Invalid car type.'];
        }

        $total = Service::priceFor($service, $carType);

        return [
            'success'      => true,
            'service_name' => $service['service_name'],
            'car_type'     => $carType,
            'total'        => $total,
            'formatted'    => 'GH₵ ' . number_format($total, 2),
        ];
    }

    public static function checkIn(array $input): array
    {
        try {
            $plate     = GhanaPlate::normalize((string) ($input['license_plate'] ?? ''));
            $carType   = $input['car_type'] ?? '';
            $serviceId = (int) ($input['service_id'] ?? 0);
            $bayId     = (int) ($input['bay_id'] ?? 0);
            $washerId  = (int) ($input['washer_id'] ?? 0);

            if ($plate === '' || $serviceId < 1 || $bayId < 1 || $washerId < 1) {
                return ['success' => false, 'message' => 'Please fill in all fields.'];
            }

            if (!GhanaPlate::isValid($plate)) {
                return ['success' => false, 'message' => GhanaPlate::validationMessage()];
            }

            $bay = Bay::find($bayId);
            if (!$bay) {
                return ['success' => false, 'message' => 'Bay not found.'];
            }
            if ($bay['bay_status'] === 'occupied' || Bay::hasActiveWash($bayId)) {
                return ['success' => false, 'message' => 'That bay is already occupied.'];
            }

            $service = Service::find($serviceId);
            if (!$service) {
                return ['success' => false, 'message' => 'Service not found.'];
            }

            $washer = Washer::find($washerId);
            if (!$washer || $washer['washer_status'] !== 'active') {
                return ['success' => false, 'message' => 'Washer is not available.'];
            }
            if (Washer::isBusy($washerId)) {
                return ['success' => false, 'message' => 'That washer is already working on another vehicle.'];
            }

            $allowed = ['Sedan', 'SUV', 'Van'];
            if (!in_array($carType, $allowed, true)) {
                return ['success' => false, 'message' => 'Invalid car type.'];
            }

            $customerPhone = trim((string) ($input['customer_phone'] ?? ''));

            if ($customerPhone !== '' && !ReceiptSender::isValidPhone($customerPhone)) {
                return ['success' => false, 'message' => 'Enter a valid customer phone, or leave it blank.'];
            }

            $total     = Service::priceFor($service, $carType);
            $vehicleId = Vehicle::create(
                $plate,
                $carType,
                $bayId,
                $washerId,
                null,
                $customerPhone !== '' ? $customerPhone : null
            );
            $txnId     = Transaction::create($vehicleId, $serviceId, $total);
            $receipt   = Transaction::find($txnId);

            if (!$receipt) {
                return ['success' => false, 'message' => 'Checked in, but receipt could not be loaded.'];
            }

            return [
                'success' => true,
                'message' => 'Vehicle checked in successfully.',
                'receipt' => $receipt,
                'bays'    => Bay::all(),
                'washers' => Washer::allAvailable(),
                'jobs'    => Vehicle::activeJobs(),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Check-in failed: ' . $e->getMessage(),
            ];
        }
    }

    public static function markPaid(int $transactionId, string $method): array
    {
        try {
            // Cash is manual. MoMo must go through Paystack (paystack_initialize / paystack_verify).
            if ($method !== 'cash') {
                return ['success' => false, 'message' => 'Use Paystack MoMo for non-cash payments.'];
            }

            $txn = Transaction::find($transactionId);
            if (!$txn) {
                return ['success' => false, 'message' => 'Transaction not found.'];
            }

            if (($txn['payment_status'] ?? '') === 'paid') {
                return ['success' => false, 'message' => 'This job is already paid.'];
            }

            $bayName    = $txn['bay_name'];
            $washerName = $txn['washer_name'];

            // Payment only — does NOT free bay/washer (wash may still be in progress)
            Transaction::markPaid($transactionId, 'cash');

            $receipt = Transaction::find($transactionId);
            if ($receipt) {
                $receipt['bay_name']    = $bayName;
                $receipt['washer_name'] = $washerName;
            }

            $stillWashing = in_array($receipt['vehicle_status'] ?? '', ['pending', 'washing'], true);

            return [
                'success' => true,
                'message' => $stillWashing
                    ? 'Cash payment recorded. Bay and washer stay busy until the wash is marked done.'
                    : 'Cash payment recorded.',
                'receipt' => $receipt,
                'bays'    => Bay::all(),
                'washers' => Washer::allAvailable(),
                'jobs'    => Vehicle::activeJobs(),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Start a Paystack charge for MoMo (label stays MoMo in the UI).
     */
    public static function initializePaystack(int $transactionId): array
    {
        try {
            if (!Paystack::isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Paystack is not configured yet. Paste your test keys into config/paystack.php.',
                ];
            }

            $txn = Transaction::find($transactionId);
            if (!$txn) {
                return ['success' => false, 'message' => 'Transaction not found.'];
            }

            if (($txn['payment_status'] ?? '') === 'paid') {
                return ['success' => false, 'message' => 'This job is already paid.'];
            }

            $amountGhs = (float) ($txn['total_amount'] ?? 0);
            if ($amountGhs <= 0) {
                return ['success' => false, 'message' => 'Invalid amount for Paystack.'];
            }

            // Paystack expects the smallest currency unit (pesewas for GHS).
            $amountPesewas = (int) round($amountGhs * 100);
            $reference     = 'SCW-' . $transactionId . '-' . time();

            Transaction::savePaystackReference($transactionId, $reference);

            $payload = [
                'email'        => PAYSTACK_CHARGE_EMAIL,
                'amount'       => $amountPesewas,
                'currency'     => PAYSTACK_CURRENCY,
                'reference'    => $reference,
                'channels'     => ['mobile_money', 'card'],
                'metadata'     => [
                    'transaction_id' => $transactionId,
                    'license_plate'  => $txn['license_plate'] ?? '',
                    'service_name'   => $txn['service_name'] ?? '',
                ],
            ];

            $response = Paystack::initialize($payload);
            if (empty($response['status'])) {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Paystack could not start the payment.',
                ];
            }

            $data = $response['data'] ?? [];

            return [
                'success'     => true,
                'message'     => 'Paystack MoMo ready.',
                'public_key'  => PAYSTACK_PUBLIC_KEY,
                'email'       => PAYSTACK_CHARGE_EMAIL,
                'amount'      => $amountPesewas,
                'currency'    => PAYSTACK_CURRENCY,
                'reference'   => $reference,
                'access_code' => $data['access_code'] ?? null,
                'authorization_url' => $data['authorization_url'] ?? null,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Paystack init failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Confirm Paystack payment, then mark the invoice paid as MoMo.
     */
    public static function verifyPaystack(string $reference): array
    {
        try {
            if (!Paystack::isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Paystack is not configured yet. Paste your test keys into config/paystack.php.',
                ];
            }

            $reference = trim($reference);
            if ($reference === '') {
                return ['success' => false, 'message' => 'Missing Paystack reference.'];
            }

            $txn = Transaction::findByPaystackReference($reference);
            if (!$txn) {
                return ['success' => false, 'message' => 'No local invoice matches that Paystack reference.'];
            }

            if (($txn['payment_status'] ?? '') === 'paid') {
                return [
                    'success' => true,
                    'message' => 'Already paid.',
                    'receipt' => $txn,
                    'bays'    => Bay::all(),
                    'washers' => Washer::allAvailable(),
                    'jobs'    => Vehicle::activeJobs(),
                ];
            }

            $response = Paystack::verify($reference);
            if (empty($response['status'])) {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Paystack could not verify this payment.',
                ];
            }

            $data   = $response['data'] ?? [];
            $status = strtolower((string) ($data['status'] ?? ''));
            if ($status !== 'success') {
                return [
                    'success' => false,
                    'message' => 'Payment not completed yet (status: ' . ($data['status'] ?? 'unknown') . ').',
                ];
            }

            $paidPesewas = (int) ($data['amount'] ?? 0);
            $expected    = (int) round(((float) $txn['total_amount']) * 100);
            if ($paidPesewas !== $expected) {
                return [
                    'success' => false,
                    'message' => 'Paid amount does not match this invoice.',
                ];
            }

            $bayName    = $txn['bay_name'];
            $washerName = $txn['washer_name'];

            Transaction::markPaidFromPaystack((int) $txn['id'], $reference);

            $receipt = Transaction::find((int) $txn['id']);
            if ($receipt) {
                $receipt['bay_name']    = $bayName;
                $receipt['washer_name'] = $washerName;
            }

            $stillWashing = in_array($receipt['vehicle_status'] ?? '', ['pending', 'washing'], true);

            return [
                'success' => true,
                'message' => $stillWashing
                    ? 'MoMo payment confirmed via Paystack. Bay and washer stay busy until wash is done.'
                    : 'MoMo payment confirmed via Paystack.',
                'receipt' => $receipt,
                'bays'    => Bay::all(),
                'washers' => Washer::allAvailable(),
                'jobs'    => Vehicle::activeJobs(),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Paystack verify failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Mark wash finished — frees bay and washer. Independent of payment.
     */
    public static function completeWash(int $transactionId): array
    {
        try {
            $txn = Transaction::find($transactionId);
            if (!$txn) {
                return ['success' => false, 'message' => 'Transaction not found.'];
            }

            $vehicleId = (int) ($txn['vehicle_id'] ?? 0);
            if ($vehicleId < 1) {
                return ['success' => false, 'message' => 'Vehicle not found for this job.'];
            }

            if (($txn['vehicle_status'] ?? '') === 'completed') {
                return ['success' => false, 'message' => 'This wash is already marked done.'];
            }

            $bayName    = $txn['bay_name'];
            $washerName = $txn['washer_name'];

            Vehicle::complete($vehicleId);

            $receipt = Transaction::find($transactionId);
            if ($receipt) {
                $receipt['bay_name']    = $bayName;
                $receipt['washer_name'] = $washerName;
            }

            return [
                'success' => true,
                'message' => 'Wash completed. Bay and washer are free.',
                'receipt' => $receipt,
                'bays'    => Bay::all(),
                'washers' => Washer::allAvailable(),
                'jobs'    => Vehicle::activeJobs(),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Could not complete wash: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send a paid receipt by SMS (Messages) or WhatsApp share link.
     */
    public static function sendReceipt(array $input): array
    {
        $transactionId = (int) ($input['transaction_id'] ?? 0);
        $channel = strtolower(trim((string) ($input['channel'] ?? '')));
        $phone = trim((string) ($input['customer_phone'] ?? ''));

        $txn = Transaction::find($transactionId);
        if (!$txn) {
            return ['success' => false, 'message' => 'Receipt not found.'];
        }

        if (strtolower((string) ($txn['payment_status'] ?? '')) !== 'paid') {
            return ['success' => false, 'message' => 'Pay the invoice first, then send the receipt.'];
        }

        $vehicleId = (int) ($txn['vehicle_id'] ?? 0);
        if ($phone === '') {
            $phone = trim((string) ($txn['customer_phone'] ?? ''));
        }

        if ($vehicleId > 0 && $phone !== '') {
            Vehicle::updateCustomerContact($vehicleId, null, $phone);
            $txn = Transaction::find($transactionId) ?? $txn;
        }

        if ($channel === 'sms') {
            $result = ReceiptSender::sendSms($phone, $txn);
            $result['receipt'] = $txn;
            return $result;
        }

        if ($channel === 'whatsapp') {
            if (!ReceiptSender::isValidPhone($phone)) {
                return ['success' => false, 'message' => 'Enter a valid customer phone number.'];
            }

            try {
                $pdfUrl = ReceiptPdf::generate($txn);
            } catch (Throwable $e) {
                return [
                    'success' => false,
                    'message' => 'Could not build PDF receipt: ' . $e->getMessage(),
                ];
            }

            return [
                'success'      => true,
                'message'      => 'A5 PDF ready. Attach the downloaded file in WhatsApp.',
                'whatsapp_url' => ReceiptSender::whatsappUrl($phone, $txn),
                'pdf_url'      => $pdfUrl,
                'pdf_name'     => ReceiptPdf::filename((int) $txn['id']),
                'receipt'      => $txn,
            ];
        }

        return ['success' => false, 'message' => 'Choose SMS or WhatsApp.'];
    }

    /**
     * Stream a paid A5 receipt PDF for download / WhatsApp attach.
     */
    public static function downloadReceiptPdf(int $transactionId): void
    {
        $txn = Transaction::find($transactionId);
        if (!$txn || strtolower((string) ($txn['payment_status'] ?? '')) !== 'paid') {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Receipt PDF not available.';
            exit;
        }

        try {
            ReceiptPdf::generate($txn);
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Could not build PDF: ' . $e->getMessage();
            exit;
        }

        $path = ReceiptPdf::absolutePath($transactionId);
        if (!is_file($path)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'PDF file missing.';
            exit;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . ReceiptPdf::filename($transactionId) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
