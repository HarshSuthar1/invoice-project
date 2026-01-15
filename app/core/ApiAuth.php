<?php

class ApiAuth
{
    public static function requireLogin(): void
    {
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            exit;
        }
    }
}
