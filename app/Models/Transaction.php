<?php

class Transaction
{
    public static function create(int $vehicleId, int $serviceId, float $amount): int
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            "INSERT INTO transactions
                (vehicle_id, service_id, total_amount, payment_status, payment_method)
             VALUES (?, ?, ?, 'pending', 'none')"
        );
        $stmt->execute([$vehicleId, $serviceId, $amount]);
        return (int) $pdo->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT t.*, v.license_plate, v.car_type, v.vehicle_status, v.assigned_washer_id,
                    v.customer_email, v.customer_phone, v.id AS vehicle_id,
                    w.id AS washer_id, w.washer_name, b.bay_name, s.service_name
             FROM transactions t
             JOIN vehicles v ON v.id = t.vehicle_id
             LEFT JOIN washers w ON w.id = v.assigned_washer_id
             LEFT JOIN bays b ON b.id = v.assigned_bay_id
             JOIN services s ON s.id = t.service_id
             WHERE t.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByVehicle(int $vehicleId): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT t.*, s.service_name
             FROM transactions t
             JOIN services s ON s.id = t.service_id
             WHERE t.vehicle_id = ?
             ORDER BY t.id DESC
             LIMIT 1'
        );
        $stmt->execute([$vehicleId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function markPaid(int $id, string $method): bool
    {
        $stmt = Database::connect()->prepare(
            "UPDATE transactions
             SET payment_status = 'paid',
                 payment_method = ?,
                 paid_at = NOW()
             WHERE id = ?"
        );
        return $stmt->execute([$method, $id]);
    }

    public static function savePaystackReference(int $id, string $reference): bool
    {
        $stmt = Database::connect()->prepare(
            'UPDATE transactions SET paystack_reference = ? WHERE id = ?'
        );
        return $stmt->execute([$reference, $id]);
    }

    public static function findByPaystackReference(string $reference): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT id FROM transactions WHERE paystack_reference = ? LIMIT 1'
        );
        $stmt->execute([$reference]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return self::find((int) $row['id']);
    }

    public static function markPaidFromPaystack(int $id, string $reference): bool
    {
        $stmt = Database::connect()->prepare(
            "UPDATE transactions
             SET payment_status = 'paid',
                 payment_method = 'momo',
                 paystack_reference = ?,
                 paid_at = NOW()
             WHERE id = ?
               AND payment_status = 'pending'"
        );
        return $stmt->execute([$reference, $id]);
    }

    public static function dailyRevenue(): float
    {
        $stmt = Database::connect()->query(
            "SELECT COALESCE(SUM(total_amount), 0) AS total
             FROM transactions
             WHERE payment_status = 'paid'
               AND DATE(paid_at) = CURDATE()"
        );
        return (float) $stmt->fetch()['total'];
    }

    public static function weeklyRevenue(): float
    {
        $stmt = Database::connect()->query(
            "SELECT COALESCE(SUM(total_amount), 0) AS total
             FROM transactions
             WHERE payment_status = 'paid'
               AND paid_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)"
        );
        return (float) $stmt->fetch()['total'];
    }

    public static function vehiclesWashedToday(): int
    {
        $stmt = Database::connect()->query(
            "SELECT COUNT(*) AS total
             FROM vehicles
             WHERE vehicle_status = 'completed'
               AND DATE(checked_in_at) = CURDATE()"
        );
        return (int) $stmt->fetch()['total'];
    }

    public static function recentPaid(int $limit = 8): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT t.*, v.license_plate, v.car_type, s.service_name,
                    w.id AS washer_id, w.washer_name
             FROM transactions t
             JOIN vehicles v ON v.id = t.vehicle_id
             JOIN services s ON s.id = t.service_id
             LEFT JOIN washers w ON w.id = v.assigned_washer_id
             WHERE t.payment_status = 'paid'
             ORDER BY t.paid_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Paid money movements for a date range (inclusive), for manager reports.
     */
    public static function paidReport(string $fromDate, string $toDate): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT t.id,
                    t.total_amount,
                    t.payment_method,
                    t.paid_at,
                    t.created_at,
                    v.license_plate,
                    v.car_type,
                    s.service_name,
                    w.id AS washer_id,
                    w.washer_name,
                    b.bay_name
             FROM transactions t
             JOIN vehicles v ON v.id = t.vehicle_id
             JOIN services s ON s.id = t.service_id
             LEFT JOIN washers w ON w.id = v.assigned_washer_id
             LEFT JOIN bays b ON b.id = v.assigned_bay_id
             WHERE t.payment_status = 'paid'
               AND DATE(t.paid_at) BETWEEN ? AND ?
             ORDER BY t.paid_at ASC, t.id ASC"
        );
        $stmt->execute([$fromDate, $toDate]);
        return $stmt->fetchAll();
    }

    public static function reportSummary(string $fromDate, string $toDate): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT
                COUNT(*) AS payment_count,
                COALESCE(SUM(total_amount), 0) AS total_revenue,
                COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END), 0) AS cash_total,
                COALESCE(SUM(CASE WHEN payment_method = 'momo' THEN total_amount ELSE 0 END), 0) AS momo_total
             FROM transactions
             WHERE payment_status = 'paid'
               AND DATE(paid_at) BETWEEN ? AND ?"
        );
        $stmt->execute([$fromDate, $toDate]);
        $row = $stmt->fetch() ?: [];

        return [
            'payment_count' => (int) ($row['payment_count'] ?? 0),
            'total_revenue' => (float) ($row['total_revenue'] ?? 0),
            'cash_total'    => (float) ($row['cash_total'] ?? 0),
            'momo_total'    => (float) ($row['momo_total'] ?? 0),
        ];
    }
}
