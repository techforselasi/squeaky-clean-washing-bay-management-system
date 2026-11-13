<?php

require_once __DIR__ . '/lib/fpdf.php';

/**
 * Builds an A5 digital receipt PDF for download / WhatsApp sharing.
 */
class ReceiptPdf
{
    public static function storageDir(): string
    {
        $dir = dirname(__DIR__) . '/storage/receipts';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    public static function filename(int $transactionId): string
    {
        return 'receipt-' . $transactionId . '.pdf';
    }

    public static function absolutePath(int $transactionId): string
    {
        return self::storageDir() . '/' . self::filename($transactionId);
    }

    /**
     * Generate (or refresh) the A5 PDF and return the public download path.
     */
    public static function generate(array $receipt): string
    {
        $id = (int) ($receipt['id'] ?? 0);
        if ($id < 1) {
            throw new RuntimeException('Missing receipt id.');
        }

        $amount = 'GHS ' . number_format((float) ($receipt['total_amount'] ?? 0), 2);
        $method = strtoupper((string) ($receipt['payment_method'] ?? 'PAID'));
        $plate = (string) ($receipt['license_plate'] ?? '—');
        $carType = (string) ($receipt['car_type'] ?? '');
        $bay = (string) ($receipt['bay_name'] ?? '');
        $service = (string) ($receipt['service_name'] ?? 'Wash Service');
        $washer = (string) ($receipt['washer_name'] ?? '—');
        $date = (string) ($receipt['paid_at'] ?? $receipt['created_at'] ?? date('Y-m-d H:i'));

        $pdf = new FPDF('P', 'mm', 'A5');
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();

        // Header bar
        $pdf->SetFillColor(72, 135, 183);
        $pdf->Rect(0, 0, 148, 52, 'F');

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetXY(12, 8);
        $pdf->Cell(0, 5, 'From', 0, 1);
        $pdf->SetFont('Helvetica', 'B', 13);
        $pdf->SetX(12);
        $pdf->Cell(0, 6, 'Squeaky Clean Washing Bay', 0, 1);

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetXY(12, 24);
        $pdf->Cell(70, 5, 'Vehicle', 0, 0);
        $pdf->Cell(0, 5, 'RECEIPT', 0, 1);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetX(12);
        $pdf->Cell(70, 6, $plate, 0, 0);
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->Cell(0, 6, $amount, 0, 1);

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetX(12);
        $pdf->Cell(70, 5, trim($carType . '  ' . $bay), 0, 0);
        $pdf->Cell(0, 5, '#' . $id . '  |  ' . $date, 0, 1);

        $pdf->SetXY(12, 44);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'PAID - ' . $method, 0, 1);

        // Body
        $pdf->SetTextColor(26, 42, 54);
        $pdf->SetY(60);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(90, 7, 'Service', 'B', 0);
        $pdf->Cell(34, 7, 'Total', 'B', 1, 'R');

        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(90, 8, $service, 0, 0);
        $pdf->Cell(34, 8, $amount, 0, 1, 'R');
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(107, 138, 154);
        $pdf->Cell(0, 5, 'Washer: ' . $washer, 0, 1);

        $pdf->Ln(8);
        $pdf->SetTextColor(26, 42, 54);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(90, 8, 'Amount paid', 'T', 0);
        $pdf->Cell(34, 8, $amount, 'T', 1, 'R');

        $pdf->Ln(10);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(107, 138, 154);
        $pdf->MultiCell(0, 5, "Thank you for washing with Squeaky Clean.\nThis A5 digital receipt is valid proof of payment.");

        $path = self::absolutePath($id);
        $pdf->Output('F', $path);

        return '/api/attendant.php?action=download_receipt_pdf&transaction_id=' . $id;
    }
}
