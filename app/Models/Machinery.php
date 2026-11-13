<?php

class Machinery
{
    public static function all(): array
    {
        $stmt = Database::connect()->query(
            'SELECT * FROM machinery ORDER BY id ASC'
        );
        return $stmt->fetchAll();
    }

    public static function create(string $machineName): ?array
    {
        $name = trim($machineName);
        if ($name === '') {
            return null;
        }

        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            "INSERT INTO machinery (machine_name, machinery_status) VALUES (?, 'active')"
        );
        $stmt->execute([$name]);

        $id = (int) $pdo->lastInsertId();
        $find = $pdo->prepare('SELECT * FROM machinery WHERE id = ?');
        $find->execute([$id]);
        $machine = $find->fetch();

        return $machine ?: null;
    }

    public static function toggleStatus(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT * FROM machinery WHERE id = ?'
        );
        $stmt->execute([$id]);
        $machine = $stmt->fetch();

        if (!$machine) {
            return null;
        }

        $newStatus = $machine['machinery_status'] === 'active' ? 'under repair' : 'active';

        $update = Database::connect()->prepare(
            'UPDATE machinery SET machinery_status = ? WHERE id = ?'
        );
        $update->execute([$newStatus, $id]);

        $machine['machinery_status'] = $newStatus;
        return $machine;
    }

    public static function delete(int $id): ?array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT * FROM machinery WHERE id = ?');
        $stmt->execute([$id]);
        $machine = $stmt->fetch();

        if (!$machine) {
            return null;
        }

        $delete = $pdo->prepare('DELETE FROM machinery WHERE id = ?');
        $delete->execute([$id]);

        return $machine;
    }
}
