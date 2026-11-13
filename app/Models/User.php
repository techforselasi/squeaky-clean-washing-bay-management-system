<?php

class User
{
    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT * FROM users WHERE username = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT id, username, user_role, user_status FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function updatePassword(int $id, string $hash): bool
    {
        $stmt = Database::connect()->prepare(
            'UPDATE users SET user_password = ? WHERE id = ?'
        );
        return $stmt->execute([$hash, $id]);
    }

    /** Attendant accounts only (never returns managers). */
    public static function allAttendants(): array
    {
        $stmt = Database::connect()->query(
            "SELECT id, username, user_role, user_status
             FROM users
             WHERE user_role = 'attendant'
             ORDER BY username ASC"
        );
        return $stmt->fetchAll();
    }

    public static function createAttendant(string $username, string $passwordHash): ?array
    {
        $stmt = Database::connect()->prepare(
            "INSERT INTO users (username, user_password, user_role, user_status)
             VALUES (?, ?, 'attendant', 'active')"
        );
        $stmt->execute([$username, $passwordHash]);
        $id = (int) Database::connect()->lastInsertId();
        return $id > 0 ? self::findById($id) : null;
    }

    public static function setStatus(int $id, string $status): ?array
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            return null;
        }

        $user = self::findById($id);
        if (!$user || ($user['user_role'] ?? '') !== 'attendant') {
            return null;
        }

        $stmt = Database::connect()->prepare(
            'UPDATE users SET user_status = ? WHERE id = ? AND user_role = \'attendant\''
        );
        $stmt->execute([$status, $id]);
        return self::findById($id);
    }

    public static function resetAttendantPassword(int $id, string $passwordHash): ?array
    {
        $user = self::findById($id);
        if (!$user || ($user['user_role'] ?? '') !== 'attendant') {
            return null;
        }

        if (!self::updatePassword($id, $passwordHash)) {
            return null;
        }

        return self::findById($id);
    }
}
