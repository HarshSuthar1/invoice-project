<?php

class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id'   => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? '',
            'email'=> $_SESSION['user_email'] ?? ''
        ];
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header("Location: /Business%20project/public/index.php?page=login");
            exit;
        }
    }

    public static function logout(): void
    {
        session_unset();
        session_destroy();
    }
}
