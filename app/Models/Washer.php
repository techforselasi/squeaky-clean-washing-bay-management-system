<?php

class Washer
{
    public static function allActive(): array
    {
        $stmt = Database::connect()->query(
            "SELECT * FROM washers WHERE washer_status = 'active' ORDER BY washer_name ASC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Active washers who are NOT currently assigned to a washing vehicle.
     * Used by the intake dropdown so a booked washer cannot be double-booked.
     */
    public static function allAvailable(): array
    {
        $stmt = Database::connect()->query(
            "SELECT w.*
             FROM washers w
             WHERE w.washer_status = 'active'
               AND w.id NOT IN (
                    SELECT DISTINCT v.assigned_washer_id
                    FROM vehicles v
                    WHERE v.vehicle_status = 'washing'
                      AND v.assigned_washer_id IS NOT NULL
               )
             ORDER BY w.washer_name ASC"
        );
        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        $stmt = Database::connect()->query(
            'SELECT * FROM washers ORDER BY washer_name ASC'
        );
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT * FROM washers WHERE id = ?'
        );
        $stmt->execute([$id]);
        $washer = $stmt->fetch();
        return $washer ?: null;
    }

    public static function isBusy(int $washerId): bool
    {
        $stmt = Database::connect()->prepare(
            "SELECT id FROM vehicles
             WHERE assigned_washer_id = ?
               AND vehicle_status = 'washing'
             LIMIT 1"
        );
        $stmt->execute([$washerId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Performance: completed paid washes + revenue per washer.
     */
    public static function performanceReport(): array
    {
        $sql = "SELECT
                    w.id,
                    w.washer_name,
                    w.washer_status,
                    COUNT(v.id) AS vehicles_washed,
                    COALESCE(SUM(CASE WHEN t.payment_status = 'paid' THEN t.total_amount ELSE 0 END), 0) AS total_revenue
                FROM washers w
                LEFT JOIN vehicles v
                    ON v.assigned_washer_id = w.id
                   AND v.vehicle_status = 'completed'
                LEFT JOIN transactions t ON t.vehicle_id = v.id
                GROUP BY w.id, w.washer_name, w.washer_status
                ORDER BY vehicles_washed DESC, total_revenue DESC";

        return Database::connect()->query($sql)->fetchAll();
    }
}
