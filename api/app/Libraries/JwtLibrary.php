<?php

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtLibrary
{
    private static string $key = 'AksiGuruJWTSecretKey2025!@#$SecureToken';

    /**
     * Generate JWT token
     *
     * @param array $data  ['user_id', 'username', 'role']
     * @return string
     */
    public static function encode(array $data): string
    {
        $payload = [
            'iat'       => time(),
            'exp'       => time() + 86400, // 24 jam
            'data'      => $data,
        ];

        return JWT::encode($payload, self::$key, 'HS256');
    }

    /**
     * Decode JWT token
     *
     * @param string $token
     * @return object
     */
    public static function decode(string $token): object
    {
        $decoded = JWT::decode($token, new Key(self::$key, 'HS256'));
        return $decoded->data;
    }
}