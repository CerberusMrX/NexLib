<?php
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthMiddleware {
    public static function authenticate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $payload = JWT::decode($token);
            if ($payload) {
                return $payload;
            }
        }

        Response::error("Unauthorized access", 401);
    }

    public static function isAdmin() {
        $user = self::authenticate();
        if ($user['role'] !== 'admin') {
            Response::error("Forbidden: Admin access required", 403);
        }
        return $user;
    }
}
?>
