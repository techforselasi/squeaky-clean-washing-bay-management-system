<?php

require_once __DIR__ . '/../Models/User.php';

class AuthController
{
    /**
     * Hash a plain password with Argon2i.
     */
    public static function hashPassword(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_ARGON2I);
    }

    public static function login(string $username, string $password): array
    {
        $user = User::findByUsername($username);

        if (!$user || !password_verify($password, $user['user_password'])) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        $status = $user['user_status'] ?? 'active';
        if ($status !== 'active') {
            return ['success' => false, 'message' => 'This account is inactive. Ask the manager to restore access.'];
        }

        // Upgrade older hashes (e.g. bcrypt) to Argon2i on successful login.
        if (password_needs_rehash($user['user_password'], PASSWORD_ARGON2I)) {
            $newHash = self::hashPassword($password);
            User::updatePassword((int) $user['id'], $newHash);
        }

        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['user_role'];

        $redirect = $user['user_role'] === 'manager'
            ? '/manager.php'
            : '/attendant.php';

        return [
            'success'  => true,
            'message'  => 'Login successful.',
            'redirect' => $redirect,
            'role'     => $user['user_role'],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}
