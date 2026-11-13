<?php

class Service
{
    public static function all(): array
    {
        $stmt = Database::connect()->query(
            'SELECT * FROM services ORDER BY id ASC'
        );
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT * FROM services WHERE id = ?'
        );
        $stmt->execute([$id]);
        $service = $stmt->fetch();
        return $service ?: null;
    }

    public static function priceFor(array $service, string $carType): float
    {
        return match ($carType) {
            'Sedan' => (float) $service['price_sedan'],
            'SUV'   => (float) $service['price_suv'],
            'Van'   => (float) $service['price_van'],
            default => 0.0,
        };
    }

    public static function updatePrices(
        int $id,
        float $sedan,
        float $suv,
        float $van
    ): bool {
        $stmt = Database::connect()->prepare(
            'UPDATE services
             SET price_sedan = ?, price_suv = ?, price_van = ?
             WHERE id = ?'
        );
        return $stmt->execute([$sedan, $suv, $van, $id]);
    }
}
