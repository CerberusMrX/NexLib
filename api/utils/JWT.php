<?php
class JWT {
    private static $secret = "your_secret_key_here_1234567890"; // In production, move to env

    public static function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $header = self::base64UrlEncode($header);
        
        $payload['exp'] = time() + (60 * 60 * 24); // 24 hours
        $payload = json_encode($payload);
        $payload = self::base64UrlEncode($payload);

        $signature = hash_hmac('sha256', "$header.$payload", self::$secret, true);
        $signature = self::base64UrlEncode($signature);

        return "$header.$payload.$signature";
    }

    public static function decode($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;

        [$header, $payload, $signature] = $parts;

        $validSignature = hash_hmac('sha256', "$header.$payload", self::$secret, true);
        $validSignature = self::base64UrlEncode($validSignature);

        if ($signature !== $validSignature) return false;

        $payload = json_decode(self::base64UrlDecode($payload), true);
        if (isset($payload['exp']) && $payload['exp'] < time()) return false;

        return $payload;
    }

    private static function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode($data) {
        $data = str_replace(['-', '_'], ['+', '/'], $data);
        $padding = strlen($data) % 4;
        if ($padding) $data .= str_repeat('=', 4 - $padding);
        return base64_decode($data);
    }
}
?>
