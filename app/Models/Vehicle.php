<?php

class Vehicle
{
    public static function create(
        string $plate,
        string $carType,
        int $bayId,
        int $washerId,
        ?string $customerEmail = null,
        ?string $customerPhone = null
    ): int {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            "INSERT INTO vehicles
                (license_plate, car_type, vehicle_status, assigned_bay_id, assigned_washer_id, customer_email, customer_phone)
             VALUES (?, ?, 'washing', ?, ?, ?, ?)"
        );
        $email = $customerEmail !== null && trim($customerEmail) !== '' ? trim($customerEmail) : null;
        $phone = $customerPhone !== null && trim($customerPhone) !== '' ? trim($customerPhone) : null;
        $stmt->execute([strtoupper(trim($plate)), $carType, $bayId, $washerId, $email, $phone]);

        $vehicleId = (int) $pdo->lastInsertId();
        if ($vehicleId < 1) {
            throw new RuntimeException('Could not create vehicle record.');
        }

        Bay::setStatus($bayId, 'occupied');
        return $vehicleId;
    }

    public static function updateCustomerContact(int $vehicleId, ?string $email, ?string $phone): bool
    {
        $stmt = Database::connect()->prepare(
            'UPDATE vehicles
             SET customer_email = COALESCE(?, customer_email),
                 customer_phone = COALESCE(?, customer_phone)
             WHERE id = ?'
        );
        $emailVal = $email !== null && trim($email) !== '' ? trim($email) : null;
        $phoneVal = $phone !== null && trim($phone) !== '' ? trim($phone) : null;
        return $stmt->execute([$emailVal, $phoneVal, $vehicleId]);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT v.*, w.washer_name, b.bay_name
             FROM vehicles v
             LEFT JOIN washers w ON w.id = v.assigned_washer_id
             LEFT JOIN bays b ON b.id = v.assigned_bay_id
             WHERE v.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function complete(int $vehicleId): bool
    {
        $vehicle = self::find($vehicleId);
        if (!$vehicle) {
            return false;
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            "UPDATE vehicles
             SET vehicle_status = 'completed'
             WHERE id = ?"
        );
        $stmt->execute([$vehicleId]);

        // Free the bay for new cars, but keep assigned_bay_id for receipt history
        if (!empty($vehicle['assigned_bay_id'])) {
            Bay::setStatus((int) $vehicle['assigned_bay_id'], 'empty');
        }

        return true;
    }

    /**
     * Live washing jobs + all of today's completed jobs (paid or unpaid),
     * so attendants can still collect payment or reprint after the wash is done.
     */
    public static function activeJobs(): array
    {
        $stmt = Database::connect()->query(
            "SELECT v.*, w.washer_name, b.bay_name, s.service_name,
                    t.total_amount, t.payment_status, t.payment_method,
                    t.created_at AS transaction_created_at, t.paid_at,
                    t.id AS transaction_id,
                    v.customer_email, v.customer_phone
             FROM vehicles v
             LEFT JOIN washers w ON w.id = v.assigned_washer_id
             LEFT JOIN bays b ON b.id = v.assigned_bay_id
             LEFT JOIN transactions t ON t.vehicle_id = v.id
             LEFT JOIN services s ON s.id = t.service_id
             WHERE v.vehicle_status IN ('pending', 'washing')
                OR (
                    v.vehicle_status = 'completed'
                    AND DATE(v.checked_in_at) = CURDATE()
                )
             ORDER BY
                CASE WHEN v.vehicle_status IN ('pending', 'washing') THEN 0 ELSE 1 END,
                COALESCE(t.paid_at, v.checked_in_at) DESC"
        );
        return $stmt->fetchAll();
    }
}
