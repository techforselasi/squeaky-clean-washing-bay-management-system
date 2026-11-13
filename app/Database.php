<?php
/**
 * Returns the shared PDO connection ($conn) from config/database.php.
 */
class Database
{
    public static function connect(): PDO
    {
        global $conn;

        if (!isset($conn) || !($conn instanceof PDO)) {
            require_once __DIR__ . '/../config/database.php';
        }

        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $conn;
    }
}
