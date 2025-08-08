<?php
declare(strict_types=1);

/**
 * Manejador de JWT para APIs de servicio a servicio
 * 
 * @package KetchupLovers
 * @version 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    http_response_code(403);
    exit('Acceso directo prohibido');
}

/**
 * Clase para manejar JWT en APIs de servicio a servicio
 */
class KL_Wallet_JWT_Handler {
    
    /**
     * Generar token JWT para servicio a servicio
     * 
     * @param array $payload Datos del token
     * @param int $expiration Tiempo de expiración en segundos
     * @return string
     */
    public static function generate_token(array $payload, int $expiration = 3600): string {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiration;
        $payload['iss'] = 'kl-wallet-api';
        $payload['aud'] = 'service-to-service';
        
        $secret = self::get_jwt_secret();
        
        $header_encoded = self::base64url_encode(json_encode($header));
        $payload_encoded = self::base64url_encode(json_encode($payload));
        
        $signature = hash_hmac('sha256', 
            $header_encoded . '.' . $payload_encoded, 
            $secret, 
            true
        );
        
        $signature_encoded = self::base64url_encode($signature);
        
        return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
    }
    
    /**
     * Verificar token JWT
     * 
     * @param string $token Token JWT
     * @return array|false
     */
    public static function verify_token(string $token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        [$header_encoded, $payload_encoded, $signature_encoded] = $parts;
        
        $secret = self::get_jwt_secret();
        
        $signature = hash_hmac('sha256', 
            $header_encoded . '.' . $payload_encoded, 
            $secret, 
            true
        );
        
        $expected_signature = self::base64url_encode($signature);
        
        if (!hash_equals($expected_signature, $signature_encoded)) {
            return false;
        }
        
        $payload = json_decode(self::base64url_decode($payload_encoded), true);
        
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Obtener secreto JWT
     * 
     * @return string
     */
    private static function get_jwt_secret(): string {
        // Usar la misma API key como secreto JWT
        if (class_exists('KL_Wallet_API_Config')) {
            return KL_Wallet_API_Config::get_api_key();
        }
        
        if (defined('KL_WALLET_API_KEY')) {
            return KL_WALLET_API_KEY;
        }
        
        return 'kl_wallet_jwt_secret_2024';
    }
    
    /**
     * Codificar en base64url
     * 
     * @param string $data
     * @return string
     */
    private static function base64url_encode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Decodificar base64url
     * 
     * @param string $data
     * @return string
     */
    private static function base64url_decode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }
}

/**
 * Funciones helper para JWT
 */

/**
 * Generar token JWT para servicio a servicio
 * 
 * @param string $service_name Nombre del servicio
 * @param int $expiration Tiempo de expiración en segundos
 * @return string
 */
function kl_wallet_generate_service_token(string $service_name, int $expiration = 3600): string {
    return KL_Wallet_JWT_Handler::generate_token([
        'service' => $service_name,
        'type' => 'service-to-service'
    ], $expiration);
}

/**
 * Verificar token de servicio
 * 
 * @param string $token Token JWT
 * @return array|false
 */
function kl_wallet_verify_service_token(string $token) {
    return KL_Wallet_JWT_Handler::verify_token($token);
} 