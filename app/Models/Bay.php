<?php

class Bay
{
    /**
     * Always returns exactly one row per physical bay (Bay 1–4).
     * At most one active (washing) vehicle is attached per bay.
     */
    public static function all(): array
    {
        $stmt = Database::connect()->query(
            'SELECT b.id,
                    b.bay_name,
                    b.bay_status,
                    v.id AS vehicle_id,
                    v.license_plate,
                    v.car_type,
                    v.vehicle_status,
                    w.id AS washer_id,
                    w.washer_name
             FROM bays b
             LEFT JOIN vehicles v
                ON v.id = (
                    SELECT v2.id
                    FROM vehicles v2
                    WHERE v2.assigned_bay_id = b.id
                      AND v2.vehicle_status = \'washing\'
                    ORDER BY v2.checked_in_at DESC
                    LIMIT 1
                )
             LEFT JOIN washers w ON w.id = v.assigned_washer_id
             ORDER BY b.id ASC'
        );

        $bays = $stmt->fetchAll();

        // Keep bay_status honest: occupied only when a live wash is on it
        foreach ($bays as &$bay) {
            $inUse = !empty($bay['vehicle_id']);
            $expected = $inUse ? 'occupied' : 'empty';
            if ($bay['bay_status'] !== $expected) {
                self::setStatus((int) $bay['id'], $expected);
                $bay['bay_status'] = $expected;
            }
        }
        unset($bay);

        return $bays;
    }

    public static function setStatus(int $bayId, string $status): bool
    {
        $stmt = Database::connect()->prepare(
            'UPDATE bays SET bay_status = ? WHERE id = ?'
        );
        return $stmt->execute([$status, $bayId]);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM bays WHERE id = ?');
        $stmt->execute([$id]);
        $bay = $stmt->fetch();
        return $bay ?: null;
    }

    public static function hasActiveWash(int $bayId): bool
    {
        $stmt = Database::connect()->prepare(
            "SELECT id FROM vehicles
             WHERE assigned_bay_id = ?
               AND vehicle_status = 'washing'
             LIMIT 1"
        );
        $stmt->execute([$bayId]);
        return (bool) $stmt->fetch();
    }
}
