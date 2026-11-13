<?php

/**
 * Ghana number-plate helpers.
 *
 * Accepts:
 * 1) Standard DVLA format: REGION ####-YY  (e.g. GR 2345-26)
 * 2) Zonal-style suffix:   REGION ####-ZZ  (e.g. GR 2224-AD)
 * 3) Custom / vanity plates that still look like a plate
 *    (letters + numbers, spaces/hyphens ok) — e.g. BOSS 1, KING-22
 *
 * Rejects empty junk, symbols-only, and letter-only random strings.
 */
class GhanaPlate
{
    private const REGION_CODES = [
        'GR', 'GB', 'GC', 'GE', 'GG', 'GH', 'GL', 'GM', 'GN', 'GS', 'GT', 'GW', 'GX', 'GY',
        'AS', 'AE', 'AW', 'AC', 'AK', 'AP',
        'BA', 'BE', 'BO', 'CR', 'ER', 'NR', 'NE', 'OT', 'SV', 'UE', 'UW', 'VR', 'WR', 'WN',
    ];

    public static function normalize(string $raw): string
    {
        $value = strtoupper(trim($raw));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        // GR2345-26 → GR 2345-26 when it looks like a region code + digits
        $value = preg_replace('/^([A-Z]{2})(\d)/', '$1 $2', $value) ?? $value;
        return $value;
    }

    public static function isStandardFormat(string $plate): bool
    {
        if (!preg_match('/^([A-Z]{2}) (\d{1,4})-([0-9]{2}|[A-Z]{2})$/', $plate, $m)) {
            return false;
        }

        $region = $m[1];
        $serial = (int) $m[2];
        $suffix = $m[3];

        if (!in_array($region, self::REGION_CODES, true)) {
            return false;
        }

        if ($serial < 1 || $serial > 9999) {
            return false;
        }

        if (ctype_digit($suffix)) {
            $year = (int) $suffix;
            $maxYear = ((int) date('Y') + 1) % 100;
            if ($year < 9 || $year > $maxYear) {
                return false;
            }
        }

        return true;
    }

    /**
     * Custom / vanity plate: letters + numbers, plate-like length.
     * Blocks random letter-only strings and symbol junk.
     */
    public static function isCustomFormat(string $plate): bool
    {
        $len = strlen($plate);
        if ($len < 3 || $len > 12) {
            return false;
        }

        if (!preg_match('/^[A-Z0-9 \-]{3,12}$/', $plate)) {
            return false;
        }

        if (!preg_match('/[A-Z]/', $plate) || !preg_match('/[0-9]/', $plate)) {
            return false;
        }

        // Reject silly repeats like AAAA or 1111
        $compact = preg_replace('/[\s\-]/', '', $plate) ?? $plate;
        if ($compact !== '' && preg_match('/^(.)\1+$/', $compact)) {
            return false;
        }

        return true;
    }

    public static function isValid(string $raw): bool
    {
        $plate = self::normalize($raw);
        if ($plate === '') {
            return false;
        }

        return self::isStandardFormat($plate) || self::isCustomFormat($plate);
    }

    public static function validationMessage(): string
    {
        return 'Enter a Ghana plate (e.g. GR 2345-26) or a custom plate with letters and numbers.';
    }
}
