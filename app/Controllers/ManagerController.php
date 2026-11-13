<?php

require_once __DIR__ . '/../Models/Service.php';
require_once __DIR__ . '/../Models/Machinery.php';
require_once __DIR__ . '/../Models/Washer.php';
require_once __DIR__ . '/../Models/Transaction.php';
require_once __DIR__ . '/../Models/Bay.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/AuthController.php';

class ManagerController
{
    public static function dashboardData(): array
    {
        return [
            'metrics' => [
                'daily_revenue'    => Transaction::dailyRevenue(),
                'weekly_revenue'   => Transaction::weeklyRevenue(),
                'vehicles_washed'  => Transaction::vehiclesWashedToday(),
                'empty_bays'       => count(array_filter(Bay::all(), fn ($b) => $b['bay_status'] === 'empty')),
            ],
            'services'     => Service::all(),
            'machinery'    => Machinery::all(),
            'performance'  => Washer::performanceReport(),
            'recent'       => Transaction::recentPaid(),
            'bays'         => Bay::all(),
            'attendants'   => User::allAttendants(),
        ];
    }

    public static function updateServicePrices(array $input): array
    {
        $id    = (int) ($input['service_id'] ?? 0);
        $sedan = (float) ($input['price_sedan'] ?? -1);
        $suv   = (float) ($input['price_suv'] ?? -1);
        $van   = (float) ($input['price_van'] ?? -1);

        if ($id < 1 || $sedan < 0 || $suv < 0 || $van < 0) {
            return ['success' => false, 'message' => 'Invalid price data.'];
        }

        if (!Service::find($id)) {
            return ['success' => false, 'message' => 'Service not found.'];
        }

        Service::updatePrices($id, $sedan, $suv, $van);

        return [
            'success'  => true,
            'message'  => 'Prices updated successfully.',
            'services' => Service::all(),
        ];
    }

    public static function toggleMachinery(int $id): array
    {
        $machine = Machinery::toggleStatus($id);
        if (!$machine) {
            return ['success' => false, 'message' => 'Machine not found.'];
        }

        return [
            'success'   => true,
            'message'   => $machine['machine_name'] . ' is now ' . $machine['machinery_status'] . '.',
            'machinery' => Machinery::all(),
        ];
    }

    public static function addMachinery(array $input): array
    {
        $name = trim($input['machine_name'] ?? '');
        if ($name === '') {
            return ['success' => false, 'message' => 'Enter an equipment name.'];
        }

        if (strlen($name) > 100) {
            return ['success' => false, 'message' => 'Equipment name is too long.'];
        }

        $machine = Machinery::create($name);
        if (!$machine) {
            return ['success' => false, 'message' => 'Could not add equipment.'];
        }

        return [
            'success'   => true,
            'message'   => $machine['machine_name'] . ' added to the registry.',
            'machinery' => Machinery::all(),
        ];
    }

    public static function removeMachinery(int $id): array
    {
        $machine = Machinery::delete($id);
        if (!$machine) {
            return ['success' => false, 'message' => 'Machine not found.'];
        }

        return [
            'success'   => true,
            'message'   => $machine['machine_name'] . ' removed from the registry.',
            'machinery' => Machinery::all(),
        ];
    }

    /**
     * Money report for manager: paid Cash/MoMo transactions in a date range.
     */
    public static function revenueReport(array $input): array
    {
        $from = trim($input['from_date'] ?? '');
        $to   = trim($input['to_date'] ?? '');

        if ($from === '' || $to === '') {
            return ['success' => false, 'message' => 'Choose a from and to date.'];
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            return ['success' => false, 'message' => 'Invalid date format.'];
        }

        if ($from > $to) {
            return ['success' => false, 'message' => 'From date cannot be after to date.'];
        }

        $rows    = Transaction::paidReport($from, $to);
        $summary = Transaction::reportSummary($from, $to);

        return [
            'success'  => true,
            'from_date'=> $from,
            'to_date'  => $to,
            'summary'  => $summary,
            'rows'     => $rows,
        ];
    }

    /** Manager-only: list attendant login accounts. */
    public static function attendantsData(): array
    {
        return [
            'success'    => true,
            'attendants' => User::allAttendants(),
        ];
    }

    public static function createAttendant(array $input): array
    {
        $username = strtolower(trim($input['username'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $confirm  = (string) ($input['password_confirm'] ?? '');

        if ($username === '' || $password === '') {
            return ['success' => false, 'message' => 'Username and password are required.'];
        }

        if (!preg_match('/^[a-z0-9._-]{3,50}$/', $username)) {
            return [
                'success' => false,
                'message' => 'Username must be 3–50 characters: letters, numbers, dot, underscore, or hyphen.',
            ];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
        }

        if ($password !== $confirm) {
            return ['success' => false, 'message' => 'Password confirmation does not match.'];
        }

        if (User::findByUsername($username)) {
            return ['success' => false, 'message' => 'That username is already taken.'];
        }

        $user = User::createAttendant($username, AuthController::hashPassword($password));
        if (!$user) {
            return ['success' => false, 'message' => 'Could not create attendant account.'];
        }

        return [
            'success'    => true,
            'message'    => 'Attendant account "' . $username . '" created.',
            'attendants' => User::allAttendants(),
        ];
    }

    public static function setAttendantStatus(int $id, string $status): array
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            return ['success' => false, 'message' => 'Invalid status.'];
        }

        $user = User::setStatus($id, $status);
        if (!$user) {
            return ['success' => false, 'message' => 'Attendant account not found.'];
        }

        $label = $status === 'active' ? 'reactivated' : 'deactivated';

        return [
            'success'    => true,
            'message'    => 'Account "' . $user['username'] . '" ' . $label . '.',
            'attendants' => User::allAttendants(),
        ];
    }

    public static function resetAttendantPassword(array $input): array
    {
        $id       = (int) ($input['user_id'] ?? 0);
        $password = (string) ($input['password'] ?? '');
        $confirm  = (string) ($input['password_confirm'] ?? '');

        if ($id < 1) {
            return ['success' => false, 'message' => 'Attendant not selected.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
        }

        if ($password !== $confirm) {
            return ['success' => false, 'message' => 'Password confirmation does not match.'];
        }

        $user = User::resetAttendantPassword($id, AuthController::hashPassword($password));
        if (!$user) {
            return ['success' => false, 'message' => 'Attendant account not found.'];
        }

        return [
            'success'    => true,
            'message'    => 'Password updated for "' . $user['username'] . '".',
            'attendants' => User::allAttendants(),
        ];
    }
}
